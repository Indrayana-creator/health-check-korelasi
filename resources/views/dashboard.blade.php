<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 1. KPI Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-indigo-500">
                    <p class="text-sm text-gray-500">Total Aset</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalAset }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-blue-500">
                    <p class="text-sm text-gray-500">Total Form Health Check</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalFormHc }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 {{ $rataCompliance >= 80 ? 'border-green-500' : 'border-red-500' }}">
                    <p class="text-sm text-gray-500">Rata-rata Compliance</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $rataCompliance }}%</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- 2. Ranking cabang paling butuh perhatian (admin saja) --}}
                @if ($isAdmin)
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-gray-800 mb-4">Cabang Paling Butuh Perhatian</h3>
                        @if ($rankingCabang->isEmpty())
                            <p class="text-sm text-gray-400">Belum ada data health check.</p>
                        @else
                            <div class="space-y-2">
                                @foreach ($rankingCabang as $r)
                                    <div class="flex items-center justify-between text-sm border-b pb-2">
                                        <div>
                                            <p class="font-medium text-gray-700">{{ $r['uker'] }}</p>
                                            <p class="text-gray-400 text-xs">{{ $r['periode'] }}</p>
                                        </div>
                                        <span class="px-2 py-1 text-xs rounded font-semibold
                                            {{ $r['persen'] >= 95 ? 'bg-green-100 text-green-700' : ($r['persen'] >= 80 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                            {{ $r['persen'] }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-gray-800 mb-1">Uker Belum Mengisi Health Check</h3>
                        <p class="text-xs text-gray-400 mb-4">Belum pernah membuat form health check sama sekali (bukan sekadar compliance rendah).</p>
                        @if ($ukerBelumMengisi->isEmpty())
                            <p class="text-sm text-green-600">Semua uker sudah mengisi minimal 1 form health check.</p>
                        @else
                            <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                                @foreach ($ukerBelumMengisi as $u)
                                    <span class="px-2 py-1 text-xs rounded bg-red-50 text-red-600 border border-red-200">
                                        {{ $u->nama }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-3">{{ $ukerBelumMengisi->count() }} uker belum mengisi.</p>
                        @endif
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-gray-800 mb-1">Uker Belum Ada Data Aset</h3>
                        <p class="text-xs text-gray-400 mb-4">Belum ada satu pun aset tercatat untuk uker ini.</p>
                        @if ($ukerBelumAdaAset->isEmpty())
                            <p class="text-sm text-green-600">Semua uker sudah punya minimal 1 data aset.</p>
                        @else
                            <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                                @foreach ($ukerBelumAdaAset as $u)
                                    <span class="px-2 py-1 text-xs rounded bg-orange-50 text-orange-600 border border-orange-200">
                                        {{ $u->nama }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-3">{{ $ukerBelumAdaAset->count() }} uker belum ada data aset.</p>
                        @endif
                    </div>
                @endif

                {{-- 3. Distribusi aset per tipe perangkat --}}
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-semibold text-gray-800 mb-4">Distribusi Aset per Tipe</h3>
                    @if ($distribusiPerangkat->isEmpty())
                        <p class="text-sm text-gray-400">Belum ada data aset.</p>
                    @else
                        <div class="space-y-2">
                            @php $maxJumlah = $distribusiPerangkat->max('jumlah'); @endphp
                            @foreach ($distribusiPerangkat as $d)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-700">{{ $d->perangkat }}</span>
                                        <span class="text-gray-500">{{ $d->jumlah }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $maxJumlah ? ($d->jumlah / $maxJumlah * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- 4. Aktivitas terbaru --}}
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="font-semibold text-gray-800 mb-4">Aktivitas Terbaru</h3>
                @if ($aktivitasTerbaru->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada aktivitas.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($aktivitasTerbaru as $a)
                            <div class="flex items-start gap-3 text-sm border-b pb-2">
                                <span class="px-2 py-0.5 text-xs rounded {{ $a['jenis'] === 'Aset' ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $a['jenis'] }}
                                </span>
                                <div class="flex-1">
                                    <p class="text-gray-700">{{ $a['teks'] }}</p>
                                    <p class="text-gray-400 text-xs">{{ $a['waktu']->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
