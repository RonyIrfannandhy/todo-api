<table>
    <thead>
        <tr>
            <th>Title</th>
            <th>Assignee</th>
            <th>Due Date</th>
            <th>Time Tracked</th>
            <th>Status</th>
            <th>Priority</th>
        </tr>
    </thead>
    <tbody>
        @foreach($todos as $todo)
            <tr>
                <td>{{ $todo->title }}</td>
                <td>{{ $todo->assignee }}</td>
                <td>{{ $todo->due_date }}</td>
                <td>{{ $todo->time_tracked }}</td>
                <td>{{ $todo->status }}</td>
                <td>{{ $todo->priority }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="2"><strong>Total</strong></td>
            <td></td>
            <td><strong>{{ $summary['total_time_tracked'] }}</strong></td>
            <td colspan="2"><strong>{{ $summary['total_todos'] }} todos</strong></td>
        </tr>
    </tbody>
</table>