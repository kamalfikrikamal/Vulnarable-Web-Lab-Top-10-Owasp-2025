# Kunci Jawaban — Data Integrity Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — PHP Object Injection lewat cookie (`lab1_php_object_injection.php`)
**Kode:** `unserialize($_COOKIE['remember_token'])` dipanggil langsung tanpa opsi
`allowed_classes`, lalu `$obj->role === 'admin'` dipakai untuk membuka Admin Panel.
**Payload:** hasil `serialize()` dari objek dengan `role` diubah jadi `admin`:
```
O:15:"RememberMeToken":2:{s:8:"username";s:5:"guest";s:4:"role";s:5:"admin";}
```
Karena payload mengandung karakter `;` (pemisah antar cookie di header HTTP), nilainya harus
di-URL-encode dulu sebelum dipasang sebagai cookie mentah:
```
O%3A15%3A%22RememberMeToken%22%3A2%3A%7Bs%3A8%3A%22username%22%3Bs%3A5%3A%22guest%22%3Bs%3A4%3A%22role%22%3Bs%3A5%3A%22admin%22%3B%7D
```
**Langkah:** buka lab (server otomatis membuat cookie default dengan `role=user`), generate
payload lewat tombol di halaman (atau susun manual dari template di atas), set cookie
`remember_token` ke payload yang sudah di-URL-encode lewat `document.cookie =
"remember_token=" + encodeURIComponent(payload) + "; path=/"` di console DevTools, lalu reload.
**Kenapa berhasil:** server mempercayai begitu saja properti objek hasil `unserialize()` tanpa
validasi apa pun. Deserialisasi objek PHP dari data yang dikendalikan attacker berarti attacker
bisa mengisi properti apa pun dengan nilai apa pun, dan bisa memicu magic method
(`__wakeup()`, `__destruct()`, dst.) — di aplikasi nyata yang punya lebih banyak class, ini bisa
dirangkai jadi POP chain menuju RCE, bukan cuma sekadar mengubah satu properti seperti di demo
ini.
**Hasil:** bagian "ADMIN PANEL UNLOCKED" (berisi daftar user, API master key, IP internal palsu)
terbuka tanpa pernah login sebagai admin.

---

## Lab 2 — State cookie tanpa proteksi integritas (`lab2_unsigned_state_cookie.php`)
**Kode:** `json_decode(base64_decode($_COOKIE['cart_state']), true)` — tidak ada
signature/HMAC, tidak ada validasi silang ke state di server sama sekali.
**Payload:** cookie awal (`role=user`, `wallet_balance=0`):
```
eyJjYXJ0X3RvdGFsIjoxNTAwMDAsIndhbGxldF9iYWxhbmNlIjowLCJyb2xlIjoidXNlciJ9
```
decode → `{"cart_total":150000,"wallet_balance":0,"role":"user"}`

Cookie hasil tampering (`wallet_balance=999999999`, `role=admin`):
```
eyJjYXJ0X3RvdGFsIjoxNTAwMDAsIndhbGxldF9iYWxhbmNlIjo5OTk5OTk5OTksInJvbGUiOiJhZG1pbiJ9
```
decode → `{"cart_total":150000,"wallet_balance":999999999,"role":"admin"}`
**Langkah:** ambil cookie `cart_state`, decode base64 → edit JSON-nya → encode ulang ke base64
→ pasang kembali sebagai cookie → reload → klik "Checkout".
**Kenapa berhasil:** tidak ada apa pun (checksum, HMAC, penyimpanan state paralel di server)
yang mencegah nilai cookie diubah sebelum dikirim balik ke server — server menerima dan
memakainya apa adanya sebagai kebenaran.
**Hasil:** saldo `Rp 999.999.999` dan role `admin` diterima begitu saja, dan "Checkout"
berhasil memakai saldo palsu tersebut (sisa saldo `Rp 999.849.999` setelah dipotong total
belanja `Rp 150.000`).

---

## Lab 3 — Verifikasi signature pakai `==` (`lab3_timing_unsafe_hmac.php`)
**Kode:** `SECRET_KEY = 'key123'` (sengaja pendek/lemah). Signature = `hash_hmac('sha256',
$data, SECRET_KEY)`. Verifikasi: `if ($submitted_sig == $expected_sig)` — bukan
`hash_equals()`.
**Data & signature default (voucher awal, `discount=10`):**
```
data = user=guest;discount=10
signature = bfa7595e77872ced957d0d43a4f7001a3f4a452a362a58b3206a438114e86d6b
```
(dihitung dengan `php -r 'echo hash_hmac("sha256", "user=guest;discount=10", "key123");'`)

