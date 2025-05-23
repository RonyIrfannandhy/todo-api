<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Todo;
use App\Exports\TodosExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TodoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'assignee' => 'nullable|string|max:255',
            'due_date' => 'required|date|after_or_equal:today',
            'time_tracked' => 'nullable|integer|min:0',
            'status' => ['nullable', Rule::in(['pending', 'open', 'in_progress', 'completed'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
        ]);

        $validated['status'] = $validated['status'] ?? 'pending';
        $validated['time_tracked'] = $validated['time_tracked'] ?? 0;

        $todo = Todo::create($validated);

        return response()->json([
            'message' => 'Todo created successfully',
            'data' => $todo,
        ], 201);
    }

    public function export(Request $request)
    {
        $filters = $request->only([
            'title', 'assignee', 'status', 'priority',
            'start', 'end', 'min', 'max'
        ]);

        return Excel::download(new TodosExport($filters), 'todos.xlsx');
    }

    public function chart(Request $request)
    {
        $request->validate([
            'type' => 'required|in:status,priority,assignee'
        ]);

        $type = $request->query('type');

        switch ($type) {
            case 'status':
                $summary = Todo::select('status', DB::raw('count(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');

                return response()->json([
                    'status_summary' => [
                        'pending' => $summary['pending'] ?? 0,
                        'open' => $summary['open'] ?? 0,
                        'in_progress' => $summary['in_progress'] ?? 0,
                        'completed' => $summary['completed'] ?? 0,
                    ]
                ]);

            case 'priority':
                $summary = Todo::select('priority', DB::raw('count(*) as total'))
                    ->groupBy('priority')
                    ->pluck('total', 'priority');

                return response()->json([
                    'priority_summary' => [
                        'low' => $summary['low'] ?? 0,
                        'medium' => $summary['medium'] ?? 0,
                        'high' => $summary['high'] ?? 0,
                    ]
                ]);

            case 'assignee':
                $assignees = Todo::select('assignee')
                    ->distinct()
                    ->pluck('assignee');

                $result = [];
                foreach ($assignees as $assignee) {
                    $label = $assignee ?? 'Unassigned';

                    $totalTodos = Todo::where('assignee', $assignee)->count();
                    $totalPending = Todo::where('assignee', $assignee)
                        ->where('status', 'pending')->count();
                    $timeTrackedCompleted = Todo::where('assignee', $assignee)
                        ->where('status', 'completed')
                        ->sum('time_tracked');

                    $result[$label] = [
                        'total_todos' => $totalTodos,
                        'total_pending_todos' => $totalPending,
                        'total_timetracked_completed_todos' => $timeTrackedCompleted,
                    ];
                }

                return response()->json([
                    'assignee_summary' => $result
                ]);
        }
    }
}
