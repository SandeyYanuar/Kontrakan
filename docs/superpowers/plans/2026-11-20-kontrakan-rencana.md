# Rencana Pengerjaan Aplikasi Kontrakan

Acuan: [desain yang disetujui](../specs/2026-11-20-kontrakan-design.md). Mockup ada di `.superpowers/brainstorm/24288-1795105351/content/`.

Stack: Laravel 13, PHP 8.4, SQLite, Blade + Livewire, Tailwind CSS v4, Pest untuk tes, dompdf untuk kuitansi.

Cara kerja di setiap langkah:
1. Tulis tes dulu (Pest), lalu kode, lalu jalankan `php artisan test`.
2. Untuk halaman, buka di browser dan bandingkan dengan mockup, termasuk lebar HP.
3. Lakukan pemeriksaan visual: jarak konsisten, warna abu-abu tidak berlebihan, tombol rata.
4. Langkah dianggap selesai kalau semua tes hijau dan Pangeran sudah melihat hasilnya.

---

## Tahap 1

### Langkah 0: Persiapan mesin
- Pasang PHP 8.4, Composer, dan installer Laravel. Pilihannya:
  - Laravel Herd (aplikasi gratis untuk Mac), atau
  - skrip resmi `php.new`.
- Pastikan perintah `php -v`, `composer -V`, dan `laravel --version` berjalan.
- Node sudah ada lewat nvm (v26). Pastikan `npm` bisa dipanggil dari shell.

### Langkah 1: Kerangka proyek
- `laravel new` di folder `kontrakan/app`, dengan pilihan:
  - SQLite
  - Pest
  - tanpa starter kit, karena login dibuat sendiri dengan nomor HP
- Pasang Livewire.
- Daftarkan token warna dan font Plus Jakarta Sans di `resources/css/app.css` (`@theme`).
- Atur zona waktu `Asia/Jakarta`, bahasa `id`, dan format Rupiah/tanggal lewat helper.
- Buat layout dasar:
  - **admin:** menu di strip atap
  - **penyewa:** HP lebih dulu
- Tambahkan `.superpowers/` ke `.gitignore`. Mulai git repo kalau Pangeran mau.
- **Selesai kalau:** halaman kosong tampil dengan font dan warna yang benar, dan `php artisan test` hijau.

### Langkah 2: Akun, peran, dan login
- **Migrasi `users`:**
  - `phone` unik, format `62xxx`
  - `email` opsional
  - `role`: `super_admin`, `admin`, atau `tenant`
  - `is_active`, `kode_penyewa`, `phone_darurat`
- Helper normalisasi nomor (`08xxx`, `+62xxx`, `62xxx` menjadi `62xxx`), lengkap dengan tes.
- **Login dengan nomor HP dan password:**
  - pilihan "Ingat saya" (30 hari); tanpa itu, login berakhir saat browser ditutup
  - batas percobaan login
  - akun nonaktif ditolak
- **Lupa password:** link WhatsApp ke nomor admin dengan pesan terisi.
- **Middleware atau Gate per peran,** mengikuti tabel hak akses di desain bagian 2.
- **Seeder:** satu super admin dengan nomor yang ditanyakan ke Pangeran.
- **Tes:**
  - login dengan berbagai format nomor
  - akun nonaktif ditolak
  - penyewa tidak bisa membuka halaman admin
  - admin tidak bisa membuka menu Admin dan Pengaturan

### Langkah 3: Riwayat aktivitas (fondasi)
- Model `ActivityLog` yang **hanya bisa insert**: update dan delete melempar exception, dan tidak ada route untuk mengubahnya.
- Helper `activity('aksi', $subject, $data)` yang dipakai di semua langkah berikutnya.
- **Tes:** log tidak bisa diubah atau dihapus lewat model.

### Langkah 4: Pengaturan, unit, dan admin (super admin)
- **Pengaturan:**
  - rekening tujuan
  - nomor WhatsApp admin
  - nama kontrakan
  - tanggal mulai bayar (1) dan jatuh tempo (5)
- **Unit:** kode, harga sewa, keterangan. Hanya super admin yang bisa menambah atau mengubah. Perubahan harga dicatat di log.
- **Admin:** tambah, nonaktifkan, dan reset password. Semuanya dicatat di log.
- **Tes:** hak akses per peran, dan setiap aksi menghasilkan log.

