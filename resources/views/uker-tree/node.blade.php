{{--
    Partial rekursif buat 1 node tree + semua anaknya.
    Dipanggil ulang buat tiap level (Kanwil -> Area -> Cabang -> Unit).
--}}
<div
    x-data="{ terbuka: {{ $level === 0 ? 'true' : 'false' }} }"
    class="{{ $level > 0 ? 'ml-6 border-l border-gray-200 pl-4' : '' }} py-1"
>
    <div class="flex items-center justify-between gap-3 py-1.5 group">
        <button
            @click="terbuka = !terbuka"
            class="flex items-center gap-2 text-left flex-1 min-w-0"
            @if ($node['anak']->isEmpty()) disabled @endif
        >
            @if ($node['anak']->isNotEmpty())
                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0 transition-transform" :class="terbuka ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            @else
                <span class="w-3.5 flex-shrink-0"></span>
            @endif

            <span class="px-1.5 py-0.5 text-[10px] font-semibold rounded flex-shrink-0
                {{ match($node['jenis']) {
                    'KANWIL' => 'bg-purple-100 text-purple-700',
                    'AREA' => 'bg-blue-100 text-blue-700',
                    'KC' => 'bg-indigo-100 text-indigo-700',
                    'KCP' => 'bg-teal-100 text-teal-700',
                    'UNIT' => 'bg-gray-100 text-gray-600',
                    default => 'bg-gray-100 text-gray-500',
                } }}">
                {{ $node['jenis'] ?? '-' }}
            </span>

            <span class="text-sm font-semibold text-gray-800 truncate">{{ $node['nama'] }}</span>
            <span class="text-xs text-gray-400 flex-shrink-0">({{ $node['kode'] }})</span>
        </button>

        <div class="flex items-center gap-3 text-xs text-gray-500 flex-shrink-0 whitespace-nowrap">
            @if ($node['jumlah_unit_bawah'] > 0)
                <span>{{ $node['jumlah_unit_bawah'] }} unit di bawah</span>
            @endif
            <button @click.stop="$store.ukerDetail.buka({{ $node['kode'] }})" class="font-semibold text-gray-700 hover:underline cursor-pointer">
                {{ $node['total_aset'] }} aset
            </button>
            @if ($node['rata_compliance'] !== null)
                <button @click.stop="$store.complianceDetail.buka({{ $node['kode'] }})"
                    class="px-2 py-0.5 rounded-full font-semibold hover:opacity-75 cursor-pointer
                    {{ $node['rata_compliance'] >= 95 ? 'bg-green-100 text-green-700' : ($node['rata_compliance'] >= 80 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                    {{ $node['rata_compliance'] }}%
                </button>
            @else
                <button @click.stop="$store.complianceDetail.buka({{ $node['kode'] }})" class="text-gray-300 hover:text-gray-500 cursor-pointer">
                    - compliance
                </button>
            @endif
            <button @click.stop="$store.ukerDetail.buka({{ $node['kode'] }})" class="text-cakrawala hover:underline font-semibold">
                Detail
            </button>
        </div>
    </div>

    @if ($node['anak']->isNotEmpty())
        <div x-show="terbuka" x-cloak>
            @foreach ($node['anak'] as $anak)
                @include('uker-tree.node', ['node' => $anak, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
