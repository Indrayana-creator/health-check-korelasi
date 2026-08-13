<?php

// Daftar checklist resmi dari template IT_Endpoint_Health_Check_Form_v2.xlsx
// Setiap kali form health check baru dibuat, seluruh item ini otomatis
// digenerate sebagai baris health_check_items dengan status default
// "Belum Diperiksa".
//
// Kategori "F - Genset" BUKAN dari template resmi di atas -- ini draft
// berdasarkan praktik umum pemeliharaan genset, ditambahkan atas
// persetujuan langsung user (bukan lewat approval atasan/dokumen resmi).
// Kalau nanti ada daftar resmi dari atasan, item-item di kategori ini
// harus disesuaikan/diganti. Sengaja dikasih huruf "F" (bukan "E") biar
// gak bentrok sama "Kategori E - Dokumentasi Visual" yang sudah lebih
// dulu jadi istilah baku di seluruh aplikasi (lihat dashboard & form
// health check) walau bukan bagian dari config ini.

return [
    'A - Ruang Server/Jaringan' => [
        'Suhu ruangan terjaga pada rentang 18°C – 22°C',
        'Ruangan bebas dari debu berlebih',
        'Tidak ada barang di atas rak (cairan, kardus, dsb.)',
        'Tidak ada barang mudah terbakar di area server',
        'Ruang server steril dari barang/peralatan yang tidak diperlukan',
        'Hanya personel berwenang yang dapat masuk area server',
        'Log akses lengkap dan terdokumentasi tanpa gap',
        'Tidak ada akses tidak terotorisasi tercatat',
        'Pintu depan semua rak terkunci rapat',
        'Pintu belakang semua rak terkunci rapat',
        'Kondisi kunci dan engsel baik, tidak ada yang rusak/longgar',
        'Buku log/logbook tersedia dan terisi setiap kali rak dibuka',
        'Setiap entri log lengkap: tanggal, waktu, nama, dan keperluan',
        'Tidak ada pembukaan rak tanpa pencatatan di logbook',
        'Kamera CCTV aktif mengarah langsung ke area rak',
        'Coverage rak memadai, tidak ada blind spot di area kritikal',
        'Recording berjalan aktif dan kualitas gambar jelas',
        'Posisi AC TIDAK tepat di atas rak server, UPS, atau panel dan jendela yang terkena sinar matahari langsung',
    ],
    'B - CCTV & Storage' => [
        'Kapasitas storage mencukupi untuk rekaman 90 hari',
        'Sistem overwrite otomatis aktif dan terkonfigurasi dengan benar',
        'Seluruh jumlah channel kamera aktif sesuai jumlah yang terpasang',
        "Tidak ada channel dengan status 'Video Loss'",
        'Semua kamera menampilkan gambar live normal, tidak ada gambar beku',
        'Seluruh kamera merekam minimal resolusi 2 MP (1080p) dan berbasis IP',
        'Setting waktu NVR sesuai waktu aktual (sinkron NTP maupun manual)',
        'Penamaan channel disesuaikan dengan nama area yang disorot',
        'Jadwal rekaman aktif 24/7 tanpa gap atau jeda',
        'Metode kompresi diatur pada H.265/H.265+ untuk efisiensi storage',
        'Kualitas gambar tidak berkurang secara signifikan akibat kompresi',
        'Status S.M.A.R.T. semua HDD: GOOD/PASSED',
        'Tidak ada bad sector pada HDD manapun',
        'Tidak ada alarm kegagalan disk aktif',
        'Rekaman tersedia minimal selama 90 hari',
        "Tidak ada 'gap' atau potongan waktu pada rekaman",
        'Playback lancar tanpa error pada semua channel yang diuji',
        'Password NVR/DVR bukan password default pabrikan',
        'User dan password dikelola oleh petugas CCTV yang ditunjuk secara resmi',
        'Pergantian password dilakukan secara periodik setiap 3 bulan',
        'Tidak ada password sharing antar pengguna',
        'Dokumentasi pengelola dan riwayat pergantian password tersedia',
    ],
    'C - Jaringan' => [
        'Tidak ada lampu indikator Merah/Orange (Fault) pada switch manapun',
        'Semua LED port aktif berwarna hijau dan berkedip normal',
        'Power LED menyala hijau di semua perangkat jaringan',
        'Semua kabel terpasang di dalam cable manager',
        'Kabel tidak menghalangi aliran udara di dalam rak',
        'Semua kabel berlabel sesuai dokumentasi jaringan',
        'Tidak ada dead zone di area yang diperlukan untuk aktivitas kerja',
        'Tidak ada SSID Wifi terbaca dari luar kantor',
        'Tidak terdapat perangkat jaringan tambahan yang ilegal di jaringan LAN',
        'Tidak ada SSID asing atau Rogue AP terdeteksi di area kantor',
        'End device yang terhubung ke LAN/WIFI merupakan perangkat yang sudah terdaftar di Uker',
    ],
    'D - Power System (UPS)' => [
        'Beban UPS tidak melebihi 70% dari kapasitas total',
        'Nilai load terpantau stabil dan terdokumentasi di panel LCD',
        'Tidak ada alarm overload aktif pada UPS',
        "Tidak ada alarm 'Replace Battery' aktif",
        'Usia baterai dalam batas rekomendasi pabrikan (umumnya maks. 3 tahun)',
        'Arrester terpasang dan berfungsi baik',
        'Grounding terpasang dan berfungsi baik',
        'Panel ATS (Automatic Transfer Switch) berfungsi baik dan perpindahan sumber listrik berjalan otomatis',
        'Kondisi fisik panel COS baik, tidak ada tanda keausan atau panas berlebih',
        'Panel COS berlabel jelas dan dokumentasi tersedia',
    ],
    'F - Genset' => [
        'Genset dapat menyala otomatis (auto-start) saat pasokan listrik PLN padam',
        'Automatic Transfer Switch (ATS) berfungsi baik, perpindahan PLN-Genset berjalan otomatis',
        'Level bahan bakar (solar) minimal 3/4 dari kapasitas tangki',
        'Tidak ada kebocoran bahan bakar, oli, maupun air radiator',
        'Level oli mesin dalam batas normal sesuai indikator dipstick',
        'Air radiator/coolant cukup dan tidak keruh',
        'Aki (accu) starter dalam kondisi baik, tegangan sesuai standar (tidak soak)',
        'Panel kontrol genset menunjukkan status normal, tidak ada alarm/fault aktif',
        'Uji beban (load test) dilakukan rutin sesuai jadwal pemeliharaan',
        'Ruang genset bersih, berventilasi baik, dan bebas dari barang mudah terbakar',
        'Dokumentasi jadwal pemeliharaan dan riwayat servis genset tersedia dan terisi',
    ],
];
