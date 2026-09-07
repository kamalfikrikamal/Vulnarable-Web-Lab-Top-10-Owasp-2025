# Kunci Jawaban — Broken Function-Level Access Control Lab

> 📌 **Untuk trainer/pendamping.** Payload final, langkah lengkap, dan penjelasan *kenapa*
> untuk ke-5 lab di [README.md](README.md). Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Unprotected admin functionality (`lab1_unprotected_admin.php`)

**Kode:** hanya `if (!$me) { deny }` — tidak ada `if ($me['role'] !== 'admin')`.

**Langkah:** login sebagai `alice`, buka `lab1_unprotected_admin.php`.
**Kenapa berhasil:** developer menyamakan "sudah login" dengan "berhak", padahal keduanya beda
konsep (autentikasi vs otorisasi).
**Hasil:** config sistem (`DATABASE_PASSWORD`, `STRIPE_API_KEY`, dst) terbaca oleh user biasa.

---

## Lab 2 — Hidden URL (`lab2_hidden_url.php` + `internal_ops_7f3a.php`)

**Langkah:** buka `/robots.txt`, ambil path `internal_ops_7f3a.php` dari baris `Disallow`, akses
langsung file itu.
**Kenapa berhasil:** `robots.txt` memang dipublikasikan untuk crawler mesin pencari — ironisnya
baris `Disallow` yang dimaksud untuk *menyembunyikan* URL dari indexing justru **mengumumkan**
keberadaan URL tersebut ke siapa pun yang membacanya. File tujuannya sendiri tidak melakukan
pengecekan role apa pun (sama seperti Lab 1).
**Hasil:** config sistem yang sama terbaca lewat URL "tersembunyi" ini.

---

## Lab 3 — Role via cookie (`lab3_role_cookie.php`)

**Kode:**
```php
$effective_role = $_COOKIE['role'] ?? 'user';
$is_admin = ($effective_role === 'admin');
```
**Payload:** login sebagai `alice`, lalu di console browser:
```js
document.cookie = "role=admin; path=/";
```
refresh halaman.
**Kenapa berhasil:** cookie ini di-set saat login sebagai "cache" role, tapi tidak
ditandatangani (bukan JWT bertanda tangan, bukan session server-side) dan tidak `HttpOnly` —
sehingga bisa diubah bebas oleh JavaScript maupun DevTools, dan halaman ini keliru
mempercayainya sebagai sumber kebenaran otorisasi.
**Hasil:** panel admin & config sistem terbuka meski data user asli di server tetap `role: user`.

---

## Lab 4 — Method-based bypass (`lab4_method_bypass.php`)

**Kode:** pengecekan `$me['role'] === 'admin'` hanya dipakai untuk memutuskan apakah tombol
ditampilkan di render GET; handler POST langsung mengeksekusi aksi tanpa syarat apa pun.

**Payload:**
```bash
curl -b "PHPSESSID=<session alice>" -X POST http://target/bfla/lab4_method_bypass.php
```
**Kenapa berhasil:** developer menganggap "kalau tombolnya tidak terlihat, aksinya tidak akan
terpicu" — padahal endpoint HTTP di baliknya tetap bisa dipanggil langsung terlepas dari apakah
ada UI yang mengarah ke sana, dan tidak ada pengecekan ulang di titik eksekusi sebenarnya.
**Hasil:** aksi "Hapus Log Audit" berhasil dijalankan oleh `alice` (user biasa).

---

## Lab 5 — Referer-based bypass (`lab5_referer_bypass.php`)

**Kode:**
```php
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$came_from_admin_menu = (strpos($referer, '/admin_menu.php') !== false);
```
**Payload:**
```bash
curl -b "PHPSESSID=<session alice>" -H "Referer: http://target/bfla/admin_menu.php" \
  http://target/bfla/lab5_referer_bypass.php
```
**Kenapa berhasil:** header `Referer` dikirim oleh browser/klien pemanggil dan **sepenuhnya**
bisa dipalsukan nilainya — bukan bukti kriptografis apa pun tentang dari mana request "benar-
benar" berasal. Server memperlakukannya seolah itu adalah bukti navigasi sah dari menu admin.
**Hasil:** config sistem terbuka hanya dengan mengatur satu header HTTP, tanpa perlu role admin
maupun menavigasi UI apa pun.

---

## Ringkasan hasil akhir

| Lab | Teknik | Payload final | Bukti keberhasilan |
|---|---|---|---|
| 1 | Missing role check | Akses langsung setelah login | Config sistem terbaca user biasa |
| 2 | Hidden URL leak | URL dari `robots.txt` | `internal_ops_7f3a.php` terbuka |
| 3 | Client-controlled role | Cookie `role=admin` | Panel admin terbuka |
| 4 | Method bypass | `POST` langsung tanpa lewat UI | Aksi admin tereksekusi |
| 5 | Referer spoofing | Header `Referer` palsu | Config sistem terbuka |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Deny by default; cek role dari data user tervalidasi di server pada **setiap** endpoint
  sensitif, bukan dari cookie/header yang bisa dipalsukan.
- URL "tersembunyi" bukan access control (Lab 2).
- Cek otorisasi harus berlaku di semua method HTTP yang memicu aksi yang sama (Lab 4).
- Jangan pernah memakai Referer/header lain sebagai bukti otorisasi (Lab 5).
