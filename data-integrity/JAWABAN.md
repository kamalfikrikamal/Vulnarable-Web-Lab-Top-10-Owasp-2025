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

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | `unserialize()` cookie tanpa `allowed_classes` | Admin Panel terbuka dari cookie hasil tempa sendiri |
| 2 | State klien tanpa signature/HMAC | Saldo & role di cookie diubah bebas dan diterima server |
| 3 | Perbandingan signature pakai `==`, kunci HMAC lemah | Voucher `discount=100` diterima valid tanpa tahu `SECRET_KEY` lewat channel resmi |
| 4 | Upload "update" tanpa cek checksum/signature | File dengan hash tidak cocok tetap "diterapkan" sebagai update resmi |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Jangan `unserialize()` data yang dikendalikan pengguna — pakai JSON, atau batasi dengan
  `allowed_classes => false`.
- State yang dipercaya di sisi server harus ditandatangani (`hash_hmac()`) dan diverifikasi
  ulang setiap request dengan `hash_equals()`, bukan `==`.
- Signing key harus panjang dan acak (CSPRNG), bukan string pendek yang bisa ditebak.
- Update/artifact harus diverifikasi dengan signature kriptografis terhadap public key
  tepercaya sebelum diterapkan — checksum biasa saja tidak cukup melawan attacker aktif.
