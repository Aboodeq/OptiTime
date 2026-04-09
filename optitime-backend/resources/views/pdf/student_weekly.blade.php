<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student schedule</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>OptiTime — Student weekly schedule</h2>
    <p>{{ $user->name }}</p>
    @php($items = $data['items'] ?? [])
    <table>
        <thead>
            <tr>
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
                <th>Course</th>
                <th>Room</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $row)
                <tr>
                    <td>{{ $row['day_of_week'] ?? '' }}</td>
                    <td>{{ $row['start_time'] ?? '' }}</td>
                    <td>{{ $row['end_time'] ?? '' }}</td>
                    <td>{{ $row['section']['course']['code'] ?? '' }}</td>
                    <td>{{ $row['room']['name'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No sessions.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