**Payload hasil forge (`discount=100`, dengan kunci yang sama karena SECRET_KEY sudah
diketahui/dibongkar):**
```
data = user=guest;discount=100
signature = ba7114c781ccc345193563bb82db55a0d6d7573ae406d314c13b64824db7fb82
```

**Jalur A (timing attack):** endpoint `?data=...&sig_guess=...` dibuat sengaja lambat
(`usleep(50)` per byte awal yang cocok) supaya perbedaan waktu respons antara tebakan yang
"hampir benar" vs "langsung salah di awal" bisa diukur lewat HTTP biasa. Hasil pengukuran nyata
saat lab ini diverifikasi (lokal, 5x percobaan tiap kondisi):
- Signature yang cocok penuh (64/64 byte benar): ~7.7–33 ms per request
- Signature yang salah di byte pertama: ~0.77–1.0 ms per request

Selisihnya jelas terukur (perbedaan rata-rata beberapa milidetik), cukup untuk trainee/tool
menentukan byte per byte signature mana yang benar dengan mengulang tiap posisi 16x (satu per
kandidat hex digit) dan memilih yang responsnya paling lambat.

**Jalur B (brute force `SECRET_KEY`, fallback):** `SECRET_KEY` cuma 6 karakter
(`key123`) — bisa dibongkar lewat wordlist kecil begitu attacker punya satu pasang
`data`+`signature` yang valid (dari cookie voucher sendiri):
```bash
php -r '
$data = "user=guest;discount=10";
$target = "bfa7595e77872ced957d0d43a4f7001a3f4a452a362a58b3206a438114e86d6b";
foreach (["123456","password","letmein","admin123","key123","secret"] as $c) {
    if (hash_hmac("sha256", $data, $c) === $target) echo "KEY: $c\n";
}
'
# Output: KEY: key123
```
**Kenapa berhasil:** `==` pada dua string BUKAN operasi konstan waktu — PHP membandingkan byte
demi byte dan bisa berhenti secepatnya begitu ada perbedaan, sehingga waktu respons bocor
informasi tentang berapa banyak byte awal yang sudah cocok. `hash_equals()` dirancang khusus
untuk selalu membandingkan seluruh panjang string tanpa peduli di mana perbedaan pertama
ditemukan. Selain itu, `SECRET_KEY` yang pendek membuat kunci itu sendiri rentan brute force,
membuat serangan bahkan lebih mudah tanpa perlu mengeksploitasi celah timing sama sekali.
**Hasil:** voucher dengan `discount=100` (bukan `discount=10` seperti aslinya) diterima sebagai
valid ("Voucher valid, diskon diterapkan!") tanpa pernah membocorkan `SECRET_KEY` lewat cara
lain selain dua jalur di atas.

---

## Lab 4 — Update sistem tanpa verifikasi checksum (`lab4_update_no_checksum.php`)
**Kode:** `file_put_contents(applied_update_path(), $content)` dipanggil langsung atas isi file
yang diupload, tanpa membandingkannya ke `data/official_update_checksum.txt` sama sekali (file
itu ada di server tapi kodenya tidak pernah membacanya).
**Checksum resmi (seed, mewakili apa yang SEHARUSNYA dipakai untuk verifikasi):**
```
Konten resmi:
SYSTEM-UPDATE-v2.1.0
Release: Security patch bundle
Build: 2026-09-01

SHA-256: d1814298fa8ecf439b6be86ec70ac0224cc37facba3f873cc338730f02c2f661
```
**Contoh file yang di-upload sebagai "update palsu" (isi bebas, sengaja beda dari yang resmi):**
```
Konten palsu:
SYSTEM-UPDATE-v2.1.0
Release: Security patch bundle
Build: 2026-09-01
BACKDOOR: admin_override=true

SHA-256: 74731eda3c7f62d8cf5a51f581841e4d8529a1c1ed9fa90f3e67a00ff7e79ac1
```
**Langkah:** upload file apa pun (isinya bebas) lewat form "Apply System Update".
**Kenapa berhasil:** tidak ada perbandingan checksum/signature apa pun di kode — file yang
diupload langsung dianggap sebagai update resmi yang sah, walau hash SHA-256-nya
(`74731eda...`) jelas berbeda dari checksum resmi yang seharusnya dipakai
(`d1814298...`).
**Hasil:** halaman menampilkan "Update berhasil diterapkan!" dan mengganti isi
`data/applied_update.txt` dengan konten arbitrer milik attacker, dengan hash yang tidak cocok
dengan checksum resmi — dan server tidak pernah menyadarinya karena memang tidak pernah
membandingkan keduanya.

