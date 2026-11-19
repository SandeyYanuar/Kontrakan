# Desain Aplikasi Web Kontrakan

Tanggal: 20 November 2026
Status: disetujui Pangeran, 20 November 2026

## 1. Maksud dan alasan

Pemilik (selanjutnya "Pangeran") punya beberapa unit kontrakan dan ingin satu web untuk mengelola tagihan sewa bulanan. Selama ini yang dibutuhkan adalah tabel yang menunjukkan siapa sudah bayar dan siapa belum, lalu menghubungi penyewa yang belum bayar lewat WhatsApp. Web ini menggantikan cara manual itu:

- Penyewa melihat tagihannya sendiri dan mengupload bukti transfer.
- Admin mengecek bukti, menyetujui atau menolak, dan mengingatkan penyewa lewat WhatsApp dengan satu klik.
- Semua tindakan admin tercatat, sehingga ketahuan siapa yang menyetujui, menolak, atau mengingatkan.

Aplikasi ini untuk dipakai sendiri, bukan produk untuk dijual.

## 2. Pengguna dan peran

| Peran | Siapa | Login dengan |
|---|---|---|
| Super admin | Pangeran (satu akun, dibuat lewat seeder) | nomor HP WhatsApp + password |
| Admin | Orang yang ditunjuk Pangeran | nomor HP WhatsApp + password |
| Penyewa | Penghuni unit | nomor HP WhatsApp + password |

Semua akun login dengan **nomor HP yang terdaftar di WhatsApp**. Nomor yang sama dipakai untuk tombol pengingat dan tombol hubungi pengelola. **Email opsional**, hanya sebagai data kontak, dan tidak dipakai untuk login.

Hak akses (disetujui Pangeran):

| Bisa melakukan | Super admin | Admin | Penyewa |
|---|:-:|:-:|:-:|
| Lihat tagihan dan status semua unit | ✓ | ✓ | hanya miliknya |
| Setujui atau tolak bukti transfer | ✓ | ✓ | – |
| Buka pengingat WhatsApp | ✓ | ✓ | – |
| Tambah penyewa, ubah nomor HP penyewa | ✓ | ✓ | – |
| Reset password penyewa | ✓ | ✓ | – |
| Lihat riwayat aktivitas | ✓ | ✓ | – |
| Kelola laporan kerusakan dan pengumuman (Tahap 2) | ✓ | ✓ | – |
| Ubah harga sewa unit, tambah atau ubah unit | ✓ | – | – |
| Nonaktifkan penyewa yang keluar | ✓ | – | – |
| Batalkan persetujuan yang salah | ✓ | – | – |
| Tambah, nonaktifkan, reset password admin | ✓ | – | – |
| Lihat dan tandai sah KTP penyewa | ✓ | ✓ | hanya miliknya |
| Hapus foto KTP penyewa yang sudah keluar | ✓ | – | – |
| Upload bukti transfer, upload KTP, lapor kerusakan | – | – | ✓ |

Akun tidak pernah dihapus, hanya dinonaktifkan, supaya nama di riwayat aktivitas tetap utuh.

## 3. Tujuan, bukan tujuan, anti-tujuan

**Tujuan**
- Sekali buka, admin tahu unit mana yang lunas, menunggu dicek, jatuh tempo dekat, telat, atau kosong.
- Mengingatkan penyewa cukup satu klik yang membuka WhatsApp dengan pesan sudah terisi.
- Bukti transfer tersimpan rapi dan bisa dicek di pop-up, lalu disetujui atau ditolak dengan alasan.
- Ada jejak lengkap siapa melakukan apa.
- Penyewa bisa melihat tagihan, membayar (upload bukti), dan mengunduh kuitansi dari HP.

**Bukan tujuan (sengaja tidak dibuat)**
- Payment gateway atau pembayaran online. Pembayaran tetap transfer manual.
- Pengiriman WhatsApp, SMS, atau OTP otomatis. Semua yang berbayar dihindari.
- Tagihan listrik dan air per meteran. Ditunda, di luar harga sewa (lihat bagian 9).
- Pendaftaran mandiri penyewa. Akun penyewa dibuat oleh admin.
- Biaya booking penyewa baru. Penyewa baru membayar penuh satu bulan di muka di luar aplikasi (keputusan Pangeran, 20 November 2026).
- Hosting. Untuk sekarang aplikasi hanya dijalankan di komputer Pangeran.

