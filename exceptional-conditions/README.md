# Exceptional Conditions Lab (Mishandling of Exceptional Conditions)

Aplikasi PHP sederhana yang mendemonstrasikan kondisi tak terduga — layanan eksternal timeout,
input di luar dugaan, dua request yang datang nyaris bersamaan, atau exception yang muncul di
tempat tak terduga — ditangani dengan cara yang justru membuka celah keamanan, alih-alih
ditolak/di-deny secara aman. Bagian dari **A10: Mishandling of Exceptional Conditions**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Fail-open saat payment gateway timeout | `lab1_fail_open_payment_timeout.php` |
| 2 | Pesan error bawaan PHP bocorkan path/baris internal | `lab2_error_message_info_leak.php` |
| 3 | Race condition pada redeem gift card (double-spend) | `lab3_race_condition_giftcard.php` |
| 4 | Fail-open di dalam catch block pengecekan akses | `lab4_failopen_catch_block.php` |
| 5 | Retry non-idempotent setelah timeout menyebabkan double charge | `lab5_duplicate_charge_retry.php` |
| 6 | Transaksi multi-langkah tanpa rollback (partial failure) | `lab6_no_rollback_partial_failure.php` |
| 7 | Tipe input tak terduga meloloskan filter `in_array()` | `lab7_type_confusion_filter_bypass.php` |
| 8 | Response API gagal di-parse, dianggap aman lewat default `??` | `lab8_malformed_response_fail_open.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/exceptcond/`, atau lewat Portal → **A10: Mishandling of Exceptional
Conditions** → **Exceptional Conditions Lab**.

## Panduan tiap lab

### Lab 1 — Fail-open saat payment gateway timeout
Checklist "Simulasikan payment gateway timeout/down" lalu klik **Checkout**. Fungsi
`verify_payment()` melempar Exception (mensimulasikan koneksi ke payment gateway eksternal yang
timeout). Kode pemanggilnya membungkus ini dalam `try/catch`, tapi di `catch`-nya malah
menganggap pembayaran **berhasil** (`$verified = true;`) alih-alih menahan/menolak order. Lihat
tabel "Riwayat Order" — order akan tetap berstatus `PAID` walaupun kamu tidak pernah memberi
konfirmasi pembayaran nyata apa pun, dan bahkan secara eksplisit membuat gateway-nya gagal.

### Lab 2 — Pesan error bocorkan detail internal
Form "Cek Diskon" sama sekali tidak punya validasi input. Coba:
- `jumlah_item = 0` → `DivisionByZeroError` yang menampilkan path file & nomor baris persis.
- `jumlah_item = 999` (atau angka negatif) → `Warning: Undefined array key` (tabel diskon cuma
  didefinisikan untuk 1–5), juga membocorkan path & baris.
- Form kedua "Verifikasi Ulang" memanggil fungsi `calc(int $harga, int $jumlah): float` yang
  di-type-hint ketat (`declare(strict_types=1)`) tanpa validasi/cast apa pun — masukkan `abc`
  (atau angka apa pun, karena data form selalu string) untuk melihat `TypeError` penuh lengkap
  dengan stack trace dan path file.

### Lab 3 — Race condition pada gift card
Halaman menampilkan gift card `GIFT100K` (saldo Rp100.000, `redeemed = false`). Tombol "Redeem
Gift Card" mengecek status dulu, lalu **tidur 300ms** (mensimulasikan panggilan ke layanan
ledger), baru menandai `redeemed = true` dan mengkredit saldo. Kirim beberapa request nyaris
bersamaan:

```bash
curl -s -X POST http://localhost:8079/exceptcond/lab3_race_condition_giftcard.php -d "action=redeem" & \
curl -s -X POST http://localhost:8079/exceptcond/lab3_race_condition_giftcard.php -d "action=redeem" & \
wait
```

Atau lebih sederhana: buka dua tab browser ke halaman ini dan klik tombol **Redeem** di kedua tab
secepat mungkin. Cek tabel "Log Redemption" — gift card yang sama berhasil di-redeem lebih dari
sekali, saldo wallet bertambah lebih dari sekali lipat nilai gift card. Klik "Reset State Lab"
untuk mengulang.

