<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        h2 { font-size: 12px; margin-top: 20px; margin-bottom: 4px; }
        p.sub { color: #666; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background-color: #0857C3; color: white; }
        tr:nth-child(even) { background-color: #f7f9fc; }
    </style>
</head>
<body>
    <h1>Laporan Health Check</h1>
    <p class="sub">Diekspor pada: {{ now()->format('d F Y H:i') }}</p>

    @forelse ($ringkasanPerForm as $entry)
        <h2>{{ $entry['form']->uker?->nama }} &mdash; {{ $entry['form']->periode }} ({{ $entry['form']->tanggal_pemeriksaan }})</h2>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Total Item</th>
                    <th>OK</th>
                    <th>Not OK</th>
                    <th>N/A</th>
                    <th>Belum Diperiksa</th>
                    <th>Compliance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entry['kategori'] as $r)
                    <tr>
                        <td>{{ $r['kategori'] }}</td>
                        <td>{{ $r['total'] }}</td>
                        <td>{{ $r['ok'] }}</td>
                        <td>{{ $r['not_ok'] }}</td>
                        <td>{{ $r['na'] }}</td>
                        <td>{{ $r['belum'] }}</td>
                        <td>{{ $r['persen'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p>Belum ada form health check.</p>
    @endforelse
</body>
</html>
