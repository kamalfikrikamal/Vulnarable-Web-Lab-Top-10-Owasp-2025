# Kunci Jawaban — Software Supply Chain Failures Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Prototype Pollution (`lab1_prototype_pollution.php`)
**Kode:** fungsi `vulnerableMerge(target, source)` melakukan deep-merge rekursif dengan
`for...in`, tanpa pernah mengecek apakah `key` adalah `__proto__`/`constructor`/`prototype`
sebelum menulis `target[key] = ...`.
**Payload:** `{"__proto__":{"isAdmin":true}}` dimasukkan ke kotak merge, lalu klik "Terapkan
Merge".
**Kenapa berhasil:** `JSON.parse()` menghasilkan `"__proto__"` sebagai OWN property literal
(bukan accessor). Saat di-iterasi dan direkursi, `target["__proto__"]` pada sisi TARGET
mengembalikan `Object.prototype` itu sendiri (lewat accessor bawaan JS), sehingga assignment di
level rekursi berikutnya (`target["isAdmin"] = true`) menulis langsung ke
`Object.prototype.isAdmin`. Ini persis mekanisme CVE-2018-3721 (lodash `merge()` sebelum 4.17.5).
**Hasil:** object polos `{}` mana pun yang dibuat setelahnya di halaman (termasuk panel "Admin"
yang mengecek `({}).isAdmin`) ikut mewarisi `isAdmin: true`, membuka panel yang seharusnya
terkunci — padahal panel tersebut tidak pernah berinteraksi langsung dengan form merge.

---

## Lab 2 — Dependency confusion (`lab2_dependency_confusion.php`)
**Kode:** proses "Internal Build" meresolve dependency `acme-internal-ui-kit`,
`acme-payment-sdk`, `lodash` — cek Internal Registry dulu (hanya berisi
`acme-internal-ui-kit`+`lodash`), kalau gagal fallback ke Public Registry (`db['public_registry']`)
dan "menjalankan" kode yang ditemukan di sana.
**Langkah:** akses `/internal-package-registry.json` untuk menemukan nama dependency internal
`acme-payment-sdk`. Publish paket publik dengan nama `acme-payment-sdk` (isi kode bebas) lewat
form pertama, lalu klik "Jalankan Internal Build".
**Kenapa berhasil:** `acme-payment-sdk` memang ada di daftar dependency yang dibutuhkan, tapi
tidak pernah benar-benar terdaftar di Internal Registry. Resolusi dependency modern lazim
fallback ke registry publik saat nama tidak ditemukan secara internal — attacker cukup
mendaftarkan nama yang sama di publik lebih dulu.
**Hasil:** pesan "Menjalankan kode dari acme-payment-sdk: &lt;isi kode yang dipublikasikan&gt;"
muncul di log build, membuktikan kode attacker "dieksekusi" oleh proses build internal.

---

## Lab 3 — CI/CD config bocor berisi secret (`lab3_cicd_secret_exposure.php`)
**Kode:** form membandingkan token yang dikirim dengan token valid
`DEPLOY_TOKEN_9f8a7b6c5d4e3f2a1b0c` (sama persis dengan yang di-hardcode di `deploy.yml`).
**Payload:** akses `/deploy.yml`, salin nilai setelah `Bearer `, tempel ke form "Internal Deploy
API".
**Kenapa berhasil:** file workflow CI/CD dengan secret plaintext di dalamnya tidak sengaja ikut
ter-deploy ke webroot publik — seharusnya file ini hanya hidup di repository/CI runner.
**Hasil:** respons "Deploy berhasil dipicu ke production" beserta deployment ID dan timestamp,
membuktikan token yang bocor memberi akses deploy production yang nyata.

---

## Lab 4 — Auto-update tanpa verifikasi signature (`lab4_unsigned_autoupdate.php`)
**Kode:** `file_get_contents(__DIR__ . '/' . $_GET['paket_url'])` — isi apa pun yang berhasil
dibaca langsung ditampilkan sebagai "paket yang diterapkan", tanpa pengecekan signature/checksum
apa pun.
**Payload:** `?paket_url=data/official_update.txt` (kontrol, paket legit) dibandingkan dengan
`?paket_url=data/tampered_update.txt` (paket yang sudah disusupi, berisi marker
"MALICIOUS CODE INJECTED").
**Kenapa berhasil:** tidak ada satu pun mekanisme (signature vendor, checksum, whitelist sumber)
yang memverifikasi keaslian paket sebelum "diterapkan". Aplikasi memperlakukan paket resmi dan
paket tampered persis sama.
**Hasil:** kedua paket diterima dengan pesan sukses "Update berhasil diterapkan" yang identik —
membuktikan aplikasi sama sekali tidak bisa membedakan update resmi dari update yang sudah
dimanipulasi penyerang.

