<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 9px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.sub { color: #666; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px 5px; text-align: left; }
        th { background-color: #0857C3; color: white; }
        tr:nth-child(even) { background-color: #f7f9fc; }
    </style>
</head>
<body>
    <h1>Laporan Log History</h1>
    <p class="sub">Diekspor pada: {{ now()->format('d F Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                @foreach ($headers as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d-m-Y H:i') }}</td>
                    <td>{{ $log->user?->name }}</td>
                    <td>{{ $log->modul }}</td>
                    <td>{{ $log->aksi }}</td>
                    <td>{{ $log->jumlah_baris }}</td>
                    <td>{{ $log->keterangan }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada log aktivitas.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
