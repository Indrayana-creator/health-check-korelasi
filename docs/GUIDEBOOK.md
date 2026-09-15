# Guidebook AsetSehat

Dokumen ini isinya draft konten buat disusun ulang di Canva. Ada 4 bagian besar — tinggal dipilih & disusun sesuai kebutuhan (gak harus semua bagian dipakai, atau bisa dijadiin beberapa dokumen terpisah kalau kepanjangan buat satu guidebook).

---

## BAGIAN 1 — Ringkasan Proyek & Progres

### Latar Belakang

AsetSehat adalah sistem internal buat mengelola dua hal utama di lingkup unit kerja (kantor cabang):

1. **Data Aset IT** — inventaris perangkat (komputer, printer, CCTV, dst) per unit kerja, lengkap dengan kondisi, status keamanan (hardening, bitlocker, DLP, antivirus), dan riwayat perubahannya.
2. **Health Check** — checklist pemeriksaan berkala kondisi ruang server/jaringan/perangkat per unit kerja, buat memantau kepatuhan pemeliharaan infrastruktur IT.

Sebelumnya proses ini dilakukan manual (Excel/laporan terpisah per cabang), sehingga sulit dipantau terpusat dan gampang gak konsisten. AsetSehat menyatukan semuanya jadi satu platform berbasis web dengan kontrol akses berjenjang (admin vs petugas cabang).

### Fitur Utama yang Sudah Dibangun

**Manajemen Data**
- Kelola Aset (CRUD, mutasi antar unit kerja, riwayat kondisi, soft-delete/sampah)
- Kelola Health Check (checklist per kategori, submit → approval admin)
- Kelola User, Unit Kerja (Uker), Pekerja, Kode Aset
- Import massal data Pekerja & Petugas IT lewat Excel

**Workflow Approval (kontrol berjenjang)**
- Permintaan Edit Aset — cabang gak bisa langsung ubah data aset, harus ajukan dulu & disetujui admin
- Permintaan Hapus Aset — pola sama kayak edit, cabang harus ajukan & disetujui admin sebelum bisa hapus
- Approval Health Check — submission checklist harus di-approve admin

**Monitoring & Pelaporan**
- Monitoring Kendala — laporan kerusakan aset dari lapangan (bisa sertakan foto), lengkap tombol kontak WhatsApp langsung ke petugas IT unit kerja terkait
- Permintaan Perangkat — pengajuan kebutuhan perangkat baru, bisa dilacak statusnya
- Dashboard ringkasan kondisi aset & compliance Health Check per unit kerja
- Rekap per Cabang, Struktur Organisasi (pohon unit kerja + compliance)
- Export Excel/PDF di hampir semua halaman data

**Audit & Keamanan**
- Log History — jejak semua aksi penting di sistem (siapa, apa, kapan)
- Login History — jejak tiap percobaan login (berhasil/gagal/ditolak), lengkap IP & perangkat
- Riwayat Perubahan User — jejak perubahan role/status akun (buat access-review)
- Kontrol sesi ganda — satu akun gak bisa login bersamaan di 2 perangkat tanpa konfirmasi
- **MFA (Verifikasi 2 Langkah)** khusus akun admin — wajib pakai authenticator app (Google/Microsoft Authenticator), lengkap recovery codes cadangan

### Keputusan Desain yang Penting Dipahami

Ini bagian yang paling berguna buat ditunjukkan ke mentor — nunjukin bukan cuma "bisa jalan", tapi ada pertimbangan di baliknya:

- **RBAC berjenjang berbasis pohon unit kerja** — admin bisa lihat semua data, petugas cabang cuma bisa lihat data unit kerjanya sendiri + turunannya (bukan cuma exact match), dicek di level Policy (server-side), bukan cuma disembunyiin di tampilan.
- **Approval workflow "sekali pakai"** — izin edit/hapus yang udah disetujui cuma bisa dipakai SATU KALI aksi, abis itu terkunci lagi otomatis. Ini nyegah izin lama dipakai berulang buat aksi lain di kemudian hari.
- **MFA cuma diwajibkan buat admin, bukan semua user** — pertimbangannya trade-off keamanan vs friksi: akun admin risikonya paling tinggi (akses & approve semua data), jadi paling perlu lapisan tambahan, tapi gak perlu bikin ratusan akun cabang jadi ribet login.
- **Ditemukan & ditutup celah keamanan riil**: fitur "Delete Massal (Excel)" ternyata bisa dipakai buat hapus data tanpa lewat approval yang baru dibikin sama sekali — celah ini ketemu pas review ulang, bukan dari awal desain, dan langsung ditutup (dibatasi khusus admin).
- **Audit log insert-only** — tabel log (Login History, riwayat perubahan) didesain gak bisa diubah/dihapus lewat aplikasi, cuma nambah baris baru — biar riwayatnya bisa dipercaya sebagai bukti audit.

