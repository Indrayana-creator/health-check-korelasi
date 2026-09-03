{{--
    Link "wa.me" siap klik buat 1 Pekerja, reusable -- dipakai di Kelola
    Pekerja (kolom No HP) dan Monitoring Kendala (kontak petugas IT uker
    terkait). Satu tempat, biar ikon & style-nya gak kececer di banyak file.

    Props:
    - pekerja: model Pekerja (butuh accessor whatsapp_url & field nama)
    - Slot opsional -- kalau diisi, dipakai sebagai teks link; default nama pekerja.
--}}
@props(['pekerja'])

@if ($pekerja?->whatsapp_url)
    <a href="{{ $pekerja->whatsapp_url }}" target="_blank" rel="noopener" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-green-600 hover:text-green-700 hover:underline font-semibold']) }}>
        <svg viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 flex-none"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.39 1.26 4.81L2 22l5.44-1.35a9.9 9.9 0 004.6 1.13h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.85 9.85 0 0012.04 2zm5.8 14.1c-.24.68-1.4 1.3-1.93 1.35-.5.06-1.02.27-3.4-.71-2.87-1.19-4.7-4.1-4.85-4.29-.14-.19-1.16-1.55-1.16-2.95 0-1.4.73-2.08.99-2.37.26-.28.57-.35.76-.35.19 0 .38 0 .55.01.18.01.42-.07.65.5.24.58.82 2 .89 2.14.07.14.12.31.02.5-.09.19-.14.31-.28.47-.14.16-.29.36-.42.48-.14.14-.28.29-.12.57.16.28.71 1.17 1.52 1.9 1.05.94 1.93 1.23 2.21 1.37.28.14.44.12.6-.07.16-.19.68-.79.86-1.06.18-.28.36-.23.6-.14.24.09 1.55.73 1.82.86.27.14.44.21.51.32.07.12.07.68-.17 1.36z"></path></svg>
        {{ $slot->isEmpty() ? $pekerja->nama : $slot }}
    </a>
@endif
