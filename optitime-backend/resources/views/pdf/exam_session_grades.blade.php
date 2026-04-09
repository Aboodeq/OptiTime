<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Session grades</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 6px; }
    </style>
</head>
<body>
    <h2>OptiTime — Session grades (session {{ $schedule_session_id }})</h2>
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Course</th>
                <th>Total</th>
                <th>Letter</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grades as $g)
                <tr>
                    <td>{{ $g->student->user->name ?? '' }}</td>
                    <td>{{ $g->session->courseOffering->course->code ?? '' }}</td>
                    <td>{{ $g->total ?? '—' }}</td>
                    <td>{{ $g->letter_grade ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No grades.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