### Langkah 5: Penyewa dan kontrak sewa
- Kode `KT-0001` dibuat berurutan dan tidak pernah dipakai ulang. Tes tetap lulus walaupun ada penyewa yang dinonaktifkan.
- **Tambah penyewa (admin):**
  - nama, nomor HP, email opsional
  - unit kosong dan tanggal mulai
  - harga sewa diambil dari harga unit
  - password awal
- **Ubah nomor HP dan reset password penyewa (admin).**
- **Akhiri sewa, pindah unit, dan nonaktifkan (super admin):**
  - pindah unit berarti kontrak baru dengan kode penyewa yang sama
- Tabel Penyewa memakai ikon status KTP (diisi di Langkah 8).
- **Tes:** keunikan kode, pindah unit tetap memakai kode yang sama, dan admin tidak bisa mengubah harga.

### Langkah 6: Tagihan
- Migrasi `invoices` dan `invoice_items`.
- **Command `tagihan:buat`:**
  - membuat tagihan setiap kontrak aktif 14 hari sebelum tanggal 5 bulan berikutnya
  - aman dijalankan berulang (tidak membuat tagihan ganda)
- Command dijadwalkan harian, dan juga dipanggil saat admin membuka halaman Tagihan (cadangan untuk lokal).
- Admin bisa membuat tagihan bulan berikutnya secara manual.
- **Label waktu dihitung saat ditampilkan:**
  - "jatuh tempo dekat": tanggal 1–5, belum lunas
  - "telat": mulai tanggal 6, belum lunas
- **Tes:**
  - batas tanggal 21, 1, 5, dan 6
  - Februari
  - tidak ada tagihan ganda
  - perubahan harga hanya berlaku untuk tagihan yang belum dibuat

### Langkah 7: Halaman Tagihan admin (mockup `admin-tabel-v5.html`)
- **Kepala halaman:** pilihan bulan, ringkasan "Rp X masuk dari Rp Y" dengan bar empat warna, dan tunggakan disebut terpisah.
- **Deretan pintu:**
  - komponen Blade `x-pintu` dan `x-fasad`
  - warna pintu mengikuti tagihan paling mendesak
  - lampu menyala untuk unit terisi
  - klik pintu menyorot baris penyewanya
- **Tabel:**
  - kelompok "Perlu ditindak" (termasuk tunggakan dengan nama bulannya) dan "Sudah lunas"
  - kolom tindakan rata kiri
  - centang hijau untuk yang lunas
- **Tombol "Ingatkan via WhatsApp":**
  - membuat link `wa.me` dengan pesan terisi yang memuat kode, unit, rincian bulan, total, dan permintaan menulis kode di berita transfer
  - setiap klik dicatat di log lewat request kecil, lalu WhatsApp dibuka
- Tampilan kartu di HP.
- **Tes:**
  - isi pesan WhatsApp
  - urutan kelompok
  - tunggakan bulan lalu tetap muncul
  - klik pengingat tercatat

### Langkah 8: KTP penyewa
- **Migrasi `tenant_identities`:** NIK dengan cast `encrypted`, foto di disk privat.
- **Penyewa:** form di Profil (foto, NIK 16 digit, nama sesuai KTP), dan banner "Lengkapi data KTP" di beranda.
- **Admin:**
  - halaman detail penyewa dengan foto KTP
  - tombol "Tandai sah" dan "Minta upload ulang" dengan alasan
  - setiap kali foto dibuka, tercatat di log
- NIK tersamar `320••••••••••004` di tabel.
- KTP yang sudah sah tidak bisa diganti oleh penyewa.
- Super admin bisa menghapus foto KTP penyewa yang sudah keluar, dan penghapusannya tercatat.
- **Tes:**
  - penyewa lain tidak bisa membuka foto
  - setiap kali foto dibuka, tercatat di log
  - fungsi penyamaran NIK
  - validasi 16 digit

### Langkah 9: Sisi penyewa (mockup `penyewa-beranda.html`)
- **Beranda:**
  - kepala halaman dengan pintu unit sendiri, nama, kode, dan masa sewa
  - daftar tagihan yang belum lunas (yang terlama di atas), masing-masing dengan kartu status: belum bayar, menunggu, lunas, atau ditolak dengan alasan dan tombol upload ulang
