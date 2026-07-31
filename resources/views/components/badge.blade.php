@props(['color' => 'gray'])

@php
    $colors = [
        'green' => 'bg-green-100 text-green-700',
        'yellow' => 'bg-yellow-100 text-yellow-700',
        'red' => 'bg-red-100 text-red-700',
        'blue' => 'bg-blue-100 text-blue-700',
        'nusantara' => 'bg-cakrawala/10 text-nusantara',
        'gray' => 'bg-gray-100 text-gray-600',
    ];
    $classes = $colors[$color] ?? $colors['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {$classes}"]) }}>
    {{ $slot }}
</span>
