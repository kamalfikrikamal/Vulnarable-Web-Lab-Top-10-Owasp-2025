# Software Supply Chain Failures Lab (Software Supply Chain Failures)

Aplikasi PHP sederhana yang mendemonstrasikan kerentanan yang tidak muncul dari kode aplikasi
yang kamu tulis sendiri, tapi dari mata rantai di sekitarnya — library pihak ketiga, nama paket
internal, pipeline CI/CD, mekanisme auto-update, dan dependency yang tidak pernah direview —
bagian dari **A03: Software Supply Chain Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Prototype Pollution di library bundel (client-side, mirip CVE-2018-3721) | `lab1_prototype_pollution.php` |
| 2 | Dependency confusion (nama paket internal bocor) | `lab2_dependency_confusion.php` |
| 3 | Config CI/CD bocor berisi secret | `lab3_cicd_secret_exposure.php` |
| 4 | Auto-update tanpa verifikasi signature | `lab4_unsigned_autoupdate.php` |
| 5 | Postinstall script jahat dari dependency yang tidak direview | `lab5_malicious_postinstall.php` |
| 6 | Typosquatting (nama paket lookalike) | `lab6_typosquatting.php` |
| 7 | Missing Subresource Integrity (SRI) pada skrip CDN | `lab7_missing_sri.php` |
| 8 | Lockfile diabaikan saat build | `lab8_lockfile_ignored.php` |
| 9 | CI Action dipin ke tag mutable, bukan commit SHA | `lab9_ci_action_mutable_tag.php` |
| 10 | Container base image dipin ke tag mutable, bukan digest | `lab10_mutable_base_image.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/supplychain/`, atau lewat Portal → **A03: Software Supply Chain
Failures** → **Software Supply Chain Failures Lab**.

## Panduan tiap lab

### Lab 1 — Prototype Pollution
Halaman ini membundel fungsi `vulnerableMerge()` (versi vulnerable dari lodash `merge()` sebelum
4.17.5, CVE-2018-3721) yang deep-merge JSON ke object tanpa memblokir key `__proto__`. Masukkan
`{"__proto__":{"isAdmin":true}}` ke kotak payload, klik "Terapkan Merge" — panel "Admin" yang
tadinya terkunci langsung terbuka karena object `{}` baru di mana pun di halaman kini mewarisi
`isAdmin: true` dari `Object.prototype` yang sudah tercemar.

### Lab 2 — Dependency confusion
Jelajahi webroot dan temukan `/internal-package-registry.json` — file manifest build internal
yang bocor, berisi nama dependency `acme-payment-sdk`. Publikasikan paket publik dengan nama
persis `acme-payment-sdk` lewat form "Publish ke Public Registry" (isi kode bebas), lalu klik
"Jalankan Internal Build". Karena `acme-payment-sdk` ternyata tidak pernah terdaftar di Internal
Registry, proses build fallback ke Public Registry dan "menjalankan" kode yang baru saja kamu
publikasikan.

### Lab 3 — CI/CD config bocor berisi secret
Akses `/deploy.yml` langsung — file workflow CI/CD yang tidak sengaja ikut ter-deploy ke webroot,
berisi token `Authorization: Bearer DEPLOY_TOKEN_9f8a7b6c5d4e3f2a1b0c` dalam bentuk plaintext.
Tempel token tersebut ke form "Internal Deploy API" untuk memicu "deploy ke production" dan
mendapat deployment ID + timestamp sebagai bukti akses berhasil.

### Lab 4 — Auto-update tanpa verifikasi signature
Isi form "URL paket update" dengan `data/official_update.txt` — paket "resmi" diterima dan
diterapkan. Coba lagi dengan `data/tampered_update.txt` — paket yang sudah ditampering ini
diterima dan "diterapkan" dengan pesan sukses yang PERSIS SAMA, karena aplikasi tidak pernah
memverifikasi signature/checksum apa pun sebelum mempercayai isi paket.

### Lab 5 — Postinstall script jahat
Submit "dependency" baru lewat form dengan field `postinstall` diisi perintah shell, misalnya
`id` atau `whoami`. Klik "Jalankan Build (Admin)" — perintah tersebut benar-benar dieksekusi di
server dan outputnya ditampilkan, membuktikan dependency pihak ketiga yang tidak direview bisa
mendapat eksekusi kode penuh lewat lifecycle script yang berjalan otomatis saat build/install.

### Lab 6 — Typosquatting
Cari "http-client" di katalog paket — perhatikan tiga hasil yang muncul: `corp-http-client`
(resmi, ber-badge "verified publisher"), `corp-http-cIient` (huruf `l` diganti `I` kapital), dan
`corp_http_client` (`-` diganti `_`). Klik "Jalankan command di atas apa adanya" (pre-filled
dengan nama yang salah ketik, meniru command hasil copy-paste dari tutorial) untuk melihat
payload berbahaya "tereksekusi", lalu bandingkan dengan meng-install paket resmi secara manual.

