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