---

## Lab 5 — Magic hash / type juggling bypass (`lab5_magic_hash_bypass.php`)
**Kode:** `$match = (md5($submitted) == $stored_hash);` — perbandingan pakai `==`, bukan
`===`/`hash_equals()`. `$stored_hash` di-seed sebagai `'0e462097431906509019562988736854'`
(`md5('240610708')`).
**Payload:** kirim `recovery_code=QNKCDZO`.
**Kenapa berhasil:** `md5('QNKCDZO')` = `'0e830400451993494058024219903391'`. Kedua string
("0e" diikuti hanya digit) ditafsirkan PHP sebagai notasi ilmiah saat dibandingkan dengan `==`
— `0e830400... == 0e462097...` sama-sama dievaluasi sebagai `0 == 0`, `true`, walau isi string
aslinya sama sekali berbeda. Diverifikasi langsung lewat `php -r 'var_dump("0e830400451993494058024219903391" == "0e462097431906509019562988736854");'` → `bool(true)`.
**Hasil:** "COCOK — akses recovery diberikan!" muncul tanpa attacker pernah tahu kode recovery
asli (`240610708`) sama sekali.

---

## Lab 6 — Signing secret bocor di client-side JS (`lab6_leaked_signing_secret.php`)
**Kode:** `define('RESET_LINK_SECRET', 'corp-reset-2024-preview-key');` — nilai yang PERSIS SAMA
juga ada di `reset_preview.js` (file statis publik, dimuat lab tanpa autentikasi apa pun).
**Payload:**
```bash
php -r "echo hash_hmac('sha256', 'admin@corp.test', 'corp-reset-2024-preview-key');"
# 5ca711a3682e179ce62506029d9def1f68d9fc180d16a04538722f8db66ea15b
```
Tempel hasilnya ke form "Verifikasi Token" dengan email `admin@corp.test`.
**Kenapa berhasil:** mekanisme HMAC-nya sendiri benar (dibandingkan dengan `hash_equals()`,
tidak ada celah timing) — tapi secret yang dipakai menandatangani bukan rahasia, siapa pun yang
membuka `reset_preview.js` bisa membacanya langsung.
**Hasil:** token dinyatakan "VALID" untuk email admin tanpa pernah melalui alur "Lupa Password"
yang sah sama sekali.

---

## Lab 7 — Signature cuma menutupi sebagian data (`lab7_partial_signature_gap.php`)
**Kode:** `$signature = hash_hmac('sha256', (string)$amount, TRANSFER_SECRET);` — cuma
`$amount` yang ditandatangani, `$currency`/`$recipient` tidak pernah ikut tercakup.
**Payload:** form pre-filled dengan `amount=500000`, `recipient=1111111111` (rekening sendiri),
dan signature yang valid untuk `amount=500000`. Ubah `recipient` jadi rekening lain (apa saja),
JANGAN ubah `amount`/`signature`, submit.
**Kenapa berhasil:** verifikasi signature (`hash_equals($expected, $signature)`) tetap `true`
karena `$expected` dihitung ulang dari `$amount` yang tidak berubah — server tidak tahu (dan
tidak bisa tahu) bahwa `recipient` sudah diubah, karena field itu memang tidak pernah jadi
bagian dari apa yang ditandatangani.
**Hasil:** "Signature valid: YA" dengan peringatan eksplisit bahwa recipient/currency sudah
berubah dari instruksi transfer aslinya, tapi tetap dieksekusi sebagai transfer yang sah.

---

## Lab 8 — Checksum dari sumber yang sama dengan artifact (`lab8_checksum_same_source.php`)
**Kode:** `$valid = hash_equals(hash('sha256', $content), $submitted_checksum);` — keduanya
(`$content` dan `$submitted_checksum`) datang dari field form yang sama, diisi pengirim yang
sama, dalam request yang sama.
**Payload:** isi textarea dengan konten bebas, hitung `sha256sum`-nya sendiri, tempel ke field
checksum.
**Kenapa berhasil:** perbandingan hash-nya sendiri 100% benar secara matematis — bug-nya murni
di desain: tidak ada apa pun yang memaksa checksum pembanding datang dari sumber yang BENAR-BENAR
independen (manifest resmi vendor, hash yang sudah di-pin sebelumnya). Attacker cuma perlu
menghitung checksum untuk konten mereka SENDIRI, bukan memecahkan/memalsukan apa pun.
**Hasil:** "VALID — package DITERAPKAN sebagai update resmi" untuk konten apa pun yang dikirim,
tercatat di log riwayat package yang "berhasil" diterapkan.

