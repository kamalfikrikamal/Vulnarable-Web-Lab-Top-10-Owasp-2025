# Kunci Jawaban — IDOR Lab

> 📌 **Untuk trainer/pendamping.** Payload final, langkah lengkap, dan penjelasan *kenapa*
> untuk ke-5 lab di [README.md](README.md). Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Basic IDOR (`lab1_basic_idor.php`)

**Kode:**
```php
foreach ($db['invoices'] as $inv) { if ((string)$inv['id'] === (string)$id) { $invoice = $inv; break; } }
```
Tidak ada perbandingan `$inv['user_id'] == $me['id']` sama sekali sebelum data dikembalikan.

**Payload:** login sebagai `alice`, lalu `?id=5002` atau `?id=5003`.
**Kenapa berhasil:** server mengambil invoice berdasarkan `id` yang dikirim client, bukan
berdasarkan siapa yang sedang login.
**Hasil:** invoice milik `bob` (5002) dan `carol` (5003) terbaca meski session milik `alice`.

---

## Lab 2 — IDOR pada aksi tulis (`lab2_idor_write.php`)

**Kode:**
```php
$target_id = $_POST['user_id'] ?? $me['id'];
foreach ($db['users'] as &$u) { if ((string)$u['id'] === (string)$target_id) { $u['email'] = $new_email; ... } }
```
`user_id` diambil dari hidden field POST, bukan dari `$_SESSION['uid']`.

**Payload:** intercept request POST (Burp) atau edit hidden input via DevTools sebelum submit:
```
user_id=4&email=pwned@attacker.test
```
**Kenapa berhasil:** validasi kepemilikan tidak pernah dilakukan — server percaya begitu saja
nilai `user_id` dari form, walau field itu "hidden" (hidden ≠ tidak bisa diubah client).
**Hasil:** email akun `admin` (id=4) berubah menjadi `pwned@attacker.test`.

---

## Lab 3 — IDOR dengan ID tidak berurutan (`lab3_idor_unpredictable.php`)

**Kode:**
```php
function doc_token($username) { return substr(sha1($username . '_doc_salt_2025'), 0, 12); }
```
Token terlihat acak, tapi (a) deterministik dari username saja (tidak ada rahasia per-sesi),
dan (b) yang lebih penting untuk kelas kerentanan ini: **tidak divalidasi kepemilikannya** —
sama seperti Lab 1, siapa pun yang tahu tokennya bisa membuka dokumen itu.

**Payload:** salin salah satu token dari bagian "Recent team activity" (mis. token milik
`carol`), lalu `?token=<token carol>`.
**Kenapa berhasil:** "ID sulit ditebak" hanyalah *obscurity*, bukan *access control*. Begitu
token bocor lewat kanal lain (di sini: disimulasikan lewat feed aktivitas tim), dokumen bisa
dibuka siapa saja yang memilikinya — sama seperti kalau ID-nya angka urut.
**Hasil:** dokumen "Q3 salary review" milik user lain terbaca penuh.

---

## Lab 4 — IDOR di endpoint API/JSON (`lab4_idor_api.php`)

**Kode:**
```php
$user = find_user_by_id($db, $id);
echo $user ? json_encode($user) : json_encode(['error' => 'not found']);
```
Endpoint mengembalikan **seluruh** field record user (termasuk `password` dan `credit_card`)
untuk ID mana pun yang diminta, tanpa mengecek bahwa `$id` sama dengan user yang sedang login.

**Payload:** `lab4_idor_api.php?api=1&id=4` (langsung di address bar, atau lihat request
`fetch()` di tab Network lalu ubah parameternya).
**Kenapa berhasil:** endpoint API sering luput diaudit karena "cuma dipanggil dari JavaScript
kita sendiri" — padahal endpoint HTTP bisa diakses dan dipanggil langsung oleh siapa saja,
terlepas dari apakah ada tombol/UI yang mengarah ke sana.
**Hasil:** password dan nomor kartu kredit akun `admin` bocor dalam response JSON.

---

## Lab 5 — Mass Assignment (`lab5_mass_assignment.php`)

**Kode:**
```php
$u = array_merge($u, $_POST);
$u['id'] = $me['id'];
```
Seluruh isi `$_POST` di-merge ke record user, bukan hanya `full_name`/`email` yang ditampilkan
form.

**Payload (curl, ganti cookie session sesuai punyamu):**
```bash
curl -b "PHPSESSID=<session alice>" \
  -d "full_name=Alice&email=alice@corp.test&role=admin" \
  http://target/idor/lab5_mass_assignment.php
```
**Kenapa berhasil:** "bind semua field sekaligus" adalah pola umum di framework modern (ORM
`update($request->all())`, dsb.) yang nyaman tapi berbahaya kalau tidak dibatasi allowlist —
field apa pun yang ada di model bisa diubah lewat request, termasuk yang tidak pernah
dimaksudkan untuk bisa diedit user biasa.
**Hasil:** role akun `alice` berubah dari `user` menjadi `admin`.

---

## Ringkasan hasil akhir

| Lab | Teknik | Payload final | Bukti keberhasilan |
|---|---|---|---|
| 1 | IDOR read | `?id=5002` (login sbg alice) | Invoice milik bob terbaca |
| 2 | IDOR write | `user_id=4` di POST | Email admin berubah |
| 3 | IDOR unpredictable ID | Token bocor dari activity feed | Dokumen user lain terbaca |
| 4 | IDOR via API | `?api=1&id=4` | Password & kartu kredit admin bocor |
| 5 | Mass assignment | `role=admin` ekstra di POST | Role akun sendiri naik jadi admin |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Verifikasi kepemilikan objek di server pada **setiap** operasi baca/tulis, jangan percaya ID
  dari client begitu saja.
- ID acak/UUID bukan pengganti access control (Lab 3).
- Access control harus diterapkan konsisten di endpoint API, bukan hanya di halaman HTML (Lab 4).
- Gunakan allowlist field eksplisit untuk operasi update, jangan bind seluruh request body ke
  model (Lab 5).