### Lab 4 — Fail-open di dalam catch block
Kamu login sebagai `alice`. `?report_id=1` (laporan milik sendiri) berhasil dibuka;
`?report_id=2` (laporan gaji admin) ditolak dengan benar — membuktikan pengecekan kepemilikan
memang bekerja untuk kasus normal. Sekarang coba `?report_id=abc` atau `?report_id[]=1` — kedua
input aneh ini membuat fungsi `user_can_access_report()` melempar `TypeError` di tengah proses
pengecekan. Kode pemanggilnya menangkap `\Throwable` apa pun dan, alih-alih menolak akses,
malah meloloskannya (`$can_access = true; // TODO: tangani error dengan benar nanti`) — laporan
gaji admin yang sensitif pun berhasil dibuka tanpa verifikasi kepemilikan yang valid.

### Lab 5 — Retry non-idempotent setelah timeout menyebabkan double charge
Order demo #7001 (Rp450.000). Klik "Bayar (jalur normal)" dulu — satu baris charge tercatat.
Reset state lab, lalu klik "Bayar (simulasikan response timeout, tapi charge tetap diproses di
backend)" — fungsi `process_payment()` **selalu** mencatat charge nyata di `$db['charges']`
SEBELUM melempar Exception yang mensimulasikan response yang hilang di jalan. Klik tombol "Coba
Lagi" yang muncul — form ini mengirim ulang order/jumlah yang PERSIS SAMA, tanpa idempotency key
dan tanpa pengecekan "apakah order ini sudah pernah dibayar?". Lihat tabel "Charge Tercatat untuk
Order Ini": setiap klik "Coba Lagi" menambah SATU baris charge baru untuk order yang sama — dua
kali retry = tiga charge nyata untuk satu pembayaran yang seharusnya cuma sekali.

### Lab 6 — Transaksi multi-langkah tanpa rollback
Saldo awal: Akun A Rp1.000.000, Akun B (ID 1002) Rp500.000. Form "Transfer Dana" memotong saldo
Akun A di **step 1** dan langsung menyimpannya (`save_db()`), baru di **step 2** mencoba
memvalidasi & mengkredit akun tujuan. Transfer normal (akun tujuan = `1002`) membuat kedua step
sukses. Sekarang reset state lab, lalu transfer lagi tapi isi "Akun Tujuan" dengan nilai tidak
valid (kosong, `abc`, atau ID yang tidak dikenal seperti `9999`) — step 2 melempar Exception.
Karena tidak ada transaksi/rollback yang membungkus kedua step, saldo Akun A **tetap berkurang**
walaupun step 2 gagal dan saldo Akun B tidak berubah sama sekali — uang hilang dari sistem. Beda
dengan Lab 3: ini murni bug sekuensial, satu request normal saja sudah cukup untuk memicunya,
tidak butuh dua request bersamaan/race condition sama sekali.

### Lab 7 — Tipe input tak terduga meloloskan filter `in_array()`
Filter "Display Name" menolak nilai di blocklist (`admin`, `root`, `superuser`) lewat
`in_array($_POST['display_name'], $BLOCKED_VALUES)`. Form A mengirim `display_name=admin`
sebagai string biasa — ditolak dengan benar. Form B mengirim field yang sama tapi bernama
`display_name[]` berisi nilai yang PERSIS SAMA (`admin`) — karena PHP menerimanya sebagai array
`['admin']`, bukan string, `in_array()` (perbandingan non-strict array vs string) tidak pernah
cocok terhadap satu pun entri blocklist, sehingga nilai yang identik ini lolos begitu saja dan
tersimpan sebagai display name baru. Setara lewat curl:
```bash
curl -s -X POST http://localhost:8079/exceptcond/lab7_type_confusion_filter_bypass.php --data-urlencode "display_name[]=admin"
```

