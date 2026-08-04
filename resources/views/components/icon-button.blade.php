@props(['variant' => 'neutral', 'label' => null, 'href' => null, 'type' => 'submit'])

@php
    $variants = [
        'neutral' => 'border-gray-200 bg-gray-50 text-gray-500 hover:bg-gray-100',
        'edit' => 'border-cakrawala/30 bg-cakrawala/10 text-cakrawala hover:bg-cakrawala/20',
        'danger' => 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100',
        'success' => 'border-green-200 bg-green-50 text-green-600 hover:bg-green-100',
    ];
    $classes = 'inline-flex items-center justify-center w-8 h-8 rounded-lg border transition '
        .($variants[$variant] ?? $variants['neutral']);
@endphp

@if ($href)
    <a href="{{ $href }}" title="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" title="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