**Anti-tujuan (gagal walaupun secara teknis jalan)**
- Ada biaya bulanan yang harus dibayar Pangeran untuk menjalankannya sekarang.
- Riwayat aktivitas bisa diubah atau dihapus, atau ada tindakan admin yang tidak tercatat.
- Penyewa bisa melihat tagihan, bukti transfer, atau data penyewa lain.
- Tampilan terasa seperti template admin generik. Pangeran sudah menyetujui gaya visual tertentu (bagian 7).
- Di HP, tombol utama susah ditekan atau tabel harus digeser ke samping.

## 4. Batasan

- **Tanpa biaya.** Tidak ada layanan berbayar: tanpa WhatsApp API, SMS, OTP, atau database berbayar.
- **Lokal dulu.** Dijalankan di MacBook Pangeran (Apple Silicon, macOS). Saat ini PHP dan Composer belum terpasang. SQLite sudah ada bawaan macOS.
- **Siap dipindah ke hosting nanti** tanpa menulis ulang: cukup ganti koneksi database di `.env` ke MySQL hosting.
- Bahasa antarmuka: Bahasa Indonesia. Format uang `Rp 750.000`, tanggal `5 Desember 2026`, zona waktu `Asia/Jakarta`.

## 5. Pendekatan yang dipertimbangkan

| Pendekatan | Kelebihan | Kekurangan | Hasil |
|---|---|---|---|
| **A. Laravel + database lokal (SQLite)** | Login, peran, upload, PDF, penjadwal sudah bawaan. Satu tempat. Gratis di lokal. Nanti pindah ke hosting murah dengan MySQL bawaan hosting. | Perlu memasang PHP di Mac (pakai Laravel Herd, gratis). | **Dipilih Pangeran** |
| B. Next.js + Supabase gratis | Rp 0 di awal | Tersebar di beberapa layanan. Paket gratis berhenti sendiri kalau seminggu sepi. Kuota penyimpanan foto terbatas. | Tidak dipilih |
| C. Tanpa kode (Glide/AppSheet) | Paling cepat jadi | Tidak bisa membuat tampilan deretan pintu, pembagian super admin dan admin, atau log yang tidak bisa diubah. | Dicoret |

Pangeran memilih A dengan syarat tidak memakai database berbayar dan tidak di-hosting dulu. Database yang dipakai adalah **SQLite**: gratis, berupa satu file di folder proyek, dan bawaan Laravel. Catatan: MySQL sebenarnya juga gratis. Biaya yang sempat disebut sebelumnya adalah biaya hosting, bukan database.

## 6. Keputusan yang sudah dibuat bersama Pangeran

### 6.1 Tabel tagihan admin (halaman utama admin)
- Judul bulan berjalan, ringkasan "Rp X masuk dari Rp Y" dengan bar empat warna.
- **Deretan pintu:** setiap unit digambar sebagai pintu di bawah atap. Warna pintu sesuai status, lampu di atas pintu menyala kalau unit terisi, dan unit kosong bergaris putus-putus. Mengklik pintu menyorot baris penyewanya.
- Tabel dikelompokkan menjadi "Perlu ditindak" (telat, jatuh tempo dekat, menunggu dicek) di atas dan "Sudah lunas" di bawah. Kolom: penyewa (kotak nomor unit berwarna, nama, kode, nomor HP), tagihan, status, tindakan.
- Kolom tindakan rata kiri, sejajar dengan judulnya. Isinya:
  - Belum bayar atau telat: tombol hijau **"Ingatkan via WhatsApp"**.
  - Menunggu dicek: tombol **"Cek bukti transfer"**.
  - Lunas: **ikon centang hijau** saja.
- Di HP, tabel berubah jadi kartu dengan tombol selebar kartu, dan deretan pintu mengecil jadi strip warna.

### 6.2 Pengingat WhatsApp
- Tombol membuka `https://wa.me/62xxxx?text=...` ke nomor penyewa, dengan pesan sudah terisi. **Tidak terkirim otomatis**: admin sendiri yang menekan kirim di WhatsApp.
- Isi pesan: nama dan kode penyewa, unit, bulan, nominal, tanggal jatuh tempo, permintaan menulis kode di berita transfer, dan link ke web.
- Setiap klik dicatat di riwayat aktivitas sebagai "membuka pengingat WhatsApp". Sistem tidak bisa tahu apakah pesan benar-benar terkirim.