### Lab 7 — Missing Subresource Integrity (SRI)
Buka lab ini di browser — perhatikan banner merah "PWNED" muncul otomatis di script pertama
(tanpa `integrity`). Script kedua menunjuk ke file yang SAMA (sudah ditampering) tapi dipasangi
atribut `integrity` dengan hash file ASLI/legit — buka DevTools → Console dan lihat browser
menolak menjalankannya sama sekali karena hash-nya tidak cocok.

### Lab 8 — Lockfile diabaikan saat build
Klik "Build dengan lockfile ditegakkan" — versi yang terpasang persis sesuai lockfile
(`3.4.0`, sudah direview). Klik "Build biasa (lockfile diabaikan)" — versi `3.5.1` yang lebih
baru (belum direview, ternyata berisi payload berbahaya) diam-diam terpasang menggantikan versi
di lockfile.

### Lab 9 — CI Action dipin ke tag mutable
Klik "Pindahkan tag v1 ke commit jahat" (simulasi maintainer Action-nya dibajak attacker), lalu
lihat tabel perbandingan: workflow yang pin ke `@v1` sekarang menjalankan commit jahat, sementara
workflow yang pin ke commit SHA eksplisit tetap menjalankan commit asli — tidak terpengaruh sama
sekali oleh perubahan tag.

### Lab 10 — Container base image dipin ke tag mutable
Klik "Push image backdoor ke tag :latest", lalu bandingkan: Dockerfile yang pakai `:latest`
sekarang ter-build dengan konten backdoor, sementara Dockerfile yang pin ke digest
`sha256:...` tetap memakai image asli — digest immutable tidak pernah berubah isinya.

## Mitigasi (untuk didiskusikan setelah lab)
- **Pin & audit versi dependency.** Kunci versi exact (lockfile) untuk semua dependency, dan
  jalankan pemindaian kerentanan otomatis (mis. Dependabot, `npm audit`, `composer audit`) secara
  rutin di pipeline CI.
- **Cegah dependency confusion.** Reservasi nama paket internal juga di registry publik (bukan
  cuma di registry internal), gunakan namespace ber-scope (mis. `@acme-corp/payment-sdk`), dan
  konfigurasikan package manager agar hanya mengambil paket berscope dari registry internal —
  tidak pernah fallback diam-diam ke publik untuk nama yang seharusnya internal.
- **Jangan pernah commit secret CI/CD ke config yang bisa ter-deploy.** Gunakan secret
  manager (Vault, AWS Secrets Manager, GitHub Actions secrets, dst.) dan token yang berumur
  pendek (short-lived), bukan credential statis yang di-hardcode di file YAML mana pun.
  Pastikan juga tidak ada file konfigurasi CI yang ikut tersalin ke webroot publik.
- **Verifikasi signature/checksum pada auto-update dan artifact pihak ketiga apa pun.** Cek
  signature GPG/code-signing dari vendor resmi dan/atau checksum kriptografis sebelum menerapkan
  paket update apa pun — jangan pernah percaya begitu saja pada isi yang diterima dari jaringan.
- **Review dan sandbox lifecycle script (postinstall, dst.) dari dependency pihak ketiga**,
  atau nonaktifkan secara default (mis. `npm install --ignore-scripts`) kecuali benar-benar
  diperlukan dan sudah diaudit.
- **Waspadai typosquatting.** Selalu salin nama paket dari sumber resmi (dokumentasi vendor,
  bukan tutorial pihak ketiga sembarangan), dan pertimbangkan tools yang mendeteksi kemiripan
  nama paket dengan dependency populer sebelum instalasi.
- **Pasang `integrity` (SRI) di setiap `<script>`/`<link>` yang dimuat dari domain eksternal**,
  ditambah `crossorigin="anonymous"` — browser akan menolak menjalankan resource yang isinya
  tidak cocok dengan hash yang dipasang.
- **Tegakkan lockfile di CI/CD** dengan `npm ci`/`--frozen-lockfile`/`composer install`
  (bukan `composer update`) — jangan biarkan build mengambil versi "terbaru" yang belum direview
  begitu saja.
- **Pin referensi eksternal ke identitas immutable, bukan tag/label mutable** — commit SHA
  untuk Action CI/CD pihak ketiga, digest (`sha256:...`) untuk base image container. Tag seperti
  `@v1`/`:latest` bisa dipindahkan kapan saja oleh siapa pun yang punya akses ke sumbernya.
