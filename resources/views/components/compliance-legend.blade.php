{{--
    Legenda skala kepatuhan (Sangat Baik/Baik/Perlu Perhatian), reusable di
    semua tempat yang nampilin badge status compliance. Ambang batasnya
    ditarik langsung dari App\Support\ComplianceScale (satu sumber
    kebenaran) -- kalau nanti ambang batasnya berubah, legenda ini otomatis
    ikut berubah, gak perlu di-update manual satu-satu.
--}}
@props(['class' => ''])

<div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs {{ $class }}">
    <span class="font-semibold text-gray-500">Legenda:</span>
    <span class="inline-flex items-center gap-1.5">
        <x-badge color="green">Sangat Baik</x-badge>
        <span class="text-gray-400">&ge;{{ \App\Support\ComplianceScale::AMBANG_SANGAT_BAIK }}%</span>
    </span>
    <span class="inline-flex items-center gap-1.5">
        <x-badge color="yellow">Baik</x-badge>
        <span class="text-gray-400">{{ \App\Support\ComplianceScale::AMBANG_BAIK }}-{{ \App\Support\ComplianceScale::AMBANG_SANGAT_BAIK - 1 }}%</span>
    </span>
    <span class="inline-flex items-center gap-1.5">
        <x-badge color="red">Perlu Perhatian</x-badge>
        <span class="text-gray-400">&lt;{{ \App\Support\ComplianceScale::AMBANG_BAIK }}%</span>
    </span>
</div>
