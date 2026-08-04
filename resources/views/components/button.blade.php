@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button'])

@php
    $variants = [
        'primary' => 'bg-cakrawala text-white border border-transparent hover:bg-nusantara',
        'secondary' => 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50',
        'danger' => 'bg-red-600 text-white border border-transparent hover:bg-red-700',
        'warning' => 'bg-yellow-600 text-white border border-transparent hover:bg-yellow-700',
        'success' => 'bg-green-600 text-white border border-transparent hover:bg-green-700',
        'ghost' => 'bg-transparent text-gray-600 border border-transparent hover:bg-gray-100',
    ];
    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
    ];
    $classes = 'inline-flex items-center justify-center gap-1.5 rounded-lg font-semibold whitespace-nowrap transition disabled:opacity-50 disabled:cursor-not-allowed '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
