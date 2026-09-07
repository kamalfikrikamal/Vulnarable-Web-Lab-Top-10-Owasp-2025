# Vulnerable Web Pentest Lab — Portal OWASP Top 10:2025

Portal navigasi + kumpulan aplikasi web yang sengaja dibuat rentan (PHP + Docker), disusun
untuk materi pelatihan pentest web. Struktur mengikuti **OWASP Top 10:2025 (Release
Candidate)** sebagai peta kategori, dengan kerentanan Injection (SQL Injection, XSS, OS
Command Injection) sebagai konten yang sudah lengkap saat ini di kategori **A05:2025 —
Injection**. Kategori lain sudah disiapkan slot-nya di portal dan tinggal diisi labnya
belakangan tanpa perlu merombak struktur.

> ⚠️ **PERINGATAN KEAMANAN** — Semua aplikasi di repo ini SENGAJA dibuat rentan untuk tujuan
> edukasi, termasuk `command-injection/` yang benar-benar mengeksekusi perintah shell (RCE
> sungguhan). Seluruh lab dilindungi **login gate (HTTP Basic Auth)** lewat service `gateway`
> — **wajib ganti kredensial default sebelum di-deploy ke server mana pun**, lihat
> [DEPLOY.md](DEPLOY.md) untuk panduan deploy yang aman ke DigitalOcean.

## Struktur

```
.
├── docker-compose.yml     # SATU compose file untuk seluruh stack (gateway + portal + semua lab)
├── gateway/               # Reverse proxy nginx + login (Basic Auth) di depan semua lab
├── deploy/                # Script hardening untuk deployment (block metadata cloud, dst)
├── DEPLOY.md              # Panduan deploy aman ke DigitalOcean
├── portal/                # Hub navigasi: OWASP Top 10 -> jenis kerentanan -> lab spesifik
│   └── src/data.php       # Konfigurasi seluruh konten portal (edit di sini untuk menambah lab baru)
├── sql-injection/         # PHP + MySQL - 7 lab SQL Injection
├── xss/                   # PHP - 7 lab XSS
└── command-injection/     # PHP - 4 lab OS Command Injection
```

Kode tiap kerentanan tetap terpisah per folder (sesuai kategorinya), tapi **hanya ada satu
`docker-compose.yml`** di root yang menjalankan semuanya sekaligus, termasuk portal & gateway.

## Menjalankan (lokal)

Butuh Docker & Docker Compose. Dari root repo, generate dulu kredensial login (kalau
`gateway/.htpasswd` belum ada atau mau diganti):

```bash
htpasswd -bcm gateway/.htpasswd training 'GantiPasswordIni123!'
docker compose up -d --build
```

Lalu buka portalnya: **http://localhost:8079** (browser akan minta login — pakai kredensial
yang baru saja dibuat).

| Service | URL | Keterangan |
|---|---|---|
| Portal | http://localhost:8079/ | Titik masuk utama — mulai dari sini |
| SQL Injection | http://localhost:8079/sqli/ | Bisa juga diakses langsung tanpa lewat portal |
| XSS | http://localhost:8079/xss/ | idem |
| Command Injection | http://localhost:8079/cmdi/ | idem |

Semua di atas ada di **satu port** (8079) dan dibedakan lewat path, dijaga oleh `gateway`
(Basic Auth) di level port tersebut — jadi cukup **login sekali**, browser otomatis
memakainya lagi untuk semua path/lab di atas (tidak perlu login ulang tiap pindah lab).

Untuk mematikan semuanya (termasuk hapus data komentar/database):

```bash
docker compose down -v
```

## Deploy ke server (mis. DigitalOcean)

Lihat **[DEPLOY.md](DEPLOY.md)** untuk panduan lengkap: spesifikasi droplet yang direkomendasikan
untuk ±26 peserta, konfigurasi firewall, generate kredensial login yang aman, memblokir akses
lab ke metadata endpoint cloud (`deploy/block-metadata.sh`), sampai runbook kalau lab dicurigai
disalahgunakan.

## Alur penggunaan portal

1. **Portal Home** (`/`) — grid 10 kategori OWASP Top 10:2025. Kategori berstatus
   **Tersedia** bisa diklik; sisanya **Segera Hadir**.
2. **Halaman Kategori** (mis. A05: Injection) — penjelasan konsep kategori dalam Bahasa
   Indonesia + contoh singkat, lalu daftar jenis kerentanan di dalamnya (SQL Injection, XSS,
   Command Injection).
3. **Halaman Kerentanan** (mis. SQL Injection) — penjelasan kerentanan tersebut + contoh,
   lalu daftar lab spesifik (Login Bypass, UNION, Error-based, dst).
4. **Halaman Lab** — penjelasan teknik spesifik + contoh payload, lalu tombol **"Mulai
   Lab"** yang membuka aplikasi vulnerable-nya di tab baru.

## Menambah kerentanan baru di kemudian hari

Struktur ini didesain agar mudah diperluas:

1. Buat folder baru di root (mis. `broken-access-control/`) berisi `app/` (Dockerfile + src)
   mengikuti pola folder yang sudah ada.
