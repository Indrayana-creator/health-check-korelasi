@props(['uker', 'action', 'color' => 'red'])

@php
    $palette = [
        'red' => 'bg-red-50 text-red-600 border-red-200',
        'orange' => 'bg-orange-50 text-orange-600 border-orange-200',
    ];
    $classes = $palette[$color] ?? $palette['red'];
@endphp

<div class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1.5 text-xs font-semibold rounded-lg border {{ $classes }}">
    <span>{{ $uker->nama }}</span>
    <form action="{{ $action }}" method="POST" onsubmit="return confirm('Kirim pengingat ke {{ $uker->nama }}?')">
        @csrf
        <button type="submit" title="Kirim Pengingat" class="w-5 h-5 flex items-center justify-center rounded hover:bg-black/10">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path></svg>
        </button>
    </form>
</div>
