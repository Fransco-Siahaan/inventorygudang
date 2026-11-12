# Inventory Gudang — PT. Argatek Torsada Guna

Aplikasi pencatatan stok gudang: input barang baru, tambah stok, pengeluaran barang,
daftar stok dengan riwayat, serta ekspor PDF/Excel.

**Stack:** PHP 8.1+ (native, tanpa framework), MySQL/MariaDB, TCPDF (via Composer).

---

## 1. Instalasi baru

```bash
# 1. Install dependency PHP (TCPDF). Pastikan ekstensi php-mysqli dan php-curl aktif.
composer install

# 2. Buat database dan tabel
mysql -u root -p < db/schema.sql

# 3. Atur koneksi (opsional, default: localhost, root, tanpa password, dbiogudang)
export DB_HOST=localhost DB_USER=root DB_PASS= DB_NAME=dbiogudang

# 4. Jalankan (contoh dengan server bawaan PHP)
php -S localhost:8000     # lalu buka http://localhost:8000
```

Untuk produksi, gunakan Apache/Nginx + PHP-FPM dan **jangan** memakai akun root MySQL.
Buat user khusus yang hanya punya akses ke `dbiogudang`.

## 2. Upgrade dari versi sebelumnya

Versi lama menyimpan riwayat tanpa kolom `id`. Jalankan migrasi sekali saja:

```bash
mysqldump -u root -p dbiogudang > backup_sebelum_v2.sql   # BACKUP DULU
mysql -u root -p < db/migrasi_v2.sql
```

Migrasi menambahkan kolom `id` (auto-increment) pada tabel `tambah` dan `keluar`, serta
indeks untuk pencarian. Tanpa kolom ini, menghapus satu riwayat bisa ikut menghapus riwayat
lain yang nomornya sama.

---

## 3. Struktur folder

| Lokasi | Isi |
|---|---|
| `index.php` | Dashboard: ringkasan stok, barang menipis, aktivitas terbaru |
| `masuk.php` | Input barang baru (kode unik) |
| `nambahbarang.php` | Tambah stok barang yang sudah ada |
| `output.php` | Pengeluaran barang (stok dicek di server) |
| `informasimasukbarang.php` | Daftar stok, pencarian, detail + riwayat, hapus barang |
| `inbarang.php` | Riwayat penambahan stok |
| `riwayatkurangbarang.php` | Riwayat pengeluaran |
| `forminputbarang.php`, `formnambahbarang.php`, `formoutputbarang.php` | Endpoint transaksi (JSON) |
| `hapusdata*.php` | Endpoint hapus (barang, riwayat tambah, riwayat keluar) |
| `get_riwayat.php` | Endpoint JSON riwayat per kode barang |
| `cetak*.php` | Ekspor PDF (TCPDF) dan Excel |
| `konek.php` | Koneksi database (membaca environment variable) |
| `inc/` | `bootstrap.php`, `functions.php` (helper), `layout.php` (sidebar & topbar) |
| `assets/css/app.css` | Sistem desain tunggal |
| `assets/js/app.js` | Toast, dialog konfirmasi, modal, format angka |
| `db/` | `schema.sql` (instalasi baru), `migrasi_v2.sql` (upgrade) |
| `_arsip/` | File lama yang tidak dipakai lagi (boleh dihapus setelah diverifikasi) |

---

## 4. Yang diperbaiki

**Keamanan**
- Pencarian dan ekspor memakai prepared statement atau escape. SQL injection yang sebelumnya
  ada di `inbarang.php`, `infodatabarang.php`, dan seluruh `cetak*.php` sudah ditutup.
- Data dari database ditampilkan dengan escape. Tidak ada lagi `innerHTML` berisi data user (XSS).
- Redirect setelah hapus hanya ke halaman lokal (mencegah open redirect lewat `HTTP_REFERER`).
- Kredensial database bisa diatur lewat environment variable.

**Integritas data**
- Riwayat tambah dan keluar punya `id` unik. Hapus satu baris tidak lagi menghapus baris lain.
- Harga diambil dari database, bukan dari form, sehingga klien tidak bisa mengubah harga.
- Stok dicek di server dan transaksi memakai `FOR UPDATE`, sehingga stok tidak bisa terkurangi ganda.
- Tanda kutip tidak lagi tersimpan dengan backslash (`O'Neil` sebelumnya menjadi `O\'Neil`).
- Kode barang dijamin unik di level database.

**Bug lain yang diperbaiki**
- Halaman pengeluaran sebelumnya error JavaScript karena mengakses elemen yang tidak ada.
- Pagination riwayat kehilangan kata pencarian.
- Satuan di notifikasi pengeluaran tampil tanpa spasi (`5set`).

**Tampilan & pengalaman pengguna**
- Sidebar yang konsisten di semua halaman, responsif untuk HP (menu geser).
- Dashboard dengan ringkasan, barang menipis, dan aktivitas terbaru.
- Form dengan ringkasan langsung (total nilai, sisa stok) dan validasi per kolom.
- Pengeluaran otomatis dinonaktifkan jika jumlah melebihi stok.
- Notifikasi toast menggantikan `alert()` dan blok pesan yang mendorong isi halaman.
- Dialog konfirmasi sebelum menghapus, menggantikan `confirm()` bawaan browser.
- Modal detail barang dengan tab Semua / Masuk / Keluar dan ekspor per barang.
- Pencarian lewat URL (`?q=...`), sehingga bisa di-bookmark dan tetap ada saat pindah halaman.

---

## 5. Keputusan yang perlu dikonfirmasi pemilik sistem

Perilaku berikut dipertahankan dari versi lama. Pastikan sudah sesuai dengan alur kerja gudang:

1. **Hapus riwayat tidak mengubah stok.** Menghapus riwayat tambah tidak mengurangi stok, dan
   menghapus riwayat keluar tidak mengembalikan stok. Jika stok harus ikut berubah, logika
   ini perlu ditambahkan di `hapusdatainbarang.php` dan `hapusdatakurangbarang.php`.
2. **Hapus barang dari daftar stok tidak menghapus riwayatnya.** Riwayat tetap tersimpan.
3. **Belum ada login.** Siapa pun yang bisa mengakses URL dapat mengubah data.
   Jalankan hanya di jaringan internal, atau tambahkan autentikasi sebelum dipublikasikan.
4. **Belum ada proteksi CSRF** pada form POST. Sebaiknya ditambahkan bersama login.
5. **Data invoice di `cetakpdf.php`** (nomor, nama pemesan, rekening bank) masih ditulis
   langsung di kode. Sebaiknya dipindahkan ke file konfigurasi.

---

## 6. Pengujian

- **28 uji HTTP**: input dan duplikat kode, validasi, tambah stok, pengeluaran melebihi stok
  (stok tidak berubah), kombinasi nomor/kode salah, pencarian dengan payload SQL injection,
  hapus per id, open redirect, serta ekspor PDF/Excel dengan karakter khusus.
- **29 uji browser** (Chromium): validasi form, ringkasan live, peringatan stok kurang,
  modal detail dan tab, dialog konfirmasi (batal dan lanjut), toast, dan menu mobile.

Jalankan ulang pengujian setelah setiap perubahan pada kode transaksi.