### 6.3 Pop-up cek bukti transfer
- Kiri: gambar bukti (bisa dibuka ukuran penuh).
- Kanan: nominal tagihan, tanggal transfer, nominal dan nama pengirim dari isian penyewa, serta catatan penyewa.
- Tombol **Setujui pembayaran**, **Tolak**, **Batal**. Klik di luar pop-up atau Esc sama dengan Batal.
- Tolak: muncul kolom alasan, lalu tombol berubah menjadi "Kirim penolakan". Alasan ditampilkan ke penyewa.
- Setelah setuju atau tolak: status di tabel berubah dan muncul pesan konfirmasi singkat.

### 6.4 Riwayat aktivitas (menu terpisah)
- Menu admin: Tagihan, Penyewa, Unit, Riwayat aktivitas, Admin (khusus super admin). Tahap 2 menambah Laporan kerusakan dan Pengumuman.
- Yang dicatat, masing-masing dengan pelaku, waktu, unit, kode penyewa, dan nominal bila ada:
  - persetujuan dan penolakan (dengan alasan)
  - pembatalan persetujuan
  - pembukaan pengingat WhatsApp
  - upload bukti oleh penyewa
  - reset password
  - tambah, ubah, atau nonaktifkan penyewa, unit, dan admin
  - perubahan harga sewa
  - di Tahap 2: perubahan status laporan kerusakan dan pengumuman yang diterbitkan
- Dikelompokkan per hari. Filter: cari nama, kode, atau unit; jenis aktivitas; admin; bulan.
- **Tidak bisa diubah atau dihapus** oleh siapa pun lewat aplikasi, termasuk super admin.

### 6.5 Kode unik penyewa
- Format `KT-0001`, berurutan, melekat ke orangnya (bukan unitnya), dan tidak dipakai ulang.
- Tampil di tabel, kartu HP, pop-up bukti, log, beranda penyewa, dan pesan WhatsApp. Penyewa diminta menulisnya di berita transfer.

### 6.6 Sisi penyewa (HP lebih dulu)
- **Login:** nomor HP WhatsApp + password, dengan pilihan "Ingat saya". Kalau dicentang, login bertahan 30 hari. Kalau tidak, login berakhir saat browser ditutup.
- **Lupa password:** membuka WhatsApp ke nomor admin dengan pesan terisi ("Saya KT-0007 unit A3, minta reset password"). Admin mereset dari web.
- **Beranda:**
  - Kepala halaman berisi pintu unit miliknya (warna sesuai status), nama, kode, unit, dan masa sewa.
  - Kartu tagihan bulan ini dalam empat keadaan:
    - Belum bayar: kuning tua, dengan tombol "Upload bukti transfer".
    - Menunggu dicek: biru.
    - Lunas: hijau.
    - Ditolak: merah bata, dengan alasan dan tombol "Upload ulang".
  - Rekening tujuan dengan tombol Salin, dan pengingat menulis kode di berita transfer.
  - Tombol cepat: Hubungi pengelola (link WhatsApp ke admin) dan Riwayat bayar.
- **Upload bukti:** nominal, tanggal transfer, nama pemilik rekening pengirim (opsional), dan file (JPG/PNG/PDF, maksimal 2 MB, lewat kamera atau galeri).
- **Riwayat bayar:** daftar per bulan, dengan **Kuitansi PDF** untuk yang lunas.
- **Profil:** ganti password, nomor HP darurat, dan Logout merah dengan konfirmasi.