---

## Lab 9 — Variable injection lewat extract() (`lab9_extract_variable_injection.php`)
**Kode:** `$is_admin = false; $account_balance = 0; $username = 'guest'; extract($_GET);` — tiga
variabel yang diinisialisasi aman langsung tertimpa oleh key apa pun di `$_GET` yang namanya
cocok.
**Payload:** `?is_admin=1&account_balance=999999999&username=SUPERUSER`
**Kenapa berhasil:** `extract()` tanpa flag pembatas menulis SETIAP pasangan key-value di array
sumber jadi variabel di scope pemanggil, tanpa allowlist nama variabel mana yang aman ditimpa —
persis mekanisme `register_globals` yang dihapus dari PHP karena masalah keamanan yang sama.
**Hasil:** halaman menampilkan role `ADMIN` dan saldo `Rp 999.999.999`, keduanya murni berasal
dari parameter URL, bukan sesi login atau database.

---

## Lab 10 — Dynamic dispatch dari input tak tepercaya (`lab10_untrusted_dynamic_dispatch.php`)
**Kode:** `if (function_exists($action)) { $output = $action(); }` — tidak ada pengecekan
terhadap `$MENU_ACTIONS` (allowlist resmi), cuma memastikan sebuah fungsi bernama itu ADA.
**Payload:** `?action=grant_admin_role` (fungsi internal, tidak pernah ditautkan di menu/UI
manapun).
**Kenapa berhasil:** `function_exists()` menjawab "apakah fungsi ini ada di kodebase", bukan
"apakah fungsi ini boleh dipanggil dari luar". Fungsi internal yang cuma dimaksudkan dipanggil
oleh kode lain di sistem (bukan langsung dari HTTP request) tetap reachable selama namanya bisa
ditebak.
**Hasil:** role `alice` di `data/db.json` benar-benar berubah jadi `admin`, dan log dispatch
mencatat pemanggilan `grant_admin_role` dengan tanda "TIDAK ada di menu resmi".

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | `unserialize()` cookie tanpa `allowed_classes` | Admin Panel terbuka dari cookie hasil tempa sendiri |
| 2 | State klien tanpa signature/HMAC | Saldo & role di cookie diubah bebas dan diterima server |
| 3 | Perbandingan signature pakai `==`, kunci HMAC lemah | Voucher `discount=100` diterima valid tanpa tahu `SECRET_KEY` lewat channel resmi |
| 4 | Upload "update" tanpa cek checksum/signature | File dengan hash tidak cocok tetap "diterapkan" sebagai update resmi |
| 5 | Magic hash / type juggling pada perbandingan `==` | Recovery code palsu diterima tanpa tahu kode asli |
| 6 | Signing secret bocor di file JS publik | Token reset valid dihitung untuk email admin |
| 7 | Signature tidak mencakup seluruh field | Recipient transfer diubah, signature tetap valid |
| 8 | Checksum dari sumber tidak independen | Package apa pun "lolos" verifikasi checksum |
| 9 | `extract()` menimpa variabel internal | Role admin & saldo palsu murni dari parameter URL |
| 10 | Dispatch berbasis `function_exists()` tanpa allowlist | Fungsi internal `grant_admin_role` terpanggil dari luar |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Jangan `unserialize()` data yang dikendalikan pengguna — pakai JSON, atau batasi dengan
  `allowed_classes => false`.
- State yang dipercaya di sisi server harus ditandatangani (`hash_hmac()`) dan diverifikasi
  ulang setiap request dengan `hash_equals()`, bukan `==`.
- Signing key harus panjang dan acak (CSPRNG), bukan string pendek yang bisa ditebak.
- Update/artifact harus diverifikasi dengan signature kriptografis terhadap public key
  tepercaya sebelum diterapkan — checksum biasa saja tidak cukup melawan attacker aktif.
- Pakai `===`/`hash_equals()` untuk membandingkan hash/token, jangan pernah `==`.
- Secret signing tidak boleh pernah dikirim ke client dalam bentuk apa pun.
- Signature/checksum harus mencakup seluruh data yang integritasnya ingin dijamin.
- Checksum pembanding harus datang dari kanal independen yang tepercaya.
- Jangan `extract()` data request ke scope yang berisi variabel sensitif.
- Dispatcher berbasis nama wajib pakai allowlist eksplisit, bukan `function_exists()` saja.
