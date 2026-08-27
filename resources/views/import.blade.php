<!DOCTYPE html>
<html>
<head>
    <title>Import Data Master</title>
</head>
<body style="font-family: sans-serif; max-width: 600px; margin: 40px auto;">
    <h2>Import Data Master</h2>

    <x-flash-status />
    @if ($errors->any())
        <p style="background: #f8d7da; padding: 10px; border-radius: 6px;">{{ $errors->first() }}</p>
    @endif

    <h3>1. Upload Database Pekerja (isi tabel ukers + pekerja)</h3>
    <p>File: <code>Database_All_Pekerja_BRI_SBY.xlsx</code></p>
    <form action="{{ route('import.pekerja') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" accept=".xlsx,.xls" required>
        <button type="submit">Upload &amp; Proses</button>
    </form>

    <hr>

    <h3>2. Upload Daftar Petugas IT (tandain is_petugas_it)</h3>
    <p>File: <code>Daftar_Petugas_IT_RO_Surabaya.xlsx</code> &mdash; upload nomor 1 dulu sebelum ini.</p>
    <form action="{{ route('import.petugasIt') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" accept=".xlsx,.xls" required>
        <button type="submit">Upload &amp; Proses</button>
    </form>
</body>
</html>
