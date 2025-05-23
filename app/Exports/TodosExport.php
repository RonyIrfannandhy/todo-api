<?php

namespace App\Exports;

use App\Models\Todo;
use Maatwebsite\Excel\Concerns\{
    FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class TodosExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    protected $filters;
    protected $rowCount = 0;
    protected $totalTimeTracked = 0;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Todo::query();

        if (!empty($this->filters['title'])) {
            $query->where('title', 'like', '%' . $this->filters['title'] . '%');
        }

        if (!empty($this->filters['assignee'])) {
            $query->whereIn('assignee', explode(',', $this->filters['assignee']));
        }

        if (!empty($this->filters['status'])) {
            $query->whereIn('status', explode(',', $this->filters['status']));
        }

        if (!empty($this->filters['priority'])) {
            $query->whereIn('priority', explode(',', $this->filters['priority']));
        }

        if (!empty($this->filters['start']) && !empty($this->filters['end'])) {
            $query->whereBetween('due_date', [$this->filters['start'], $this->filters['end']]);
        }

        if (!empty($this->filters['min']) && !empty($this->filters['max'])) {
            $query->whereBetween('time_tracked', [$this->filters['min'], $this->filters['max']]);
        }

        return $query->select('title', 'assignee', 'due_date', 'time_tracked', 'status', 'priority');
    }

    public function headings(): array
    {
        return ['Title', 'Assignee', 'Due Date', 'Time Tracked', 'Status', 'Priority'];
    }

    public function map($row): array
    {
        $this->rowCount++;
        $this->totalTimeTracked += $row->time_tracked;

        return [
            $row->title,
            $row->assignee ?? 'Unassigned',
            $row->due_date ? Carbon::parse($row->due_date)->format('d-m-Y') : '-',
            $row->time_tracked,
            ucfirst($row->status),
            ucfirst($row->priority),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                // Header styling
                $sheet->getStyle('A1:F1')->getFont()->setBold(true);
                $sheet->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCFFCC');

                // Freeze first row
                $sheet->freezePane('A2');

                // Summary row
                $summaryRow = $this->rowCount + 2;
                $sheet->setCellValue("A{$summaryRow}", 'Total Todos:');
                $sheet->setCellValue("B{$summaryRow}", $this->rowCount);
                $sheet->setCellValue("C{$summaryRow}", 'Total Time Tracked:');
                $sheet->setCellValue("D{$summaryRow}", $this->totalTimeTracked);

                // Bold summary
                $sheet->getStyle("A{$summaryRow}:F{$summaryRow}")->getFont()->setBold(true);
            },
        ];
    }
}