2. Tambahkan service barunya ke `docker-compose.yml` di root (tanpa `ports:` — cukup
   reachable dari `gateway` lewat jaringan Docker internal, seperti `sqli-web`/`xss-web`/
   `cmdi-web` yang sudah ada).
3. Tambah satu `location` block baru di `gateway/nginx.conf` dengan path prefix baru (mis.
   `/bac/` untuk Broken Access Control), mengikuti pola `location /sqli/ { ... }` yang sudah
   ada — ini satu-satunya bagian routing yang perlu disentuh manual, karena path gateway belum
   data-driven dari `data.php`.
4. Edit `portal/src/data.php`: ubah `status` kategori terkait dari `'soon'` menjadi
   `'active'`, lalu isi array `vulns` dan `labs`-nya mengikuti pola yang sudah ada di kategori
   `a05-injection` — field `'app'` tiap lab harus sama persis dengan path prefix yang dipakai
   di langkah 3 (mis. `'app' => 'bac'`).

Tidak perlu mengubah `index.php`, `category.php`, `vuln.php`, atau `lab.php` di portal — semua
konten portal bersifat data-driven dari `data.php`, kecuali routing path di `gateway/nginx.conf`
(langkah 3) yang memang perlu ditambah manual tiap ada app baru.

## Detail tiap kerentanan

Lihat README masing-masing folder untuk daftar lengkap payload contoh dan poin mitigasi:

- [sql-injection/README.md](sql-injection/README.md) — 7 lab SQL Injection
- [xss/README.md](xss/README.md) — 7 lab XSS
- [command-injection/README.md](command-injection/README.md) — 4 lab Command Injection

Kunci jawaban lengkap tiap lab (khusus trainer/pendamping — jangan dibagikan ke peserta
sebelum sesi selesai):

- [sql-injection/JAWABAN.md](sql-injection/JAWABAN.md)
- [xss/JAWABAN.md](xss/JAWABAN.md)
- [command-injection/JAWABAN.md](command-injection/JAWABAN.md)

## Saran alur pelatihan (1 hari, fokus Injection)

1. **Konsep dasar** (15 menit): buka Portal → klik kategori **A05: Injection**, bahas
   penjelasan & contoh di halaman tersebut bersama-sama.
2. **SQL Injection** (~2 jam): dari halaman kategori, klik **SQL Injection**, lalu kerjakan
   Lab 1 → 7 satu per satu lewat portal.
3. **XSS** (~1.5 jam): kembali ke halaman kategori (tombol breadcrumb), klik **XSS**, kerjakan
   Lab 1 → 7.
4. **Command Injection** (~1 jam): klik **OS Command Injection**, kerjakan Lab 1 → 4, tutup
   dengan diskusi risiko memanggil shell dari aplikasi web.
5. **Diskusi mitigasi** (30 menit): bandingkan kode vulnerable vs perbaikannya — lihat bagian
   "Mitigasi" di tiap README folder.

## Tooling yang disarankan untuk peserta
- Browser + DevTools
- [Burp Suite Community Edition](https://portswigger.net/burp/communitydownload) untuk
  intercept/replay request (terutama berguna di lab blind SQLi, filter bypass, dan header-based
  XSS)
- `curl` untuk lab yang melibatkan header HTTP (User-Agent) atau argument injection

## Catatan teknis
- Semua image dibangun dari `php:8.2-apache` resmi, kecuali `gateway` yang memakai
  `nginx:1.27-alpine`.
- `sql-injection/` memakai MySQL 8 sebagai container database terpisah (service `sqli-db`
  di `docker-compose.yml`); dua lab lainnya tidak butuh database eksternal.
- Setiap halaman lab punya link "← Portal" untuk kembali ke hub navigasi.
- Kategori OWASP Top 10:2025 yang dipakai portal mengacu pada Release Candidate yang beredar
  saat dokumen ini ditulis; sesuaikan urutan/penamaan di `portal/src/data.php` bila versi
  final berbeda.
- `portal`, `sqli-web`, `xss-web`, dan `cmdi-web` **tidak** publish port ke host sama sekali —
  semua akses publik lewat satu port `gateway` (8079, nginx + Basic Auth) yang meneruskan
  request ke masing-masing service lewat jaringan Docker internal berdasarkan path
  (`/sqli/`, `/xss/`, `/cmdi/`). Karena hanya ada satu origin (satu port), login Basic Auth
  cukup sekali dan otomatis berlaku untuk semua path/lab.
- `gateway/.htpasswd` **tidak boleh** memakai kredensial default/contoh saat online di server
  publik — lihat [DEPLOY.md](DEPLOY.md) langkah generate kredensial. File ini masuk
  `.gitignore` supaya tidak ikut ter-commit kalau repo di-push ke Git.
- Resource limit (CPU/memory/`pids_limit`) sengaja **tidak** diaktifkan di `docker-compose.yml`
  agar setup tetap sederhana; mitigasi risiko dari command-injection RCE mengandalkan login
  gate + block metadata endpoint di atas, bukan resource containment. Kalau butuh lapisan
  proteksi tambahan (mis. fork-bomb protection), tambahkan `pids_limit` per service secara
  manual.
