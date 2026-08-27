<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Lacak {{ $permintaan->kode_lacak }} - AsetSehat</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-800 bg-gray-50">
        <div class="min-h-screen flex flex-col items-center py-10 px-4">
            <div class="flex items-center gap-2.5 mb-6">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-nusantara to-cakrawala flex items-center justify-center">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <span class="text-xl font-extrabold tracking-tight text-gray-800">AsetSehat</span>
            </div>

            <div class="w-full max-w-lg">
                <x-card padding="p-6">
                    <div class="flex items-start justify-between gap-3 flex-wrap mb-1">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 mb-0.5">Kode Lacak</p>
                            <p class="text-lg font-extrabold font-mono text-gray-800 tracking-wide">{{ $permintaan->kode_lacak }}</p>
                        </div>
                        <x-badge :color="match($permintaan->status) { 'Done Terkirim' => 'green', 'Pending IT' => 'gray', default => 'yellow' }">
                            {{ $permintaan->status }}
                        </x-badge>
                    </div>
                    @if ($permintaan->sudahLama())
                        <p class="text-xs text-red-500 font-semibold mb-4">Sudah {{ $permintaan->hariDiStatusIni() }} hari di status ini.</p>
                    @else
                        <p class="text-xs text-gray-400 mb-4">{{ $permintaan->hariDiStatusIni() }} hari di status ini.</p>
                    @endif

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 mb-0.5">No Nota Dinas</p>
                            <p class="text-sm text-gray-800">{{ $permintaan->no_nota_dinas }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 mb-0.5">Cabang</p>
                            <p class="text-sm text-gray-800">{{ $permintaan->uker?->nama ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 mb-0.5">Tanggal Request</p>
                            <p class="text-sm text-gray-800">{{ $permintaan->tanggal_request?->format('d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 mb-0.5">Jumlah</p>
                            <p class="text-sm text-gray-800">{{ $permintaan->jumlah }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs font-semibold text-gray-400 mb-0.5">Fungsi Requester</p>
                            <p class="text-sm text-gray-800">{{ $permintaan->fungsi_requester }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs font-semibold text-gray-400 mb-0.5">Keterangan</p>
                            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $permintaan->keterangan }}</p>
                        </div>
                        @if ($permintaan->catatan_admin)
                            <div class="col-span-2">
                                <p class="text-xs font-semibold text-gray-400 mb-0.5">Catatan Admin</p>
                                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $permintaan->catatan_admin }}</p>
                            </div>
                        @endif
                    </div>
                </x-card>

                <x-card padding="p-6" class="mt-4">
                    <h3 class="font-extrabold text-sm text-gray-800 mb-4">Riwayat Status</h3>
                    @if ($permintaan->statusLogs->isEmpty())
                        <p class="text-gray-400 text-sm">Belum ada riwayat perubahan status.</p>
                    @else
                        <div class="space-y-2.5">
                            @foreach ($permintaan->statusLogs as $log)
                                <div class="flex items-start justify-between gap-3 text-sm border-b border-gray-100 pb-2.5 last:border-0 last:pb-0">
                                    <div>
                                        <p class="font-semibold text-gray-700">
                                            @if ($log->status_lama)
                                                {{ $log->status_lama }}
                                                <span class="mx-1 text-gray-400">&rarr;</span>
                                            @endif
                                            {{ $log->status_baru }}
                                        </p>
                                        @if ($log->catatan_admin)
                                            <p class="text-gray-500 text-xs mt-0.5">{{ $log->catatan_admin }}</p>
                                        @endif
                                    </div>
                                    <p class="text-gray-400 text-xs whitespace-nowrap">{{ $log->created_at?->format('d M Y H:i') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <p class="text-center text-xs text-gray-400 mt-6">
                    Halaman ini bisa dibuka siapa saja yang punya link -- simpan link ini untuk cek status kapan saja.
                </p>
            </div>
        </div>
    </body>
</html>