### 6.6a Data KTP penyewa (Tahap 1)
Permintaan Pangeran: penyewa mengupload KTP supaya ada data identitas yang sah.
- **Penyewa mengisi:** foto KTP (JPG/PNG, maksimal 2 MB, dari kamera atau galeri), NIK 16 digit, dan nama sesuai KTP. Diisi dari menu Profil.
- **Saat KTP belum diupload,** beranda penyewa menampilkan banner "Lengkapi data KTP" di atas kartu tagihan. Banner ini tidak menghalangi upload bukti transfer, supaya pembayaran tidak tertunda.
- **Admin memeriksa** foto KTP di halaman detail penyewa, lalu memilih **Tandai sah** atau **Minta upload ulang** (dengan alasan). Status KTP: `belum_ada`, `menunggu`, `sah`, `ditolak`.
- Di tabel Penyewa, admin melihat ikon status KTP untuk setiap penyewa.
- **Setelah KTP berstatus sah,** penyewa tidak bisa menggantinya sendiri. Penggantian dilakukan lewat admin.
- **Perlindungan data:**
  - KTP adalah data pribadi (UU 27/2022 tentang Pelindungan Data Pribadi). Foto KTP disimpan di storage privat.
  - Hanya super admin, admin, dan penyewa yang bersangkutan yang bisa membukanya.
  - Setiap kali admin membuka foto KTP, itu tercatat di riwayat aktivitas.
  - NIK di tabel ditampilkan tersamar: 3 digit depan dan 3 digit belakang terlihat, sisanya disembunyikan (`320••••••••••004`).
- **Penyewa yang sudah keluar:** super admin bisa menghapus foto KTP-nya. Penghapusan itu tercatat di log, tapi catatan log sendiri tidak pernah dihapus.

### 6.7 Tahap 2: laporan kerusakan dan pengumuman
Mengikuti alur dari Pangeran.
- **Penyewa:**
  - Lapor kerusakan: kategori (Listrik, Air/Pipa, AC, Furnitur, Kebersihan, Lainnya), deskripsi, foto atau video pendek, dan preferensi hari dan jam kunjungan.
  - Status: Diterima, Dijadwalkan, Dikerjakan, Selesai.
  - Penyewa mengonfirmasi bahwa perbaikan sudah beres.
- **Admin:** daftar laporan per status, mengubah status, dan mengisi jadwal kunjungan.
- **Pengumuman:** admin menulis judul dan isi, lalu pengumuman tampil di beranda penyewa sampai tanggal berakhir yang diatur admin.

## 7. Sistem visual (disetujui lewat mockup)

- **CSS:** Tailwind CSS v4, bawaan Laravel 13 lewat Vite. Token warna di tabel bawah didaftarkan di blok `@theme` pada `resources/css/app.css`, sehingga muncul sebagai kelas seperti `bg-lunas` atau `text-muted`. Motif pintu, atap, dan teras dibuat sebagai komponen Blade dengan CSS sendiri di file yang sama. Tidak memakai Bootstrap atau kit UI siap pakai.
- **Huruf:** Plus Jakarta Sans (satu keluarga), angka tabular untuk nominal.
- **Warna:**

  | Token | Hex | Dipakai untuk |
  |---|---|---|
  | plaster | `#ECEFF2` | latar halaman |
  | paper | `#FFFFFF` | latar tabel dan kartu |
  | ink | `#18222F` | teks utama |
  | muted | `#5A6573` | teks pendukung |
  | line | `#DCE1E7` | garis |
  | roof | `#3B4656` | atap dan menu |
  | lunas | `#2E6A4C` | status lunas |
  | dicek | `#2C4F86` | status menunggu dicek |
  | dekat | `#B07415` | jatuh tempo dekat |
  | telat / ditolak | `#A8261C` | telat atau ditolak |
  | WhatsApp | `#1DA851` | hanya tombol WhatsApp |

- **Motif utama:** atap, deretan pintu, dan teras. Menu admin diletakkan di strip atap. Selain motif ini, tampilan dibuat tenang.
- Mockup acuan ada di `.superpowers/brainstorm/24288-1795105351/content/`:
  - `admin-tabel-v5.html` (tabel admin)
  - `log-aktivitas-v2.html` (riwayat aktivitas)
  - `penyewa-beranda.html` (sisi penyewa)
- **Kualitas minimum:** responsif sampai lebar HP, fokus keyboard terlihat, menghormati pengaturan "kurangi gerakan", dan kontras teks cukup.
- Setelah setiap halaman dibangun, lakukan pemeriksaan visual: jarak konsisten, jumlah warna abu-abu tidak berlebihan, dan tombol rata.

## 8. Aturan tagihan

