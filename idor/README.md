# Insecure Direct Object Reference (IDOR) Lab

Aplikasi PHP sederhana yang sengaja rentan terhadap IDOR, mengikuti kategori PortSwigger Web
Security Academy **"Access control"**: server mempercayai identifier (ID/token) yang dikirim
client untuk menentukan data mana yang diambil/diubah, tanpa memverifikasi kepemilikan.

| Lab | Kategori | File | Parameter |
|---|---|---|---|
| 1 | IDOR baca data (read) | `lab1_basic_idor.php` | `id` (GET) |
| 2 | IDOR pada aksi tulis (write) | `lab2_idor_write.php` | `user_id` (POST, hidden field) |
| 3 | IDOR dengan ID tidak berurutan | `lab3_idor_unpredictable.php` | `token` (GET) |
| 4 | IDOR di endpoint API/JSON | `lab4_idor_api.php` | `id` (GET) |
| 5 | Mass Assignment | `lab5_mass_assignment.php` | field POST arbitrer (`role`) |

## Menjalankan

> Lab ini bagian dari satu stack terpadu. Jalankan dari **root repo**, lihat
> [README.md utama](../README.md) untuk portal navigasinya.

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/idor/`, atau lewat Portal → **A01: Broken Access Control** →
**Insecure Direct Object Reference (IDOR)**. Login dulu di `login.php` (akun demo:
`alice/alice123`, `bob/bob123`, `carol/carol123`, `admin/admin123`).

## Panduan tiap lab

### Lab 1 — Basic IDOR
Login sebagai `alice`, invoice miliknya ada di `id=5001`. Coba:
```
?id=5002
?id=5003
```
Data invoice `bob` dan `carol` ikut terbaca meski kamu login sebagai `alice`.

### Lab 2 — IDOR pada aksi tulis
Form update email menyimpan `user_id` di hidden field. Ubah nilainya sebelum submit (DevTools /
Burp) menjadi `user_id=4` (akun admin) untuk mengubah email milik admin, bukan email sendiri.

### Lab 3 — IDOR dengan ID tidak berurutan
Dokumen dipakai token 12-karakter (bukan angka urut). Token milik user lain bocor lewat bagian
"Recent team activity" di halaman yang sama — salin salah satu token, tempel ke `?token=`.

### Lab 4 — IDOR di endpoint API/JSON
Widget profil memanggil `lab4_idor_api.php?api=1&id=<id>` lewat `fetch()`. Endpoint ini bisa
diakses langsung dan mengembalikan seluruh field (termasuk `password` dan `credit_card`) untuk
ID mana pun:
```
?api=1&id=4
```

### Lab 5 — Mass Assignment
Form hanya menampilkan `full_name`/`email`, tapi server melakukan
`array_merge($user, $_POST)`. Tambahkan field `role=admin` ke request (curl/Burp) untuk
menaikkan hak akses akun sendiri.

## Mitigasi (untuk didiskusikan setelah lab)
- **Selalu verifikasi kepemilikan di server**: bandingkan ID objek yang diminta dengan
  identitas user dari session (`WHERE id = ? AND owner_id = ?`), jangan hanya percaya parameter
  dari client.
- ID "acak" (UUID/token) mengurangi kemudahan *enumerasi*, tapi **bukan pengganti** access
  control — tetap wajib dicek kepemilikannya (Lab 3).
- Endpoint API butuh access control yang sama ketatnya dengan halaman HTML — jangan asumsikan
  endpoint "internal" tidak akan dipanggil langsung (Lab 4).
- Gunakan **allowlist field** eksplisit untuk operasi update (mis. DTO/serializer khusus),
  jangan bind seluruh `$_POST`/request body langsung ke model (Lab 5) — ini mencegah mass
  assignment ke field sensitif seperti `role`/`is_admin`.
- Terapkan prinsip *least privilege* & *deny by default*: tolak akses kecuali eksplisit
  diizinkan, bukan sebaliknya.
