# Vulnerable Web Pentest Lab — Portal OWASP Top 10:2025

Portal navigasi + kumpulan aplikasi web yang sengaja dibuat rentan (PHP + Docker), disusun
untuk materi pelatihan pentest web. Struktur mengikuti **OWASP Top 10:2025 (Release
Candidate)** sebagai peta kategori. Seluruh sepuluh kategori sudah lengkap isinya:

- **A01:2025 — Broken Access Control**: IDOR, Broken Function-Level Access Control, CSRF.
- **A02:2025 — Security Misconfiguration**: debug mode aktif, kredensial default, directory
  listing, debug endpoint tertinggal, CORS misconfiguration, header keamanan hilang
  (clickjacking), exposed VCS/config files (`.git`, `.env`, backup editor), cookie tanpa
  HttpOnly/Secure/SameSite, HTTP method TRACE/PUT yang seharusnya dimatikan.
- **A03:2025 — Software Supply Chain Failures**: Prototype Pollution, dependency confusion,
  secret CI/CD ter-expose, auto-update tanpa verifikasi, postinstall script berbahaya,
  typosquatting, missing Subresource Integrity (SRI), lockfile diabaikan, CI Action & base image
  container yang dipin ke tag mutable alih-alih SHA/digest immutable.
- **A04:2025 — Cryptographic Failures**: Weak Password Hashing, Insecure Randomness,
  Sensitive Data Exposure, JWT Vulnerabilities.
- **A05:2025 — Injection**: SQL Injection (11 lab), XSS (11 lab), OS Command Injection, LFI,
  File Upload Vulnerabilities.
- **A06:2025 — Insecure Design**: price tampering, negative quantity, coupon stacking, skip
  step checkout, abuse bonus referral tanpa batas, 2FA bypass lewat forced browsing, ganti
  password tanpa re-autentikasi, trusted device bypass, HTTP Parameter Pollution pada kupon,
  price spoofing lewat header region, over-refund.
- **A07:2025 — Authentication Failures**: Username Enumeration, Broken Brute-Force
  Protection, Broken Session Management, Password Reset Flaws.
- **A08:2025 — Software or Data Integrity Failures**: PHP Object Injection, state cookie
  tanpa signature, signature check rentan timing attack, update tanpa checksum.
- **A09:2025 — Logging & Alerting Failures**: log injection/forgery, log injection → stored
  XSS di dashboard admin, tidak ada alert brute-force, data sensitif tercatat di log.
- **A10:2025 — Mishandling of Exceptional Conditions**: fail-open saat gateway timeout, error
  message bocor, race condition redeem gift card, fail-open di blok catch keamanan.

> ⚠️ **PERINGATAN KEAMANAN** — Semua aplikasi di repo ini SENGAJA dibuat rentan untuk tujuan
> edukasi, termasuk `command-injection/`, `lfi/`, dan `file-upload/` yang benar-benar bisa
> mencapai eksekusi perintah shell (RCE sungguhan). Seluruh lab dilindungi **login gate (HTTP
> Basic Auth)** lewat service `gateway` — **wajib ganti kredensial default sebelum di-deploy
> ke server mana pun**, lihat [DEPLOY.md](DEPLOY.md) untuk panduan deploy yang aman ke
> DigitalOcean.

## Struktur

