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

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Prototype pollution lewat `__proto__` di merge tanpa filter | Panel Admin terbuka lewat `({}).isAdmin === true` |
| 2 | Dependency confusion (fallback internal → publik) | Kode paket publik "dieksekusi" saat internal build |
| 3 | Secret CI/CD bocor di config yang ter-deploy | Deploy production berhasil dipicu dengan token bocor |
| 4 | Auto-update tanpa verifikasi signature | Paket tampered diterima persis sama seperti paket resmi |
| 5 | Lifecycle script dependency tidak direview | Perintah shell arbitrer (`id`/`whoami`) tereksekusi di server |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Pin & audit versi dependency (lockfile + scanning otomatis seperti Dependabot).
- Reservasi nama paket internal di registry publik juga, pakai namespace berscope.
- Jangan pernah hardcode secret CI/CD di config yang bisa ter-deploy — pakai secret manager +
  token berumur pendek.
- Verifikasi signature/checksum untuk auto-update dan artifact pihak ketiga apa pun.
- Review/sandbox lifecycle script (postinstall, dst.) dari dependency pihak ketiga, atau
  nonaktifkan secara default.
