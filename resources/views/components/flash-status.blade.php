@props(['autoHide' => true])

@if (session('status'))
    <div
        x-data="{ show: true }"
        x-show="show"
        @if ($autoHide) x-init="setTimeout(() => show = false, 5000)" @endif
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        {{ $attributes->merge(['class' => 'p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm']) }}
    >
        {{ $slot->isEmpty() ? session('status') : $slot }}
    </div>
@endif
