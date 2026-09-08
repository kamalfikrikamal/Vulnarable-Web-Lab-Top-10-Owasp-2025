# Kunci Jawaban — Security Misconfiguration Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Debug mode / stack trace bocor (`lab1_debug_stacktrace.php`)
**Kode:** `define('DEBUG_MODE', true)`, exception dilempar saat `is_numeric($id)` gagal, lalu
karena `DEBUG_MODE` true, `$e->getMessage()` dan trace dicetak mentah ke halaman.
**Payload:** masukkan `abc` (atau nilai non-angka apa pun) ke kolom Product ID.
**Kenapa berhasil:** mode debug yang seharusnya hanya aktif di environment development tertinggal
menyala di production. Aplikasi menampilkan pesan error asli (termasuk connection string
database) alih-alih pesan generik.
**Hasil:** connection string database production bocor lengkap:
`mysql:host=db-prod.internal;dbname=shop_prod;user=shop_app;password=Pr0d_DbP4ss_2024!`.

---

## Lab 2 — Kredensial default (`lab2_default_credentials.php`)
**Payload:** username `admin`, password `admin123`.
**Kenapa berhasil:** kredensial bawaan installer/vendor tidak pernah diganti sebelum go-live —
komentar HTML (`<!-- TODO: ganti kredensial default admin/admin123 sebelum go-live -->`) yang
terlihat di view-source bahkan mengonfirmasi ini adalah utang teknis yang diketahui tapi tidak
pernah diselesaikan.
**Hasil:** login berhasil ke "Admin Dashboard", membocorkan daftar email seluruh user terdaftar.

---

## Lab 3 — Directory listing (`lab3_directory_listing.php`)
**Payload:** buka `/secmisconfig/backup/`.
**Kenapa berhasil:** direktori `backup/` dikonfigurasi dengan `Options +Indexes` di Apache
(`apache/backup_listing.conf`), sehingga Apache menampilkan daftar seluruh file di dalamnya
tanpa perlu tahu nama file spesifik.
**Hasil:** `db_backup_2024.sql.bak` dan `config.php.bak` bisa dibuka langsung — keduanya
membocorkan password database yang sama (`Adm1n_S3cret_2024`), mengonfirmasi validitas
kredensial tersebut lewat dua sumber independen.

---

## Lab 4 — Endpoint debug terlupakan (`lab4_debug_endpoint.php`)
**Payload:** akses langsung `http://localhost:8079/secmisconfig/lab4_debug_endpoint.php`
(tidak ada di navigasi manapun, hanya bisa ditemukan lewat wordlist/URL guessing atau kalau
sudah tahu dari sumber lain).
**Kenapa berhasil:** endpoint debug internal yang dipakai developer saat development tertinggal
di production, tidak dihapus dan tidak dilindungi autentikasi. "Tidak ditautkan di menu" bukan
kontrol akses (security by obscurity) — URL tetap dapat diakses siapa saja yang mengetahuinya.
**Hasil:** versi PHP, document root, dan "environment variables" (password database, AWS secret
key, JWT signing key, secret key payment gateway) semuanya terekspos di satu halaman publik.

---

## Lab 5 — CORS misconfiguration (`lab5_cors_misconfig.php`)
**Kode:** `header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']); header('Access-Control-Allow-Credentials: true');`
**Payload:** `curl -i -H "Origin: https://evil.example" ".../lab5_cors_misconfig.php?action=api"`,
atau `fetch(url, {credentials:'include'}).then(r=>r.json()).then(console.log)` dari console
browser di origin lain.
**Kenapa berhasil:** server me-reflect nilai header `Origin` apa pun yang dikirim client ke
`Access-Control-Allow-Origin` tanpa validasi allowlist, sekaligus mengizinkan
`Access-Control-Allow-Credentials: true` — kombinasi ini membuat browser di origin mana pun
diizinkan membaca response yang berisi data user yang sedang login (lewat cookie session yang
otomatis terkirim).
**Hasil:** response `curl`/`fetch` mengembalikan `email` dan `api_key` milik user yang sedang
login, terlihat oleh origin yang sama sekali bukan aplikasi resmi.

---

## Lab 6 — Header keamanan hilang / Clickjacking (`lab6_missing_headers_clickjacking.php`)
**Kode:** halaman ini tidak mengirim `X-Frame-Options` maupun
`Content-Security-Policy: frame-ancestors` sama sekali.
**Langkah:** buka `lab6_attacker_iframe.php` (halaman simulasi attacker), klik tombol umpan
"Klaim Hadiah" yang secara visual menutupi tombol "Konfirmasi Transfer" asli yang disembunyikan
di dalam iframe (`opacity` mendekati nol) tepat di bawahnya, lalu kembali ke
`lab6_missing_headers_clickjacking.php`.
**Kenapa berhasil:** tanpa `X-Frame-Options`/`frame-ancestors`, browser mengizinkan halaman
tersebut di-embed di iframe milik domain lain. Attacker menumpuk iframe yang disembunyikan di
bawah UI umpan sehingga klik yang dimaksudkan korban untuk hal lain ("klaim hadiah") sebenarnya
jatuh tepat di tombol submit form transfer di halaman asli.
**Hasil:** entri transfer baru muncul di tabel Log Transfer, dibuat tanpa korban pernah benar-benar
bermaksud menekan tombol "Konfirmasi Transfer".

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Mode debug aktif di production | Password database production bocor di pesan error |
| 2 | Kredensial default tidak diganti | Login admin berhasil, data user terbaca |
| 3 | Directory listing aktif | Isi file backup (password DB) terbaca |
| 4 | Endpoint debug tidak dihapus/dilindungi | Kredensial cloud & JWT signing key terekspos |
| 5 | CORS reflect origin + credentials | Data pribadi user lintas-origin terbaca |
| 6 | Header X-Frame-Options/CSP tidak ada | Transfer dana terjadi tanpa sepengetahuan korban |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Matikan mode debug di production; log detail error hanya ke server, bukan ke response.
- Ganti semua kredensial default sebelum go-live.
- Nonaktifkan directory listing dan jangan taruh file sensitif di dalam webroot.
- Hapus/lindungi endpoint debug sebelum deploy.
- Terapkan allowlist origin yang ketat untuk CORS; jangan gabungkan reflect-origin dengan
  `Access-Control-Allow-Credentials: true`.
- Pasang `X-Frame-Options` dan `Content-Security-Policy: frame-ancestors` di semua respons.
