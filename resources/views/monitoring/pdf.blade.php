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
    <h1>Laporan Monitoring Kendala</h1>
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
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->form?->uker?->nama }}</td>
                    <td>{{ $item->kategori }}</td>
                    <td>{{ $item->item_pemeriksaan }}</td>
                    <td>{{ $item->catatan }}</td>
                    <td>{{ $item->form?->periode }}</td>
                    <td>{{ $item->form?->tanggal_pemeriksaan }}</td>
                    <td>{{ $item->status_tindak_lanjut }}</td>
                    <td>{{ $item->catatan_tindak_lanjut }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada item bermasalah.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