### Status Saat Ini

- **482 automated test** (Pest) yang mencakup hampir semua alur penting, semuanya lolos — dijalankan tiap ada perubahan buat mastiin gak ada fitur lama yang rusak.
- Siap masuk tahap persiapan deploy ke hosting production.

---

## BAGIAN 2 — Panduan Pengguna (User Manual)

*Semua screenshot ada di folder [`docs/screenshots/`](screenshots/), diurutkan sesuai nomor di tiap langkah di bawah — tinggal drag-drop ke Canva.*

### Login

1. Buka halaman login, masukin **PN (Personal Number)** dan password.

   ![Halaman Login](screenshots/01-login.png)

2. Kalau akun kedapatan masih aktif di perangkat lain, sistem bakal minta konfirmasi dulu sebelum lanjut (sesi lama otomatis logout begitu dikonfirmasi).
3. **Khusus akun admin**: kalau ini pertama kali dan belum setup MFA, bakal diarahkan ke halaman Setup Verifikasi 2 Langkah dulu (scan QR pakai authenticator app). Kalau MFA udah aktif, tiap login baru diminta kode 6 digit dari authenticator app. *(Lihat langkah detail di bagian "Setup MFA" di bawah.)*
4. Begitu berhasil masuk, langsung diarahkan ke Dashboard — ringkasan kondisi aset & compliance Health Check.

   ![Dashboard](screenshots/02-dashboard.png)

### Untuk Petugas Cabang (Role: User)

**Kelola Aset**
- Lihat daftar aset milik unit kerja sendiri (+ turunannya kalau ada).

  ![Daftar Aset](screenshots/03-kelola-aset-index.png)

- Tambah aset baru lewat tombol "Tambah Aset", isi form data aset.

  ![Tambah Aset](screenshots/04-tambah-aset.png)

- Mau ubah/hapus data aset? Buka halaman Detail Aset — klik "Ajukan Permintaan Edit" atau "Ajukan Permintaan Hapus", isi alasan (opsional), tunggu admin approve. Data terkunci sampai disetujui.

  ![Detail Aset — Ajukan Permintaan Hapus](screenshots/05-detail-aset.png)

- Tiap aset punya QR code sendiri (bisa di-print & ditempel fisik) — discan langsung buka halaman detail aset itu (lihat kartu "QR Code Aset" di screenshot di atas).

**Health Check**
- Lihat daftar Health Check per unit kerja beserta status compliance-nya.

  ![Daftar Health Check](screenshots/06-health-check-index.png)

- Isi checklist kondisi per kategori (Ruang Server, CCTV, Jaringan, dst) secara berkala.

  ![Form Isi Health Check](screenshots/07-health-check-form.png)

- Submit buat direview admin — status berubah jadi "Menunggu", lalu "Disetujui"/"Ditolak".

**Monitoring Kendala**
- Kalau nemu aset rusak, klik "Lapor Kerusakan" dari halaman detail aset, isi deskripsi + foto (opsional). Semua laporan kendala terpantau di sini, lengkap status tindak lanjutnya.

  ![Monitoring Kendala](screenshots/08-monitoring-kendala.png)

- Kalau butuh kontak cepat ke petugas IT unit kerja terkait, ada tombol WhatsApp langsung di daftar kendala.

**Permintaan Perangkat**
- Ajukan kebutuhan perangkat baru, dapat kode lacak buat cek status pengajuan kapan aja (gak perlu login).

  ![Permintaan Perangkat](screenshots/09-permintaan-perangkat.png)

### Untuk Admin

Semua yang bisa dilakukan petugas cabang, PLUS:

**Setup MFA (wajib, cuma sekali di awal)**

1. Login pertama kali sebagai admin langsung diarahkan ke halaman ini — scan QR pakai authenticator app (Google Authenticator/Microsoft Authenticator), masukin kode 6 digit yang muncul buat konfirmasi.

   ![Setup Verifikasi 2 Langkah](screenshots/10-setup-mfa.png)

2. Begitu berhasil, muncul 8 kode cadangan (recovery codes) — **wajib disimpan**, cuma ditampilkan sekali ini aja. Dipakai kalau HP authenticator-nya hilang/ganti.

   ![Recovery Codes](screenshots/11-recovery-codes.png)

3. Login berikutnya cuma diminta kode 6 digit dari authenticator app (gak perlu scan QR lagi), baru lanjut ke Dashboard.

   ![Dashboard tampilan Admin](screenshots/12-dashboard-admin.png)

