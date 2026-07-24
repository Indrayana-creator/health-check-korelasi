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
    <h1>Laporan Data Aset</h1>
    <p class="sub">Diekspor pada: {{ now()->format('d F Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>ASET ID</th>
                <th>Uker</th>
                <th>Kode Aset</th>
                <th>Merek/Type</th>
                <th>SN</th>
                <th>Umur</th>
                <th>Kondisi</th>
                <th>Nama User</th>
                <th>Jabatan</th>
                <th>PN</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($asetList as $aset)
                <tr>
                    <td>{{ $aset->no_asset }}</td>
                    <td>{{ $aset->uker?->nama }}</td>
                    <td>{{ $aset->kode_aset_kode }} - {{ $aset->kodeAset?->nama }}</td>
                    <td>{{ $aset->merek }} {{ $aset->tipe_model }}</td>
                    <td>{{ $aset->sn }}</td>
                    <td>{{ $aset->umur_tahun }} thn</td>
                    <td>{{ $aset->kondisi }}</td>
                    <td>{{ $aset->pemegang_nama }}</td>
                    <td>{{ $aset->jabatan }}</td>
                    <td>{{ $aset->pemegang_pn }}</td>
                    <td>{{ $aset->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="11">Belum ada data aset.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