- Rekening tujuan dengan tombol Salin, dan pengingat menulis kode di berita transfer.
- Tombol cepat: Hubungi pengelola (WhatsApp) dan Riwayat bayar.
- **Upload bukti:**
  - nominal, tanggal transfer, nama pengirim (opsional)
  - file JPG/PNG/PDF maksimal 2 MB, dari kamera atau galeri
  - satu bukti untuk satu tagihan; tagihan menjadi `menunggu`; tercatat di log
- Profil: ganti password, nomor darurat, dan Logout merah dengan konfirmasi.
- **Tes:**
  - penyewa hanya melihat miliknya
  - validasi file
  - tidak bisa upload ke tagihan yang sudah lunas atau sedang menunggu

### Langkah 10: Pop-up cek bukti transfer (mockup `admin-tabel-v5.html`)
- **Komponen Livewire pop-up:**
  - gambar bukti dari route privat
  - nominal tagihan dibandingkan dengan isian penyewa
  - catatan penyewa
- **Setujui:** tagihan menjadi lunas, pesan konfirmasi singkat, tercatat di log.
- **Tolak:** alasan wajib diisi; tagihan menjadi `ditolak`; alasan tampil di beranda penyewa; tercatat di log.
- **Batal, Esc, atau klik di luar pop-up:** tidak ada perubahan.
- **Super admin, Batalkan persetujuan:**
  - pembayaran ditandai `dibatalkan` dan tagihan kembali `belum_bayar`
  - tercatat di log
- **Tes:**
  - setiap jalur
  - alasan wajib diisi
  - admin biasa tidak bisa membatalkan persetujuan

### Langkah 11: Halaman Riwayat aktivitas (mockup `log-aktivitas-v2.html`)
- Dikelompokkan per hari.
- Filter: cari nama, kode, atau unit; jenis aktivitas; admin; bulan.
- Tombol "Lihat bukti" membuka pop-up bukti dalam mode baca saja.
- **Tes:** filter berjalan, dan penyewa tidak bisa membuka halaman ini.

### Langkah 12: Kuitansi PDF
- Pasang dompdf. Template kuitansi memakai identitas visual: nama kontrakan, kode penyewa, unit, periode, nominal, tanggal lunas, dan admin yang menyetujui.
- Hanya tersedia untuk tagihan yang sudah lunas, dan hanya untuk pemiliknya atau admin.
- **Tes:** hak akses, dan tidak tersedia untuk tagihan yang belum lunas.

### Langkah 13: Data contoh dan uji menyeluruh
- **Seeder demo:**
  - 8 unit dan 6 penyewa
  - riwayat 3 bulan, termasuk tunggakan, penolakan, dan KTP dalam berbagai status
- Uji manual seluruh alur bersama Pangeran, di laptop dan HP (lewat jaringan Wi-Fi yang sama).
- Lakukan pemeriksaan visual di setiap halaman.
- **Selesai kalau:** Pangeran bisa memakai Tahap 1 untuk satu siklus tagihan percobaan.

---

## Tahap 2 (setelah Tahap 1 dipakai)

### Langkah 14: Laporan kerusakan
- **Migrasi:** `maintenance_tickets` dan `ticket_media`, dengan foto/video di disk privat dan batas ukuran video yang ditentukan saat itu.
- **Penyewa:**
  - form berisi kategori, deskripsi, media, dan preferensi jadwal
  - pelacakan status: Diterima, Dijadwalkan, Dikerjakan, Selesai
  - tombol "Perbaikan sudah beres"
- **Admin:**
  - daftar per status
  - ubah status dan isi jadwal kunjungan
  - tombol WhatsApp ke penyewa
  - semua perubahan tercatat di log
- Tambahkan tombol cepat "Lapor kerusakan" di beranda penyewa.

### Langkah 15: Pengumuman
- Admin menulis judul, isi, dan tanggal tampil mulai/sampai.
- Pengumuman tampil di beranda penyewa selama periode itu.
- Tercatat di log.

### Langkah 16: Uji menyeluruh Tahap 2
- Perbarui seeder demo, lalu uji bersama Pangeran.

---

## Nanti, di luar rencana ini
- Hosting: ganti `.env` ke MySQL hosting, pastikan PHP 8.3+, dan atur cron `schedule:run`.
- Tagihan listrik dan air: tambah baris `invoice_items` jenis `listrik` dan `air`.