- **Siklus bulanan, periode bayar tanggal 27 bulan sebelumnya sampai tanggal 5** (diubah Pangeran, 20 November 2026). Contohnya, sewa Desember dibayar antara 27 November dan 5 Desember. Jatuh tempo adalah **tanggal 5** untuk semua kontrak, dan tidak diatur per penyewa. Kedua tanggal disimpan di Pengaturan. Kalau tanggal mulai lebih besar dari tanggal jatuh tempo, tanggal mulai dihitung di bulan sebelumnya.
- **Tagihan rutin dimulai bulan setelah bulan mulai sewa.** Bulan saat penyewa masuk sudah dibayar lewat booking di luar aplikasi. Contohnya, penyewa yang masuk 10 Desember mendapat tagihan pertama untuk Januari, dibayar 27 Desember sampai 5 Januari.
- **Tagihan dibuat otomatis 14 hari sebelum jatuh tempo** oleh penjadwal Laravel, untuk setiap kontrak aktif. Dengan begitu penyewa bisa membayar lebih awal. Admin juga bisa membuat tagihan bulan berikutnya secara manual, misalnya untuk penyewa yang mau membayar jauh di muka. Di lokal, penjadwal dijalankan dengan `php artisan schedule:work`, atau tagihan yang belum ada dibuat saat aplikasi dibuka.
- **Satu bukti transfer untuk satu tagihan.** Membayar beberapa bulan sekaligus tidak didukung: penyewa mengupload bukti untuk setiap bulan.
- **Nominal harus pas.** Kalau kurang, admin menolak dengan alasan, lalu penyewa mentransfer kekurangannya dan mengupload ulang. Tidak ada cicilan atau saldo. Kalau penyewa kelebihan bayar, admin tetap menyetujui dan menyelesaikan kelebihannya di luar aplikasi.
- **Tunggakan tetap tampil sampai lunas.**
  - Tagihan bulan sebelumnya yang belum lunas muncul di kelompok "Perlu ditindak", ditandai nama bulannya.
  - Warna pintu unit mengikuti tagihan yang paling mendesak.
  - Ringkasan "Rp X masuk dari Rp Y" menghitung tagihan bulan yang dipilih, dengan tunggakan disebut terpisah.
  - Admin bisa memilih bulan untuk melihat tabel bulan-bulan sebelumnya.
  - Beranda penyewa menampilkan semua tagihan yang belum lunas, yang terlama di atas, dan upload bukti dilakukan per tagihan.
  - Pesan WhatsApp menyebut total tunggakan beserta rincian bulannya.
- **Bulan berjalan** di tabel admin adalah bulan kalender hari ini. Tagihan bulan depan yang sudah dibuat lebih awal tampil di tabel bulan depan, tapi tagihan yang sudah dibayar lebih awal tetap tercatat di riwayat aktivitas.
- **Status tagihan:**
  - `belum_bayar`
  - `menunggu` (ada bukti yang belum dicek)
  - `lunas`
  - `ditolak` (bukti terakhir ditolak, penyewa perlu upload ulang)
- **Label waktu** dihitung saat ditampilkan, bukan disimpan sebagai status:
  - "Jatuh tempo dekat": selama periode bayar (tanggal 27 sampai 5) dan belum lunas.
  - "Telat": mulai tanggal 6 dan belum lunas.
- **Tahap 1:** tagihan hanya berisi biaya sewa. Struktur data memakai baris rincian (`invoice_items`) supaya listrik, air, denda, atau biaya lain bisa ditambahkan nanti.

## 9. Struktur data (garis besar)

```
users              id, name, phone (unik, nomor WhatsApp, format 62xxx), email (opsional), password, role(super_admin|admin|tenant),
                   is_active, kode_penyewa (unik, null untuk admin), phone_darurat
tenant_identities  id, user_id, nik (terenkripsi), nama_ktp, foto_path (storage privat),
                   status(belum_ada|menunggu|sah|ditolak), alasan, verified_by, verified_at
units              id, kode_unit (A1, B2…), harga_sewa, keterangan
leases             id, user_id, unit_id, tgl_mulai, tgl_selesai (null),
                   harga_sewa (salinan saat kontrak), status(aktif|selesai)
invoices           id, lease_id, periode (YYYY-MM), jatuh_tempo (date), total, status
invoice_items      id, invoice_id, jenis(sewa|listrik|air|denda|lain), keterangan, nominal
payments           id, invoice_id, file_path (storage privat), nominal_transfer, tgl_transfer,
                   nama_pengirim, catatan, status(menunggu|disetujui|ditolak|dibatalkan),
                   alasan, reviewed_by, reviewed_at
activity_logs      id, actor_id (null jika sistem), aksi, subject_type, subject_id, data (json), created_at
                   — hanya insert, tidak pernah update atau delete
settings           rekening tujuan (bank, nomor, atas nama), nomor WhatsApp admin, nama kontrakan,
                   tanggal mulai bayar (27), tanggal jatuh tempo (5)
Tahap 2:
maintenance_tickets id, lease_id, kategori, deskripsi, jadwal_preferensi, jadwal_kunjungan, status,
                   dikonfirmasi_at
ticket_media       id, ticket_id, file_path, jenis(foto|video)
announcements      id, judul, isi, tampil_mulai, tampil_sampai, created_by
```