**Kelola User** — tambah/nonaktifkan akun, ubah role/unit kerja, force-logout akun tertentu, lihat riwayat perubahan tiap akun.

![Kelola User](screenshots/13-kelola-user.png)

**Kelola Uker, Pekerja, Kode Aset** — master data (menu ada di sidebar bagian "Administrasi").

**Approve/Reject Permintaan Edit Aset** — daftar permintaan edit yang diajukan cabang, tinggal klik centang (approve) atau silang (tolak, wajib isi alasan).

![Permintaan Edit Aset](screenshots/14-permintaan-edit.png)

**Approve/Reject Permintaan Hapus Aset** — pola sama kayak Permintaan Edit.

![Permintaan Hapus Aset](screenshots/15-permintaan-hapus.png)

**Log History** — cari & filter semua aktivitas penting di sistem (siapa ngapain, kapan).

![Log History](screenshots/16-log-history.png)

**Login History** — pantau semua percobaan login (berhasil/gagal/ditolak), per akun/tanggal, lengkap IP & perangkat.

![Login History](screenshots/17-login-history.png)

**Rekap Cabang & Struktur Organisasi** — lihat ringkasan compliance per unit kerja dalam bentuk pohon, termasuk turunannya.

![Struktur Organisasi](screenshots/18-struktur-organisasi.png)

**Import Massal** — upload data Pekerja/Petugas IT lewat Excel.

**Delete Massal (Excel)** — khusus admin, hapus banyak aset sekaligus berdasarkan daftar SN.

---

## BAGIAN 3 — Dokumentasi Teknis (Developer Guide)

### Stack Teknologi

- **Backend**: Laravel 13, PHP 8.3
- **Frontend**: Blade + Tailwind CSS (via Vite) + Alpine.js (buat interaksi ringan tanpa reload, misal modal)
- **Database**: MySQL (lokal via Laragon)
- **Testing**: Pest (SQLite in-memory buat test, biar cepat & terisolasi dari data lokal)
- **Export**: PhpSpreadsheet (Excel), barryvdh/laravel-dompdf (PDF)
- **QR Code**: endroid/qr-code
- **MFA/TOTP**: pragmarx/google2fa

### Struktur Penting

```
app/
  Models/          -- Aset, HealthCheckForm, User, Uker, Pekerja, dst
  Http/Controllers/
  Http/Middleware/ -- termasuk EnsureAdminTwoFactor (gerbang MFA)
  Policies/        -- AsetPolicy, HealthCheckFormPolicy, UserPolicy (otorisasi server-side)
  Services/        -- TwoFactorAuthService, dll
  Console/Commands/-- command reminder otomatis, mfa:reset
routes/
  web.php          -- route utama aplikasi (di-scope role:admin buat halaman khusus admin)
  auth.php         -- login, logout, MFA setup/challenge
tests/Feature/     -- test per fitur, pola: 1 file per controller/model utama
```

### Pola-Pola Kunci yang Dipakai Berulang

**1. RBAC berbasis pohon unit kerja**
```php
Uker::descendantKodes(int $ukerKode): array
```
Dipanggil di query manapun yang perlu di-scope ke unit kerja + turunannya. Admin selalu bypass pengecekan ini (`$user->role === 'admin'`).

**2. Approval workflow (request → approve/reject)**

Polanya konsisten dipakai di 2 tempat (`AsetEditRequest`, `AsetHapusRequest`):
- Kolom `status` (Menunggu / Disetujui / Ditolak)
- `requested_by`, `handled_by`, `handled_at`
- `sudah_dipakai` (boolean) — flag ini yang bikin izin cuma bisa dipakai SEKALI, begitu aksinya kejalanin langsung di-flip `true`

Dicek lewat method di model, misal:
```php
$aset->bisaDihapus($user): bool
$aset->permintaanHapusMenunggu(): ?AsetHapusRequest
```

Dan otorisasi sebenarnya dijaga di Policy (`AsetPolicy::delete()`), bukan cuma dicek di Controller/Blade — jadi gak bisa ditembus lewat request langsung ke endpoint.

**3. Audit log insert-only**

Model log (`LoginLog`, `UserPerubahanLog`, dst) sengaja:
```php
public $timestamps = false;

protected static function booted(): void
{
    static::creating(fn ($m) => $m->created_at ??= now());
}
```
Gak ada method update/delete yang dipakai di aplikasi buat baris log — cuma insert. Tujuannya biar riwayat bisa dipercaya sebagai bukti audit.

**4. MFA (Two-Factor Authentication)**