```
.
├── docker-compose.yml     # SATU compose file untuk seluruh stack (gateway + portal + semua lab)
├── gateway/               # Reverse proxy nginx + login (Basic Auth) di depan semua lab
├── deploy/                # Script hardening untuk deployment (block metadata cloud, dst)
├── DEPLOY.md              # Panduan deploy aman ke DigitalOcean
├── portal/                # Hub navigasi: OWASP Top 10 -> jenis kerentanan -> lab spesifik
│   └── src/data.php       # Konfigurasi seluruh konten portal (edit di sini untuk menambah lab baru)
├── idor/                       # PHP - 5 lab Insecure Direct Object Reference (A01)
├── broken-function-access/     # PHP - 5 lab Broken Function-Level Access Control (A01)
├── csrf/                       # PHP - 4 lab Cross-Site Request Forgery (A01)
├── security-misconfiguration/   # PHP - 14 lab Security Misconfiguration (A02)
├── software-supply-chain/       # PHP - 10 lab Software Supply Chain Failures (A03)
├── weak-hashing/                # PHP - 3 lab Weak Password Hashing (A04)
├── insecure-randomness/         # PHP - 4 lab Insecure Randomness (A04)
├── sensitive-data-exposure/     # PHP - 3 lab Sensitive Data Exposure (A04)
├── jwt-vulnerabilities/         # PHP - 4 lab JWT Vulnerabilities (A04)
├── sql-injection/         # PHP + MySQL - 11 lab SQL Injection (A05)
├── xss/                   # PHP - 11 lab XSS (A05)
├── command-injection/     # PHP - 4 lab OS Command Injection (A05)
├── lfi/                   # PHP - 7 lab Local File Inclusion (LFI) / Path Traversal (A05)
├── file-upload/           # PHP - 7 lab File Upload Vulnerabilities (A05)
├── insecure-design/             # PHP - 11 lab Insecure Design / business logic (A06)
├── username-enumeration/       # PHP - 4 lab Username Enumeration (A07)
├── brute-force-protection/     # PHP - 3 lab Broken Brute-Force Protection (A07)
├── session-management/         # PHP - 4 lab Broken Session Management (A07)
├── password-reset/             # PHP - 4 lab Password Reset Flaws (A07)
├── data-integrity/               # PHP - 4 lab Software or Data Integrity Failures (A08)
├── logging-failures/             # PHP - 4 lab Logging & Alerting Failures (A09)
└── exceptional-conditions/       # PHP - 4 lab Mishandling of Exceptional Conditions (A10)
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
| IDOR | http://localhost:8079/idor/ | Bisa juga diakses langsung tanpa lewat portal |
| Broken Function-Level Access Control | http://localhost:8079/bfla/ | idem |
| CSRF | http://localhost:8079/csrf/ | idem |
| Security Misconfiguration | http://localhost:8079/secmisconfig/ | idem |
| Software Supply Chain Failures | http://localhost:8079/supplychain/ | idem |
| Weak Password Hashing | http://localhost:8079/hashing/ | idem |
| Insecure Randomness | http://localhost:8079/randomness/ | idem |
| Sensitive Data Exposure | http://localhost:8079/dataexposure/ | idem |
| JWT Vulnerabilities | http://localhost:8079/jwt/ | idem |
| SQL Injection | http://localhost:8079/sqli/ | idem |
| XSS | http://localhost:8079/xss/ | idem |
| Command Injection | http://localhost:8079/cmdi/ | idem |
| Local File Inclusion (LFI) | http://localhost:8079/lfi/ | idem |
| File Upload Vulnerabilities | http://localhost:8079/upload/ | idem |
| Insecure Design | http://localhost:8079/insecuredesign/ | idem |
| Username Enumeration | http://localhost:8079/userenum/ | idem |
| Broken Brute-Force Protection | http://localhost:8079/bruteforce/ | idem |
| Broken Session Management | http://localhost:8079/sessionmgmt/ | idem |
| Password Reset Flaws | http://localhost:8079/pwreset/ | idem |
| Software or Data Integrity Failures | http://localhost:8079/dataintegrity/ | idem |
| Logging & Alerting Failures | http://localhost:8079/loggingfail/ | idem |
| Mishandling of Exceptional Conditions | http://localhost:8079/exceptcond/ | idem |

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

1. Buat folder baru di root (mis. `security-misconfiguration/`) berisi `app/` (Dockerfile +
   src) mengikuti pola folder yang sudah ada.
2. Tambahkan service barunya ke `docker-compose.yml` di root (tanpa `ports:` — cukup
   reachable dari `gateway` lewat jaringan Docker internal, seperti `sqli-web`/`xss-web`/
   `idor-web` yang sudah ada).
3. Tambah satu `location` block baru di `gateway/nginx.conf` dengan path prefix baru (mis.
   `/secmisconfig/` untuk Security Misconfiguration), mengikuti pola `location /sqli/ { ... }`
   yang sudah ada — ini satu-satunya bagian routing yang perlu disentuh manual, karena path
   gateway belum data-driven dari `data.php`.
4. Edit `portal/src/data.php`: ubah `status` kategori terkait dari `'soon'` menjadi
   `'active'`, lalu isi array `vulns` dan `labs`-nya mengikuti pola yang sudah ada di kategori
   `a01-access-control`/`a04-cryptographic-failures`/`a05-injection`/`a07-authentication-failures`
   — field `'app'` tiap lab harus sama persis dengan path prefix yang dipakai di langkah 3
   (mis. `'app' => 'secmisconfig'`).

Tidak perlu mengubah `index.php`, `category.php`, `vuln.php`, atau `lab.php` di portal — semua
konten portal bersifat data-driven dari `data.php`, kecuali routing path di `gateway/nginx.conf`
(langkah 3) yang memang perlu ditambah manual tiap ada app baru.

## Detail tiap kerentanan

Lihat README masing-masing folder untuk daftar lengkap payload contoh dan poin mitigasi:

**A01: Broken Access Control**
- [idor/README.md](idor/README.md) — 5 lab Insecure Direct Object Reference (IDOR)
- [broken-function-access/README.md](broken-function-access/README.md) — 5 lab Broken Function-Level Access Control
- [csrf/README.md](csrf/README.md) — 4 lab Cross-Site Request Forgery (CSRF)

**A02: Security Misconfiguration**
- [security-misconfiguration/README.md](security-misconfiguration/README.md) — 14 lab Security Misconfiguration

**A03: Software Supply Chain Failures**
- [software-supply-chain/README.md](software-supply-chain/README.md) — 10 lab Software Supply Chain Failures

**A04: Cryptographic Failures**
- [weak-hashing/README.md](weak-hashing/README.md) — 3 lab Weak Password Hashing
- [insecure-randomness/README.md](insecure-randomness/README.md) — 4 lab Insecure Randomness
- [sensitive-data-exposure/README.md](sensitive-data-exposure/README.md) — 3 lab Sensitive Data Exposure
- [jwt-vulnerabilities/README.md](jwt-vulnerabilities/README.md) — 4 lab JWT Vulnerabilities

**A05: Injection**
- [sql-injection/README.md](sql-injection/README.md) — 11 lab SQL Injection
- [xss/README.md](xss/README.md) — 11 lab XSS
- [command-injection/README.md](command-injection/README.md) — 4 lab Command Injection
- [lfi/README.md](lfi/README.md) — 7 lab Local File Inclusion (LFI) / Path Traversal
- [file-upload/README.md](file-upload/README.md) — 7 lab File Upload Vulnerabilities

**A06: Insecure Design**
- [insecure-design/README.md](insecure-design/README.md) — 11 lab Insecure Design / business logic

**A07: Authentication Failures**
- [username-enumeration/README.md](username-enumeration/README.md) — 4 lab Username Enumeration
- [brute-force-protection/README.md](brute-force-protection/README.md) — 3 lab Broken Brute-Force Protection
- [session-management/README.md](session-management/README.md) — 4 lab Broken Session Management
- [password-reset/README.md](password-reset/README.md) — 4 lab Password Reset Flaws

**A08: Software or Data Integrity Failures**
- [data-integrity/README.md](data-integrity/README.md) — 4 lab Software or Data Integrity Failures

**A09: Logging & Alerting Failures**
- [logging-failures/README.md](logging-failures/README.md) — 4 lab Logging & Alerting Failures

**A10: Mishandling of Exceptional Conditions**
- [exceptional-conditions/README.md](exceptional-conditions/README.md) — 4 lab Mishandling of Exceptional Conditions

Kunci jawaban lengkap tiap lab (khusus trainer/pendamping — jangan dibagikan ke peserta
sebelum sesi selesai):

- [idor/JAWABAN.md](idor/JAWABAN.md), [broken-function-access/JAWABAN.md](broken-function-access/JAWABAN.md), [csrf/JAWABAN.md](csrf/JAWABAN.md)
- [security-misconfiguration/JAWABAN.md](security-misconfiguration/JAWABAN.md)
- [software-supply-chain/JAWABAN.md](software-supply-chain/JAWABAN.md)
- [weak-hashing/JAWABAN.md](weak-hashing/JAWABAN.md), [insecure-randomness/JAWABAN.md](insecure-randomness/JAWABAN.md), [sensitive-data-exposure/JAWABAN.md](sensitive-data-exposure/JAWABAN.md), [jwt-vulnerabilities/JAWABAN.md](jwt-vulnerabilities/JAWABAN.md)
- [sql-injection/JAWABAN.md](sql-injection/JAWABAN.md), [xss/JAWABAN.md](xss/JAWABAN.md), [command-injection/JAWABAN.md](command-injection/JAWABAN.md), [lfi/JAWABAN.md](lfi/JAWABAN.md), [file-upload/JAWABAN.md](file-upload/JAWABAN.md)
- [insecure-design/JAWABAN.md](insecure-design/JAWABAN.md)
- [username-enumeration/JAWABAN.md](username-enumeration/JAWABAN.md), [brute-force-protection/JAWABAN.md](brute-force-protection/JAWABAN.md), [session-management/JAWABAN.md](session-management/JAWABAN.md), [password-reset/JAWABAN.md](password-reset/JAWABAN.md)
- [data-integrity/JAWABAN.md](data-integrity/JAWABAN.md)
- [logging-failures/JAWABAN.md](logging-failures/JAWABAN.md)
- [exceptional-conditions/JAWABAN.md](exceptional-conditions/JAWABAN.md)

## Saran alur pelatihan (10 hari, mengikuti 10 kategori OWASP Top 10:2025)

**Hari 1 — A05: Injection**
1. **Konsep dasar** (15 menit): buka Portal → klik kategori **A05: Injection**, bahas
   penjelasan & contoh di halaman tersebut bersama-sama.
2. **SQL Injection** (~2 jam): Lab 1 → 7.
3. **XSS** (~1.5 jam): Lab 1 → 7.
4. **Command Injection** (~1 jam): Lab 1 → 4.
5. **Local File Inclusion (LFI)** (~1.5 jam): Lab 1 → 7.
6. **File Upload Vulnerabilities** (~1.5 jam): Lab 1 → 7.

**Hari 2 — A01: Broken Access Control**
1. **Konsep dasar** (15 menit): kategori **A01: Broken Access Control**.
2. **IDOR** (~1.5 jam): Lab 1 → 5.
3. **Broken Function-Level Access Control** (~1.5 jam): Lab 1 → 5.
4. **CSRF** (~1.5 jam): Lab 1 → 4, tutup dengan diskusi `SameSite` cookie sebagai mitigasi
   tambahan (bukan pengganti token).

**Hari 3 — A04: Cryptographic Failures**
1. **Konsep dasar** (15 menit): kategori **A04: Cryptographic Failures**.
2. **Weak Password Hashing** (~1 jam): Lab 1 → 3.
3. **Insecure Randomness** (~1.5 jam): Lab 1 → 4.
4. **Sensitive Data Exposure** (~1.5 jam): Lab 1 → 3.
5. **JWT Vulnerabilities** (~1.5 jam): Lab 1 → 4.

**Hari 4 — A07: Authentication Failures**
1. **Konsep dasar** (15 menit): kategori **A07: Authentication Failures**.
2. **Username Enumeration** (~1 jam): Lab 1 → 4.
3. **Broken Brute-Force Protection** (~1 jam): Lab 1 → 3.
4. **Broken Session Management** (~1.5 jam): Lab 1 → 4.
5. **Password Reset Flaws** (~1.5 jam): Lab 1 → 4.
6. **Diskusi mitigasi** (30 menit): bandingkan kode vulnerable vs perbaikannya di seluruh
   hari sejauh ini — lihat bagian "Mitigasi" di tiap README folder.

**Hari 5 — A02: Security Misconfiguration**
1. **Konsep dasar** (15 menit): kategori **A02: Security Misconfiguration**.
2. **Security Misconfiguration** (~1.5 jam): Lab 1 → 6 (debug mode, kredensial default,
   directory listing, debug endpoint tertinggal, CORS misconfiguration, clickjacking).
3. **Exposed VCS/Config Files** (~1 jam): Lab 7 → 9 (`.git`, `.env`, backup file editor).
4. **Cookie Security Misconfiguration** (~45 menit): Lab 10 → 12 (HttpOnly, Secure, SameSite).
5. **Insecure HTTP Methods** (~45 menit): Lab 13 → 14 (TRACE/XST, PUT-to-RCE).

**Hari 6 — A03: Software Supply Chain Failures**
1. **Konsep dasar** (15 menit): kategori **A03: Software Supply Chain Failures**.
2. **Software Supply Chain Failures** (~1.5 jam): Lab 1 → 5 (Prototype Pollution, dependency
   confusion, secret CI/CD ter-expose, auto-update tanpa verifikasi, postinstall berbahaya).
3. **Malicious/Compromised Package Content** (~1 jam): Lab 6 → 8 (typosquatting, missing SRI,
   lockfile diabaikan).
4. **Unpinned/Mutable Build References** (~45 menit): Lab 9 → 10 (CI Action & base image dipin
   ke tag mutable).

**Hari 7 — A06: Insecure Design**
1. **Konsep dasar** (15 menit): kategori **A06: Insecure Design**.
2. **Business Logic Vulnerabilities** (~1.5 jam): Lab 1 → 5 (price tampering, negative
   quantity, coupon stacking, skip step checkout, abuse referral).
3. **Flawed Multi-Step Authentication Logic** (~1 jam): Lab 6 → 8 (2FA forced browsing, ganti
   password tanpa re-auth, trusted device bypass).
4. **Business Rule Enforcement Gaps** (~1 jam): Lab 9 → 11 (HPP kupon, price spoofing region,
   over-refund).

**Hari 8 — A08: Software or Data Integrity Failures**
1. **Konsep dasar** (15 menit): kategori **A08: Software or Data Integrity Failures**.
2. **Software or Data Integrity Failures** (~2 jam): Lab 1 → 4 (PHP Object Injection, state
   cookie tanpa signature, timing attack pada signature check, update tanpa checksum).

**Hari 9 — A09: Logging & Alerting Failures**
1. **Konsep dasar** (15 menit): kategori **A09: Logging & Alerting Failures**.
2. **Logging & Alerting Failures** (~2 jam): Lab 1 → 4 (log injection/forgery, log injection
   → stored XSS, tidak ada alert brute-force, data sensitif di log).

**Hari 10 — A10: Mishandling of Exceptional Conditions**
1. **Konsep dasar** (15 menit): kategori **A10: Mishandling of Exceptional Conditions**.
2. **Mishandling of Exceptional Conditions** (~2 jam): Lab 1 → 4 (fail-open gateway timeout,
   error message bocor, race condition gift card, fail-open di blok catch).
3. **Diskusi mitigasi & penutup** (30 menit): rangkum mitigasi seluruh 10 kategori.

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
  di `docker-compose.yml`); lab lainnya tidak butuh database eksternal.
- Setiap halaman lab punya link "← Portal" untuk kembali ke hub navigasi.
- Kategori OWASP Top 10:2025 yang dipakai portal mengacu pada Release Candidate yang beredar
  saat dokumen ini ditulis; sesuaikan urutan/penamaan di `portal/src/data.php` bila versi
  final berbeda.
- Tidak satu pun service lab (`portal`, `sqli-web`, `xss-web`, `cmdi-web`, `lfi-web`,
  `upload-web`, `idor-web`, `bfla-web`, `csrf-web`, `hashing-web`, `randomness-web`,
  `dataexposure-web`, `jwt-web`, `userenum-web`, `bruteforce-web`, `sessionmgmt-web`,
  `pwreset-web`, `secmisconfig-web`, `supplychain-web`, `insecuredesign-web`,
  `dataintegrity-web`, `loggingfail-web`, `exceptcond-web`) mem-publish port ke host — semua
  akses publik lewat satu port `gateway` (8079, nginx + Basic Auth) yang meneruskan request
  lewat jaringan Docker internal berdasarkan path (`/sqli/`, `/xss/`, `/cmdi/`, `/lfi/`,
  `/upload/`, `/idor/`, `/bfla/`, `/csrf/`, `/hashing/`, `/randomness/`, `/dataexposure/`,
  `/jwt/`, `/userenum/`, `/bruteforce/`, `/sessionmgmt/`, `/pwreset/`, `/secmisconfig/`,
  `/supplychain/`, `/insecuredesign/`, `/dataintegrity/`, `/loggingfail/`, `/exceptcond/`).
  Karena hanya ada satu origin (satu port), login Basic Auth cukup sekali dan otomatis
  berlaku untuk semua path/lab.
- `gateway/.htpasswd` **tidak boleh** memakai kredensial default/contoh saat online di server
  publik — lihat [DEPLOY.md](DEPLOY.md) langkah generate kredensial. File ini masuk
  `.gitignore` supaya tidak ikut ter-commit kalau repo di-push ke Git.
- Resource limit (CPU/memory/`pids_limit`) sengaja **tidak** diaktifkan di `docker-compose.yml`
  agar setup tetap sederhana; mitigasi risiko dari RCE (command-injection, LFI-to-RCE, webshell
  upload) mengandalkan login gate + block metadata endpoint di atas, bukan resource
  containment. Kalau butuh lapisan
  proteksi tambahan (mis. fork-bomb protection), tambahkan `pids_limit` per service secara
  manual.
