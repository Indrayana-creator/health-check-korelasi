@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-cakrawala focus:ring-cakrawala rounded-lg shadow-sm text-sm']) }}>
