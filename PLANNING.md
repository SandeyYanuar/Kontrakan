# Rencana Pembuatan Web Aplikasi Kontrakan

## 1. Ringkasan
Ada dua jenis akun dengan satu halaman login. Setelah login, sistem mengarahkan ke tampilan sesuai perannya:
- **User (penyewa):** melihat daftar kontrakan, tagihan, upload bukti transfer, dan riwayat pembayaran.
- **Admin (pemilik):** mengelola semua penyewa, kamar, tagihan, memeriksa bukti transfer, dan mengirim pengingat jatuh tempo.

## 2. Teknologi yang Disarankan
| Bagian | Pilihan | Alasan |
|---|---|---|
| Backend + Frontend | **Laravel 13** + Blade + Livewire | Login, role, upload file, scheduler, dan notifikasi sudah tersedia bawaan |
| Tampilan | Tailwind CSS | Cepat dibuat dan enak dipakai di HP |
| Database | MySQL | Umum dipakai dan murah di hosting Indonesia |
| Penyimpanan file | Laravel Storage (lokal atau S3) | Untuk file bukti transfer |
| Notifikasi | In-app + Email, opsional WhatsApp (Fonnte/Wablas) | Penyewa lebih cepat membaca WA |
| Hosting | VPS atau shared hosting yang mendukung cron | Cron diperlukan untuk pengingat otomatis |

Alternatif jika lebih suka JavaScript: **Next.js + Supabase**. Namun untuk aplikasi CRUD seperti ini, Laravel lebih cepat jadi.

## 3. Fitur per Peran

### User (Penyewa)
1. **Login**, plus ganti password.
2. **Daftar**: unit kontrakan yang sedang disewa (nomor, harga, tanggal mulai, jatuh tempo bulanan). Bisa juga menampilkan unit yang masih kosong.
3. **Tagihan**: daftar tagihan per bulan dengan status `Belum Bayar`, `Menunggu Verifikasi`, `Lunas`, atau `Terlambat`.
4. **Pembayaran**:
   - Pilih tagihan, upload bukti transfer (JPG/PNG/PDF, maksimal 2 MB), lalu isi tanggal transfer dan catatan.
   - Status otomatis berubah menjadi `Menunggu Verifikasi`.
5. **Riwayat sudah bayar**: daftar pembayaran yang sudah disetujui admin.
6. **Notifikasi**: ikon lonceng yang menampilkan pengingat dari admin atau sistem.

### Admin
1. **Dashboard**: jumlah penyewa, unit kosong, tagihan bulan ini, total yang sudah dibayar, dan daftar yang jatuh tempo dalam waktu dekat.
2. **Kelola penyewa**: tambah, ubah, nonaktifkan, dan buatkan akun login.
3. **Kelola unit**: nomor unit, harga sewa, status terisi atau kosong.
4. **Kelola kontrak sewa**: hubungkan penyewa dengan unit, tanggal mulai, dan tanggal jatuh tempo bulanan.
5. **Tagihan**: dibuat otomatis setiap bulan lewat scheduler, bisa juga dibuat manual.
6. **Verifikasi pembayaran**: lihat bukti transfer, lalu **Setujui** (tagihan menjadi Lunas) atau **Tolak** dengan alasan (tagihan kembali menjadi Belum Bayar).
7. **Notifikasi atau alert**:
   - **Otomatis**: H-7, H-3, H-1, hari jatuh tempo, dan saat terlambat.
   - **Manual**: tombol "Kirim Pengingat" untuk satu penyewa atau beberapa sekaligus.
   - Log notifikasi yang sudah terkirim.
8. **Laporan**: rekap pembayaran per bulan, bisa diekspor ke Excel/PDF (opsional).

## 4. Struktur Database
```
users          : id, name, email, phone, password, role(admin|user), is_active
units          : id, kode_unit, alamat, harga_sewa, status(kosong|terisi), foto
leases (sewa)  : id, user_id, unit_id, tgl_mulai, tgl_selesai, tgl_jatuh_tempo (1-31), status
invoices       : id, lease_id, periode (2026-12), nominal, jatuh_tempo (date),
                 status(belum_bayar|menunggu|lunas|terlambat)
payments       : id, invoice_id, bukti_transfer (path), tgl_transfer, catatan,
                 status(menunggu|disetujui|ditolak), alasan_tolak, verified_by, verified_at
notifications  : id, user_id, judul, pesan, tipe(otomatis|manual), channel, read_at, sent_at
```

## 5. Alur Utama
```
[Scheduler tgl 1]  -> buat invoice bulan ini untuk semua sewa aktif
[Scheduler harian] -> cek invoice belum lunas -> H-7/H-3/H-1/H0/telat -> kirim notifikasi
User upload bukti  -> payment(menunggu), invoice(menunggu) -> notif ke admin
Admin setujui      -> payment(disetujui), invoice(lunas)   -> notif ke user
Admin tolak        -> payment(ditolak), invoice(belum_bayar) + alasan -> notif ke user
```

## 6. Daftar Halaman
**User:** `/login`, `/dashboard`, `/tagihan`, `/tagihan/{id}/bayar`, `/riwayat`, `/notifikasi`, `/profil`

**Admin:** `/admin/dashboard`, `/admin/penyewa`, `/admin/unit`, `/admin/sewa`, `/admin/tagihan`, `/admin/pembayaran` (verifikasi), `/admin/notifikasi` (kirim dan log), `/admin/laporan`

## 7. Tahapan Pengerjaan
| Fase | Pekerjaan | Perkiraan |
|---|---|---|
| 1. Setup | Install Laravel, Tailwind, DB, login, middleware role admin/user, seeder akun admin | 1–2 hari |
| 2. Master data (Admin) | CRUD penyewa, unit, kontrak sewa | 2–3 hari |
| 3. Tagihan | Generate tagihan otomatis dan manual, halaman tagihan untuk admin dan user | 2 hari |
| 4. Pembayaran | Upload bukti (validasi file), verifikasi admin, riwayat sudah bayar | 2–3 hari |
| 5. Notifikasi | Notifikasi in-app, email, scheduler H-7/H-3/H-1, tombol kirim manual, opsional WhatsApp | 2–3 hari |
| 6. Dashboard & laporan | Statistik admin, rekap, ekspor | 1–2 hari |
| 7. Testing & deploy | Uji alur, perbaikan, hosting, setting cron, backup | 2 hari |
| **Total** | | **sekitar 2–3 minggu** (1 developer) |

## 8. Keamanan
- Password di-hash (bawaan Laravel), dengan proteksi CSRF.
- User hanya bisa melihat tagihan dan bukti miliknya sendiri, diatur lewat Policy/Gate.
- File bukti transfer disimpan di storage privat, bukan folder publik, dan hanya bisa dibuka oleh pemiliknya dan admin.
- Validasi tipe dan ukuran file upload.
- Batasi percobaan login (rate limit).

## 9. Yang Perlu Dikonfirmasi
1. **"Daftar" di sisi user** maksudnya daftar unit yang sedang disewa saja, atau juga daftar unit kosong yang bisa dilihat calon penyewa?
2. **Akun user** dibuat oleh admin, atau penyewa bisa mendaftar sendiri?
3. **Channel notifikasi**: cukup di dalam web dan email, atau perlu WhatsApp juga (pakai layanan berbayar seperti Fonnte)?
4. **Siklus sewa**: bulanan saja, atau ada juga sewa tahunan?
5. **Stack**: setuju pakai Laravel, atau ada preferensi lain?