## 10. Keamanan

- Password di-hash (bawaan Laravel), dengan proteksi CSRF dan batas percobaan login.
- Policy Laravel memastikan penyewa hanya bisa mengakses data dan file miliknya.
- NIK disimpan terenkripsi (cast `encrypted` Laravel).
- File bukti, foto KTP, dan foto kerusakan disimpan di storage privat dan dilayani lewat route yang dicek izinnya.
- Validasi tipe dan ukuran file.

## 11. Diserahkan ke builder

- Struktur folder, nama route, dan pembagian komponen Livewire/Blade.
- Pembuatan kuitansi PDF (misalnya dompdf) dan tata letaknya, mengikuti sistem visual.
- Data contoh (seeder) untuk mencoba: 8 unit, 6 penyewa, dan beberapa bulan riwayat.
- Cara membuat tagihan otomatis di lokal (penjadwal atau saat aplikasi dibuka).
- Batas ukuran video kerusakan di Tahap 2.

## 12. Keputusan yang saya ambil sendiri (mohon dicek)

- Alasan penolakan **wajib diisi**.
- Label "jatuh tempo dekat" berlaku selama **tanggal 27 sampai 5**. Mulai **tanggal 6**, tagihan yang belum lunas berstatus **telat**.
- Nomor HP disimpan dalam format `62xxx`. Penyewa boleh mengetik `08xxx` saat login, dan sistem mengubahnya otomatis.
- Database lokal **SQLite**. PHP dipasang lewat **Laravel Herd** (gratis).
- **Laravel 13** (rilis stabil terbaru, Maret 2026) dengan **PHP 8.4** dari Herd. Laravel 13 minimal butuh PHP 8.3, jadi hosting nanti harus mendukung PHP 8.3 atau lebih baru. Bukan Laravel 11 seperti di PLANNING.md: Laravel 11 sudah tidak menerima perbaikan keamanan sejak Maret 2026.
- **Pengaturan** (rekening tujuan, nomor WhatsApp admin, nama kontrakan) hanya bisa diubah super admin, lewat menu Pengaturan.
- **Pembatalan persetujuan** oleh super admin mengembalikan tagihan ke `belum_bayar`. Pembayaran lama ditandai `dibatalkan`, tidak dihapus.
- **Kontrak sewa dibuat sekalian saat "Tambah penyewa".** Admin memilih unit kosong dan tanggal mulai. Harga sewa otomatis mengikuti harga unit dan tidak bisa diubah admin, supaya kontrol harga tetap di super admin.
- **Mengakhiri kontrak atau memindahkan penyewa ke unit lain** hanya bisa dilakukan super admin. Penyewa yang keluar kontraknya diakhiri dan akunnya dinonaktifkan. Penyewa yang pindah mendapat kontrak baru, dengan kode `KT-xxxx` tetap sama.
- **Perubahan harga unit oleh super admin** berlaku untuk kontrak aktif di unit itu mulai tagihan berikutnya yang belum dibuat. Tagihan yang sudah ada tidak berubah.

## 13. Urutan pengerjaan

Tahap 1 dan Tahap 2 sama-sama masuk rencana (keputusan Pangeran). Tahap 1 dibangun dan dicoba dulu sampai bisa dipakai, baru Tahap 2. Yang perlu disiapkan di Tahap 1 untuk Tahap 2:
- penyimpanan file privat yang bisa dipakai ulang
- navigasi penyewa yang menyisakan tempat untuk menu Lapor kerusakan
- jenis aktivitas di log yang bisa ditambah
