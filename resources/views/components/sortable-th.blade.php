@props(['field'])

@php
    $sortAktif = request('sort') === $field;
    $dirSekarang = request('dir', 'asc');
    $dirBerikutnya = $sortAktif && $dirSekarang === 'asc' ? 'desc' : 'asc';
    $href = request()->fullUrlWithQuery(['sort' => $field, 'dir' => $dirBerikutnya, 'page' => null]);
@endphp

<th {{ $attributes->merge(['class' => 'px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide']) }}>
    <a href="{{ $href }}" class="inline-flex items-center gap-1 hover:text-gray-800 group">
        {{ $slot }}
        <span class="text-gray-300 group-hover:text-gray-500">
            @if ($sortAktif)
                @if ($dirSekarang === 'asc')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 text-cakrawala"><path d="M12 19V5M5 12l7-7 7 7"></path></svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 text-cakrawala"><path d="M12 5v14M5 12l7 7 7-7"></path></svg>
                @endif
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"></path></svg>
            @endif
        </span>
    </a>
</th>