---

## Lab 5 — Postinstall script jahat (`lab5_malicious_postinstall.php`)
**Kode:** `shell_exec($pkg['postinstall'] . ' 2>&1')` dijalankan untuk setiap paket yang
tersimpan di `submitted_packages`, dipicu oleh tombol "Jalankan Build (Admin)".
**Payload:** submit paket baru dengan field `postinstall` = `id` (atau `whoami`), lalu klik
"Jalankan Build (Admin)".
**Kenapa berhasil:** package manager sungguhan (npm/composer/dst.) menjalankan lifecycle script
dependency secara otomatis, dengan privilege penuh proses build — tanpa review manusia sama
sekali. Simulasi ini meniru itu persis lewat `shell_exec()` tanpa validasi.
**Hasil:** output perintah shell (`id`/`whoami`) muncul apa adanya di halaman "Output build",
membuktikan eksekusi perintah arbitrer yang berasal dari sebuah "dependency" yang tidak pernah
direview.

---

## Lab 6 — Typosquatting (`lab6_typosquatting.php`)
**Kode:** katalog `$catalog` berisi satu paket resmi (`corp-http-client`) dan dua lookalike
(`corp-http-cIient` dengan `I` kapital, `corp_http_client` dengan underscore) — tidak ada
mekanisme apa pun yang membedakan "publisher resmi" dari paket lookalike selain badge visual di
tabel yang mudah terlewat.
**Payload:** klik tombol "Jalankan command di atas apa adanya" (pre-filled `corp-http-cIient`),
atau install manual salah satu lookalike lewat tabel.
**Kenapa berhasil:** registry paket publik tidak memverifikasi bahwa nama yang didaftarkan
benar-benar "milik" suatu organisasi — attacker cukup mendaftarkan nama yang secara visual nyaris
identik. Korban paling sering terkena lewat copy-paste command dari sumber tidak resmi, bukan
salah ketik manual.
**Hasil:** log instalasi menunjukkan `postinstall` mengirim environment variable & mencari file
credential (`*.pem`, `*.env`, `id_rsa`) untuk diunggah ke server attacker — dibandingkan paket
resmi yang cuma mencetak pesan OK biasa.

---

## Lab 7 — Missing Subresource Integrity / SRI (`lab7_missing_sri.php`)
**Kode:** dua tag `<script src="cdn_analytics_tampered.js">` — satu tanpa atribut `integrity`,
satu lagi dengan `integrity="sha384-eaH8jZOIPjHce62nZKGmnjT2gtnvGfBqyoyi+05SkFGo5PQNh+12ExesY5C/PWFG"`
(hash SHA-384 dari `cdn_analytics_legit.js`, dihitung lewat
`openssl dgst -sha384 -binary cdn_analytics_legit.js | openssl base64 -A`).
**Langkah:** buka lab di browser, amati banner merah "PWNED" muncul otomatis (dari script tanpa
SRI), lalu buka DevTools → Console dan lihat pesan error SRI untuk script kedua.
**Kenapa berhasil:** tanpa `integrity`, browser menjalankan APAPUN isi file yang diterima dari
`src` tanpa verifikasi. Script kedua dipasangi hash dari versi ASLI/legit, tapi menunjuk ke file
yang sudah ditampering — hash aktual file itu (`sha384-oFlZ8Luu/wbxhp35iHzD+Y5C/j2dtmPn36M8UedERo1uIg/Ink8Bt35rP0LVni/V`)
tidak cocok dengan hash yang dipasang, sehingga browser menolak mengeksekusinya sama sekali.
**Hasil:** banner "PWNED" cuma muncul SEKALI (dari script pertama) — script kedua gagal dimuat
(`Failed to find a valid digest...` di Console), membuktikan SRI benar-benar memblokir eksekusi
konten yang termodifikasi.

---

## Lab 8 — Lockfile diabaikan saat build (`lab8_lockfile_ignored.php`)
**Kode:** mode `frozen` selalu memakai versi+hash dari `$lockfile` (`3.4.0`); mode `loose`
mengambil `$registry_latest` (`3.5.1`, ditandai `compromised => true`) dan menimpa lockfile tanpa
verifikasi.
**Payload:** klik "Build biasa (npm install, lockfile diabaikan)".
**Kenapa berhasil:** `npm install` tanpa `--frozen-lockfile` (atau `composer update` alih-alih
`composer install`) diizinkan mengambil versi terbaru dari registry dan menimpa lockfile — abai
terhadap fakta bahwa versi di lockfile adalah versi yang sudah direview & disetujui tim.
**Hasil:** versi `3.5.1` yang baru dipublikasikan 2 hari lalu (belum direview) terpasang,
lengkap dengan payload `postinstall` yang mengeksfiltrasi kredensial cloud dari CI runner —
dibandingkan mode `frozen` yang konsisten memasang versi `3.4.0` yang aman.

