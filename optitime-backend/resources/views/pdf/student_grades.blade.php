<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grades</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 6px; }
    </style>
</head>
<body>
    <h2>OptiTime — Grades</h2>
    <p>{{ $user->name }}</p>
    <table>
        <thead>
            <tr>
                <th>Course</th>
                <th>Numeric</th>
                <th>Letter</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grades as $g)
                <tr>
                    <td>{{ $g['section']['course']['code'] ?? '' }} — {{ $g['section']['course']['name'] ?? '' }}</td>
                    <td>{{ $g['numeric_grade'] ?? '—' }}</td>
                    <td>{{ $g['letter_grade'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No grades.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
