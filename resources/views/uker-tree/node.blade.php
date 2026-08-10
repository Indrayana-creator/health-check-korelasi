{{--
    Partial rekursif buat 1 node org chart + semua anaknya.
    Dipanggil ulang buat tiap level (Kanwil -> Area -> KC -> KCP -> Unit).
    Anak baru di-render ke DOM pas expand pertama kali (x-if, bukan x-show),
    biar cabang yang masih ketutup gak ikut nge-bloat DOM di render awal --
    penting karena total unit di seluruh tree bisa ratusan.
--}}
<li x-data="{ terbuka: {{ $level === 0 ? 'true' : 'false' }} }">
    <div class="org-box inline-flex flex-col items-stretch gap-1.5 rounded-xl border-2 bg-white px-3.5 py-2.5 text-left shadow-sm w-[200px]
        {{ match($node['jenis']) {
            'KANWIL' => 'border-purple-300',
            'AREA' => 'border-blue-300',
            'KC' => 'border-indigo-300',
            'KCP' => 'border-teal-300',
            'UNIT' => 'border-gray-300',
            default => 'border-gray-200',
        } }}">
        <div class="flex items-center gap-1.5">
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
            <span class="text-[11px] text-gray-400">({{ $node['kode'] }})</span>
        </div>

        <p class="text-sm font-bold text-gray-800 leading-snug" title="{{ $node['nama'] }}">{{ $node['nama'] }}</p>

        <div class="flex items-center gap-1.5 text-[11px] text-gray-500 flex-wrap">
            <button type="button" @click.stop="$store.ukerDetail.buka({{ $node['kode'] }})" class="font-semibold text-gray-700 hover:underline">
                {{ $node['total_aset'] }} aset
            </button>
            @if ($node['rata_compliance'] !== null)
                <button type="button" @click.stop="$store.complianceDetail.buka({{ $node['kode'] }})"
                    class="px-1.5 py-0.5 rounded-full font-semibold hover:opacity-75
                    {{ $node['rata_compliance'] >= 95 ? 'bg-green-100 text-green-700' : ($node['rata_compliance'] >= 80 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                    {{ $node['rata_compliance'] }}%
                </button>
            @else
                <button type="button" @click.stop="$store.complianceDetail.buka({{ $node['kode'] }})" class="text-gray-300 hover:text-gray-500">
                    - compliance
                </button>
            @endif
        </div>

        @if ($node['anak']->isNotEmpty())
            <button
                type="button"
                @click="terbuka = !terbuka"
                class="mt-1 flex items-center justify-center gap-1 text-[11px] font-semibold rounded-lg py-1 border border-gray-200 text-cakrawala hover:bg-cakrawala/5"
            >
                <svg class="w-3 h-3 transition-transform flex-shrink-0" :class="terbuka ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <span x-text="terbuka ? 'Tutup' : 'Buka'"></span> ({{ $node['anak']->count() }})
            </button>
        @endif
    </div>

    @if ($node['anak']->isNotEmpty())
        <template x-if="terbuka">
            <ul>
                @foreach ($node['anak'] as $anak)
                    @include('uker-tree.node', ['node' => $anak, 'level' => $level + 1])
                @endforeach
            </ul>
        </template>
    @endif
</li>