- `App\Services\TwoFactorAuthService` — bungkus library `pragmarx/google2fa` (generate secret, verifikasi kode, generate QR pakai `endroid/qr-code` yang sama dipakai buat QR aset).
- `App\Http\Middleware\EnsureAdminTwoFactor` — **middleware global** (didaftarkan di `bootstrap/app.php`, bukan per-route), jadi gak ada halaman admin yang bisa kelewat digerbang.
- Alur: admin login → kalau belum setup MFA → redirect `two-factor.setup` (tampilin QR, minta 1 kode buat konfirmasi) → kalau udah aktif tapi sesi ini belum verifikasi → redirect `two-factor.challenge` (minta kode tiap sesi baru).
- Recovery codes disimpan **terenkripsi** di kolom `two_factor_recovery_codes` (Laravel encrypted cast), ditampilkan cuma SEKALI pas setup selesai.
- Command darurat `php artisan mfa:reset {pn}` — satu-satunya jalan reset MFA kalau HP & recovery codes sama-sama hilang, sengaja gak ada tombol reset di UI (harus akses server/SSH).

### Menjalankan Test

```bash
php artisan test          # semua test
php artisan test --filter=NamaTest   # test tertentu
./vendor/bin/pint --dirty # format kode sebelum commit
```

---

## BAGIAN 4 — Panduan Persiapan Deploy ke Hosting

### Checklist Sebelum Deploy

File [`.env.production.example`](../.env.production.example) di project ini isinya checklist konfigurasi wajib:

- `APP_ENV=production`, `APP_DEBUG=false` (WAJIB — kalau `true`, error di aplikasi bakal nunjukin detail sensitif ke siapa aja)
- `APP_KEY` baru (generate khusus buat server, jangan dipakai bareng yang lokal)
- `SESSION_SECURE_COOKIE=true` (begitu HTTPS aktif)
- `LOG_LEVEL=error` (bukan `debug` kayak lokal)
- Kredensial database production (bukan yang lokal)

### Pertimbangan Pemilihan Hosting

Dua opsi yang sempat dievaluasi:

| | Hostinger (paket "Unlimited") | idCloudHost (paket "Basic Pro") |
|---|---|---|
| SSH/Composer/Git | Kemungkinan besar ADA (SSH gak tersedia cuma di tier paling bawah "Single Web") | **TIDAK ADA** (baru tersedia mulai tier "Business Pro") |
| Cron Job | Ada | Ada (tersedia di semua tier) |
| Cara deploy | Bisa `git pull` + `composer install` langsung di server | Harus build lokal, upload manual |
| Update kode berikutnya | Gampang (tinggal pull ulang) | Manual tiap kali (zip, upload ulang) |

**Kalau hosting yang dipakai TIDAK ada SSH** (misal idCloudHost Basic Pro), langkah deploy-nya:

1. **Build semua di laptop dulu**:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm run build
   php artisan key:generate --show   # catat hasilnya, jangan run di server
   ```
2. **Susun ulang struktur folder** — source code (app/, vendor/, dst) ditaruh DI LUAR `public_html`, isi folder `public/` Laravel disalin KE DALAM `public_html`, dan `index.php` di-edit path require-nya biar nemu vendor yang di luar.
3. **Upload** lewat File Manager cPanel / FTP (zip project, ukurannya wajar besar karena vendor ikut).
4. **Bikin database** lewat menu "MySQL Databases" di cPanel.
5. **Jalankan migration TANPA SSH** — trik Cron Job SEKALI JALAN: bikin cron job isinya `php /path/ke/artisan migrate --force`, tunggu jalan sekali, lalu **hapus cron job-nya**.
6. **`storage:link` juga gak bisa langsung** — pakai script kecil sekali-pakai yang manggil `symlink()` PHP native lewat browser, lalu dihapus filenya.
7. **Reminder otomatis** (command yang udah dibikin) — cron job PERMANEN: `php artisan schedule:run` tiap menit.
8. **SSL** — aktifkan AutoSSL/Let's Encrypt gratis di menu SSL/TLS cPanel.

**Kalau hosting ADA SSH** (misal Hostinger di tier yang mendukung), prosesnya jauh lebih simpel — tinggal `git clone`/`git pull`, `composer install`, `php artisan migrate --force`, `php artisan storage:link` langsung di server lewat terminal SSH. Catatan: Hostinger defaultnya `composer` mengarah ke versi 1 — perlu pakai `composer2` secara eksplisit biar kompatibel sama `composer.json` yang butuh PHP 8.3+.

### Setelah Live

- Backup database berkala (jangan andalkan cuma soft-delete aplikasi).
- Testing penuh di domain production sebelum data asli/karyawan beneran dimasukkan.
- Kalau data yang dipakai data karyawan asli, pastikan ada persetujuan/pengarahan resmi dari pihak yang berwenang (supervisor/IT Security) sebelum go-live — bukan keputusan teknis semata.