---

## Lab 9 — CI Action dipin ke tag mutable (`lab9_ci_action_mutable_tag.php`)
**Kode:** `$db['ci_action_v1_commit']` menyimpan "commit yang sedang ditunjuk tag v1" — bisa
diubah lewat form "retarget" (mensimulasikan maintainer/attacker memindah tag), sementara
`$SAFE_COMMIT` (mewakili referensi SHA eksplisit) adalah konstanta yang tidak pernah berubah.
**Payload:** klik "Pindahkan tag v1 ke commit jahat", lalu bandingkan tabel Workflow A vs B.
**Kenapa berhasil:** tag Git adalah pointer mutable — siapa pun dengan akses tulis ke repo Action
tersebut (termasuk attacker yang membajaknya) bisa memindahkannya ke commit apa pun kapan saja.
Workflow yang pin ke `@v1` otomatis menjalankan apa pun yang SEDANG ditunjuk tag itu saat build
berjalan.
**Hasil:** Workflow A (pin tag) menunjukkan commit jahat (`f9e8d7c`) sedang aktif; Workflow B
(pin SHA) tetap menunjukkan commit asli (`a1b2c3d`) — tidak terpengaruh sama sekali oleh
perubahan tag.

---

## Lab 10 — Container base image dipin ke tag mutable (`lab10_mutable_base_image.php`)
**Kode:** struktur identik dengan Lab 9, tapi untuk konteks container registry —
`$db['base_image_latest_content']` mewakili isi tag `:latest` yang bisa "di-push ulang", vs
`$SAFE_DIGEST_CONTENT` yang mewakili referensi digest immutable.
**Payload:** klik "Push image backdoor ke tag :latest", lalu bandingkan tabel Dockerfile A vs B.
**Kenapa berhasil:** tag image container hanyalah label yang menunjuk ke digest tertentu — label
itu bisa dipindahkan ke digest lain kapan saja oleh siapa pun yang punya akses push ke registry.
`docker build`/`docker pull` tanpa digest eksplisit selalu mengambil apa pun yang SEDANG ditunjuk
tag saat itu.
**Hasil:** Dockerfile A (pin `:latest`) menunjukkan konten backdoor (cron job beacon ke
attacker.example) sedang aktif; Dockerfile B (pin digest `sha256:...`) tetap menunjukkan image
resmi — tidak terpengaruh sama sekali oleh apa yang di-push ke tag `:latest`.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Prototype pollution lewat `__proto__` di merge tanpa filter | Panel Admin terbuka lewat `({}).isAdmin === true` |
| 2 | Dependency confusion (fallback internal → publik) | Kode paket publik "dieksekusi" saat internal build |
| 3 | Secret CI/CD bocor di config yang ter-deploy | Deploy production berhasil dipicu dengan token bocor |
| 4 | Auto-update tanpa verifikasi signature | Paket tampered diterima persis sama seperti paket resmi |
| 5 | Lifecycle script dependency tidak direview | Perintah shell arbitrer (`id`/`whoami`) tereksekusi di server |
| 6 | Typosquatting (nama paket lookalike) | Payload postinstall jahat "tereksekusi" dari paket salah ketik |
| 7 | Missing SRI pada skrip CDN | Script tanpa integrity jalan; script dengan integrity diblokir browser |
| 8 | Lockfile diabaikan saat build | Versi belum-direview & berbahaya terpasang menggantikan lockfile |
| 9 | CI Action dipin ke tag mutable | Commit jahat aktif di workflow pin-tag, tidak di workflow pin-SHA |
| 10 | Base image dipin ke tag mutable | Backdoor aktif di build pin-tag, tidak di build pin-digest |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Pin & audit versi dependency (lockfile + scanning otomatis seperti Dependabot).
- Reservasi nama paket internal di registry publik juga, pakai namespace berscope.
- Jangan pernah hardcode secret CI/CD di config yang bisa ter-deploy — pakai secret manager +
  token berumur pendek.
- Verifikasi signature/checksum untuk auto-update dan artifact pihak ketiga apa pun.
- Review/sandbox lifecycle script (postinstall, dst.) dari dependency pihak ketiga, atau
  nonaktifkan secara default.
- Waspadai typosquatting — selalu salin nama paket dari sumber resmi.
- Pasang atribut `integrity` (SRI) di setiap resource eksternal yang dimuat langsung.
- Tegakkan lockfile di CI/CD (`npm ci`/`--frozen-lockfile`), jangan biarkan build mengambil
  versi terbaru begitu saja.
- Pin referensi eksternal (Action CI/CD, base image) ke identitas immutable (SHA/digest), bukan
  tag/label mutable.
