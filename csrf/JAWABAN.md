# Kunci Jawaban — CSRF Lab

> 📌 **Untuk trainer/pendamping.** Payload final, langkah lengkap, dan penjelasan *kenapa*
> untuk ke-4 lab di [README.md](README.md). Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Tidak ada token (`lab1_no_token.php`)

**Kode:** handler POST langsung menulis `$_POST['email']` ke database, tidak ada field/validasi
token CSRF apa pun — cuma mengandalkan cookie session.

**PoC:**
```html
<body onload="document.forms[0].submit()">
<form action="http://localhost:8079/csrf/lab1_no_token.php" method="POST">
  <input type="hidden" name="email" value="attacker-owns-this@evil.test">
</form>
</body>
```
**Langkah:** login sebagai victim di satu tab, buka PoC (lewat `python3 -m http.server 9000`,
bukan `file://`) di tab lain pada browser yang sama.
**Kenapa berhasil:** browser otomatis menyertakan cookie session ke request POST manapun ke
domain aplikasi, terlepas dari halaman mana yang memicu request itu — dan server tidak punya
cara membedakan "request ini benar-benar dari form aplikasi" vs "dari halaman lain".
**Hasil:** email victim berubah tanpa mereka sadar sedang mengunjungi halaman attacker.

---

## Lab 2 — Token tidak diikat ke session (`lab2_token_not_tied.php`)

**Kode:**
```php
if (in_array($token, $db['valid_tokens'] ?? [], true)) { /* proceed */ }
```
Token disimpan di pool global, tidak pernah dicek terhadap session/user pemilik token.

**Langkah:** buka lab ini tanpa login (sebagai attacker) untuk mendapat token valid milik
sendiri, tempelkan ke PoC, jalankan di tab victim.
**Kenapa berhasil:** validasi hanya membuktikan "token ini pernah diterbitkan server", bukan
"token ini diterbitkan untuk request yang sedang dikirim sekarang, oleh session yang sama".
Attacker bisa dengan sah mendapatkan token yang valid, lalu memakainya untuk memalsukan request
atas nama korban.
**Hasil:** sama seperti Lab 1, email korban berubah — meski form "kelihatannya" sudah punya
proteksi CSRF token.

---

## Lab 3 — Bypass hapus parameter token (`lab3_token_removal.php`)

**Kode:**
```php
$valid = true;
if (isset($_POST['csrf_token'])) { $valid = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']); }
```
**PoC:** form tanpa field `csrf_token` sama sekali.
**Kenapa berhasil:** validasi token yang sebenarnya (`hash_equals`, diikat session) sudah benar
— tapi hanya dijalankan **kalau parameternya ada**. Kesalahan logika `isset()` ini membuat
"tidak mengirim apa-apa" justru dianggap sah, padahal seharusnya ditolak.
**Hasil:** email korban tetap berubah walau tidak ada token CSRF valid yang disertakan.

---

## Lab 4 — Aksi lewat GET (`lab4_get_based.php`)

**Kode:** `if (isset($_GET['email']) ...) { update email }` — endpoint yang sama sekali tidak
mengharuskan POST atau token apa pun.

**PoC:**
```html
<img src="http://localhost:8079/csrf/lab4_get_based.php?email=attacker-owns-this@evil.test" style="display:none">
```
**Kenapa berhasil:** GET dianggap *safe method* oleh spesifikasi HTTP dan browser (tidak
seharusnya mengubah state), sehingga browser memuatnya secara implisit lewat tag seperti
`<img>`, `<link>`, prefetch, bahkan link preview di aplikasi chat — tanpa interaksi apa pun dari
korban dan tanpa memerlukan JavaScript sama sekali.
**Hasil:** email korban berubah hanya karena mereka membuka halaman berisi satu baris HTML.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Tidak ada token | Email berubah dari form attacker auto-submit |
| 2 | Token tidak diikat session | Token milik attacker sendiri diterima untuk victim |
| 3 | Parameter token dihilangkan | Validasi ter-skip, email tetap berubah |
| 4 | State-changing action via GET | Satu `<img src>` cukup memicu perubahan |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Token CSRF wajib diikat ke session dan wajib divalidasi (tolak jika hilang), bukan opsional.
- Jangan gunakan GET untuk aksi yang mengubah state.
- Tambahkan pengecekan `Origin`/`Sec-Fetch-Site` sebagai lapisan kedua.
- `SameSite=Strict/Lax` pada cookie session sebagai mitigasi tambahan, bukan pengganti token.
