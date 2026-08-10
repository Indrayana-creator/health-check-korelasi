@props(['tabs'])
{{-- $tabs: array of ['label' => string, 'href' => string, 'active' => bool] --}}
<div class="inline-flex gap-1 bg-white border border-gray-200 p-1 rounded-xl w-fit flex-wrap">
    @foreach ($tabs as $tab)
        <a
            href="{{ $tab['href'] }}"
            class="px-4 py-2 rounded-lg text-sm font-bold transition {{ $tab['active'] ? 'bg-cakrawala text-white' : 'text-gray-600 hover:bg-gray-50' }}"
        >{{ $tab['label'] }}</a>
    @endforeach
</div>
