@props(['value', 'decimals' => 0, 'suffix' => ''])

{{-- Angka animasi count-up dari 0 ke nilai aslinya pas elemen ini kerender --
     pakai id-ID locale biar formatnya konsisten sama number_format($x, 0, ',', '.')
     yang dipakai di seluruh app (titik buat ribuan, koma buat desimal). --}}
<span
    x-data="{ display: 0, target: {{ is_numeric($value) ? $value : 0 }} }"
    x-init="
        let start = null;
        const duration = 800;
        const step = (ts) => {
            if (!start) start = ts;
            const progress = Math.min((ts - start) / duration, 1);
            display = target * progress;
            if (progress < 1) requestAnimationFrame(step);
            else display = target;
        };
        requestAnimationFrame(step);
    "
    x-text="display.toLocaleString('id-ID', { minimumFractionDigits: {{ $decimals }}, maximumFractionDigits: {{ $decimals }} }) + '{{ $suffix }}'"
>{{ $value }}</span>