### Lab 8 — Response API gagal di-parse, dianggap aman lewat default `??`
Order demo sengaja dibuat mencurigakan (nominal Rp75.000.000, akun berumur 0 hari). Klik
"Checkout (fraud-check normal)" — `check_fraud_score()` mengembalikan `['fraud_score' => 85,
'flagged' => true]`, dan order ini DIBLOKIR dengan benar. Reset, lalu klik "Checkout (simulasikan
response fraud-check API yang rusak/tidak terduga)" — API-nya BUKAN timeout, tetap menjawab
dengan JSON valid, tapi berbentuk `['status' => 'error', 'message' => '...']` (tidak ada key
`fraud_score`/`flagged` sama sekali). Kode pemanggil memakai
`$fraud_result['flagged'] ?? false` — karena key-nya hilang, `??` diam-diam menganggap "tidak
di-flag" = aman, dan order yang SAMA-SAMA mencurigakan ini lolos checkout begitu saja padahal
fraud-check-nya tidak pernah benar-benar menjawab.

## Mitigasi

- **Kegagalan layanan eksternal harus fail CLOSED** (tahan/tolak) secara default. Jangan pernah
  fail-open (mengasumsikan sukses) hanya supaya user tidak terganggu oleh gangguan sesaat.
- Validasi/sanitasi semua input sebelum dipakai dalam aritmatika/lookup array, sehingga kasus
  ekstrem (nol, angka sangat besar/negatif, tipe salah, kosong) ditangani secara eksplisit —
  bukan dibiarkan jatuh ke perilaku error default bahasa pemrograman.
- Matikan tampilan error verbose (`display_errors`) di production, dan catat error di sisi
  server (log) — jangan pernah menampilkan path file/stack trace mentah ke pengguna.
- Operasi yang mengubah state (apalagi yang bernilai uang) harus **atomik** — pakai transaksi
  database dengan row lock (`SELECT ... FOR UPDATE`) atau mekanisme locking lain, bukan
  "cek dulu, baru tulis" (check-then-act) dengan celah waktu di antaranya.
- Berhati-hatilah dengan cakupan exception handling di kode keamanan-kritis — jangan pernah
  blanket-catch `Throwable` di sekitar keputusan otorisasi tanpa fallback eksplisit yang
  **menolak akses secara default** (deny by default) ketika terjadi error tak terduga.
- **Pakai idempotency key** untuk operasi yang diulang (retry) tapi TIDAK idempotent (mis.
  charge pembayaran): setiap upaya (termasuk retry setelah timeout/koneksi putus) mengirim key
  unik yang sama untuk operasi logis yang sama, supaya server bisa mengembalikan hasil yang sudah
  ada alih-alih memproses ulang dari nol. "Tidak ada response" berarti hasilnya TIDAK DIKETAHUI,
  bukan otomatis "pasti gagal, aman untuk dicoba lagi".
- Proses multi-langkah yang mengubah state (apalagi uang) butuh **transaksi database sungguhan**
  (`BEGIN`/`COMMIT`/`ROLLBACK`) yang membungkus semua langkah, atau compensating action eksplisit
  yang membatalkan langkah sebelumnya kalau ada langkah belakangan yang gagal — jangan biarkan
  kegagalan di tengah proses meninggalkan sistem dalam keadaan tanggung/tidak konsisten.
- Validasi TIPE input (`is_string()`, dst.) sebelum menjalankannya lewat logika
  blocklist/filter/validasi apa pun — jangan berasumsi field request selalu berupa scalar string,
  karena PHP menerima field apa pun sebagai array hanya dengan mengubah nama field jadi `nama[]`.
- Jangan biarkan fallback `??`/nilai default (`false`, `0`, `[]`) diam-diam berperan sebagai
  "sudah diverifikasi aman" pada field yang menentukan keputusan keamanan (fraud check, hasil
  otorisasi, dsb) — kalau response dari dependensi eksternal tidak sesuai bentuk/shape yang
  diharapkan, validasi eksplisit dan fail CLOSED, jangan diam-diam diperlakukan sebagai "aman".
