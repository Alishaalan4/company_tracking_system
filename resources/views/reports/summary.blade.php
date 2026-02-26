<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Summary</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #222;
            font-size: 14px;
        }
        h1 {
            margin-bottom: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Attendance Summary</h1>
    <table>
        <thead>
            <tr>
                <th>Metric</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Late</td>
                <td>{{ $data['total_late'] ?? 0 }}</td>
            </tr>
            <tr>
                <td>Total Absent</td>
                <td>{{ $data['total_absent'] ?? 0 }}</td>
            </tr>
            <tr>
                <td>Total Early Leave</td>
                <td>{{ $data['total_early'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
