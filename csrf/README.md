# Cross-Site Request Forgery (CSRF) Lab

Aplikasi PHP sederhana yang sengaja rentan terhadap CSRF, mengikuti kategori PortSwigger Web
Security Academy **"Cross-site request forgery (CSRF)"**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Tidak ada token CSRF sama sekali | `lab1_no_token.php` |
| 2 | Token CSRF tidak diikat ke session | `lab2_token_not_tied.php` |
| 3 | Validasi token dilewati dengan menghapus parameter | `lab3_token_removal.php` |
| 4 | Aksi sensitif lewat GET request | `lab4_get_based.php` |

> ℹ️ **Catatan tentang SameSite cookie**: browser modern (Chrome/Firefox) memperlakukan cookie
> tanpa atribut `SameSite` eksplisit sebagai `SameSite=Lax` secara default, yang memblokir
> sebagian skenario CSRF cross-*site* asli. Karena definisi "site" hanya melihat skema + domain
> terdaftar (bukan port), sesama `localhost` di port berbeda tetap dianggap **same-site** —
> jadi PoC di tiap lab didesain untuk dijalankan lewat mini web server lokal di port lain
> (`python3 -m http.server 9000`), **bukan** dibuka lewat `file://`, supaya perilakunya tetap
> merepresentasikan CSRF ke domain attacker sungguhan.

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/csrf/`, atau lewat Portal → **A01: Broken Access Control** →
**Cross-Site Request Forgery (CSRF)**. Klik "Login sebagai victim" di `login.php` (disederhanakan
tanpa password karena fokus lab ini murni CSRF, bukan autentikasi).

## Panduan tiap lab

### Lab 1 — Tidak ada token
Salin PoC dari halaman lab ke `exploit.html`, jalankan `python3 -m http.server 9000` dari folder
itu, buka `http://localhost:9000/exploit.html` di tab yang sudah login. Email korban berubah
otomatis (auto-submit form POST).

### Lab 2 — Token tidak diikat ke session
Buka lab ini di jendela terpisah tanpa login untuk mendapat token "milik attacker sendiri",
tempelkan ke PoC, lalu jalankan seperti Lab 1 di tab victim. Token attacker tetap diterima
karena validasi tidak mengecek pemilik token.

### Lab 3 — Bypass dengan menghapus parameter token
PoC sama sekali tidak menyertakan field `csrf_token`. Validasi hanya jalan
`if (isset($_POST['csrf_token']))` — kalau field-nya tidak ada, validasi dilewati begitu saja.

### Lab 4 — Aksi lewat GET
Fitur ubah email menerima GET. PoC hanya berupa satu tag `<img src="...?email=...">` — tidak
perlu JavaScript maupun form sama sekali, cukup korban membuka halaman berisi tag itu.

## Mitigasi (untuk didiskusikan setelah lab)
- Gunakan **CSRF token** yang (a) diikat ke session pengguna yang membuat request, (b)
  divalidasi dengan `hash_equals()` (mencegah timing attack), dan (c) **wajib** ada — tolak
  request kalau token tidak dikirim sama sekali, jangan biarkan lolos "by default" (Lab 2 & 3).
- Jangan pernah membuat aksi yang mengubah state (create/update/delete) dapat dipicu lewat
  **GET** — GET harus tetap *safe* (tanpa efek samping), sesuai semantik HTTP (Lab 4).
- Tambahkan lapisan pertahanan kedua: cek header `Origin`/`Sec-Fetch-Site` untuk memverifikasi
  request memang berasal dari origin aplikasi sendiri.
- Set cookie session dengan atribut `SameSite=Strict` atau `Lax` (dan `Secure` di HTTPS) sebagai
  lapisan mitigasi tambahan — tapi jangan jadikan ini satu-satunya pertahanan, karena beberapa
  konteks (subdomain, top-level GET navigation) tetap bisa lolos dari `SameSite=Lax`.
- Untuk aksi paling sensitif (ubah password/email, transfer dana), pertimbangkan konfirmasi
  ulang (re-authentication / step-up auth), bukan hanya mengandalkan token CSRF.
