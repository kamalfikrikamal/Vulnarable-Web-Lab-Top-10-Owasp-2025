# Broken Function-Level Access Control (Vertical Privilege Escalation) Lab

Aplikasi PHP sederhana yang sengaja rentan terhadap kegagalan pengecekan otorisasi di level
fungsi, mengikuti kategori PortSwigger Web Security Academy **"Access control"**: user biasa
bisa menjalankan fungsi yang seharusnya khusus admin (vertical privilege escalation).

| Lab | Kategori | File |
|---|---|---|
| 1 | Unprotected admin functionality | `lab1_unprotected_admin.php` |
| 2 | Unprotected admin dengan URL "tersembunyi" | `lab2_hidden_url.php` + `internal_ops_7f3a.php` |
| 3 | Role ditentukan cookie client-writable | `lab3_role_cookie.php` |
| 4 | Method-based access control bypass | `lab4_method_bypass.php` |
| 5 | Referer-based access control bypass | `lab5_referer_bypass.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/bfla/`, atau lewat Portal → **A01: Broken Access Control** →
**Broken Function-Level Access Control**. Login di `login.php` sebagai `alice/alice123` (user
biasa) — tujuan tiap lab adalah menaikkan hak akses ke admin **tanpa** tahu password admin.

## Panduan tiap lab

### Lab 1 — Unprotected admin functionality
Server hanya mengecek "apakah sudah login", bukan "apakah role-nya admin". Login sebagai
`alice`, buka langsung `lab1_unprotected_admin.php`.

### Lab 2 — Hidden URL
URL admin tidak ada di menu manapun, tapi tercatat di `/robots.txt`. Buka
`http://localhost:8079/bfla/robots.txt`, temukan path `internal_ops_7f3a.php`, akses langsung.

### Lab 3 — Role via cookie
Setelah login, cookie `role` (non-`HttpOnly`) dipakai halaman ini untuk keputusan akses. Ubah
lewat console browser:
```js
document.cookie = "role=admin; path=/";
```
lalu refresh `lab3_role_cookie.php`.

### Lab 4 — Method-based bypass
Tombol aksi disembunyikan di render GET untuk user biasa, tapi endpoint POST tidak mengecek role
sama sekali:
```bash
curl -b "PHPSESSID=<session alice>" -X POST http://localhost:8079/bfla/lab4_method_bypass.php
```

### Lab 5 — Referer-based bypass
Akses "dianggap sah" kalau header `Referer` datang dari `admin_menu.php` — header ini sepenuhnya
dikendalikan client:
```bash
curl -b "PHPSESSID=<session alice>" -H "Referer: http://localhost:8079/bfla/admin_menu.php" \
  http://localhost:8079/bfla/lab5_referer_bypass.php
```

## Mitigasi (untuk didiskusikan setelah lab)
- Terapkan **deny by default**: setiap fungsi sensitif wajib eksplisit mengecek role/permission
  dari data user yang tervalidasi di server (session/JWT yang sudah diverifikasi), bukan dari
  input yang bisa diubah client (cookie, header, parameter).
- Jangan mengandalkan **security through obscurity** (URL "tersembunyi") sebagai pengganti
  access control (Lab 2).
- Access control harus konsisten di **semua method HTTP** yang bisa memicu aksi yang sama —
  jangan hanya menjaga tampilan tombolnya (Lab 4).
- Jangan pernah memakai `Referer`, `X-Forwarded-For`, atau header lain yang dikirim client
  sebagai bukti otorisasi (Lab 5) — semuanya bisa dipalsukan bebas.
- Sentralisasi logic access control (mis. middleware/guard di satu tempat) alih-alih mengecek
  berulang secara ad-hoc di tiap halaman — lebih mudah diaudit dan sulit "lupa" ditambahkan di
  endpoint baru.
