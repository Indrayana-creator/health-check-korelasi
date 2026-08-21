<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 9px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.sub { color: #666; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        td { text-align: center; vertical-align: top; padding: 8px 4px; width: 25%; }
        td img { width: 100px; height: 100px; }
        td p { margin: 3px 0 0; font-family: monospace; font-size: 9px; }
    </style>
</head>
<body>
    <h1>QR Code Aset</h1>
    <p class="sub">{{ $items->count() }} aset &middot; Diekspor pada: {{ now()->format('d F Y H:i') }}</p>

    <table>
        @foreach ($items->chunk(4) as $baris)
            <tr>
                @foreach ($baris as $item)
                    <td>
                        <img src="data:image/png;base64,{{ $item['qr'] }}" alt="QR {{ $item['aset']->no_asset }}">
                        <p>{{ $item['aset']->no_asset }}</p>
                    </td>
                @endforeach
                @for ($i = $baris->count(); $i < 4; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>
