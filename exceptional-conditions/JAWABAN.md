# Kunci Jawaban — Exceptional Conditions Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Fail-open saat payment gateway timeout (`lab1_fail_open_payment_timeout.php`)
**Kode:**
```php
try {
    $verified = verify_payment($simulate_down);
} catch (\Exception $e) {
    // asumsikan berhasil kalau gateway sedang bermasalah, supaya user tidak terganggu
    $verified = true;
    ...
}
```
**Payload:** centang "Simulasikan payment gateway timeout/down", klik Checkout.
**Kenapa berhasil:** `verify_payment()` melempar Exception untuk mensimulasikan gateway
eksternal yang timeout. Alih-alih menahan/menolak order saat verifikasi gagal (default yang
aman), catch block-nya mengasumsikan pembayaran berhasil begitu saja.
**Hasil:** order tercatat berstatus `PAID` di "Riwayat Order" walaupun trainee secara eksplisit
membuat gateway pembayaran gagal, dan tidak pernah memberi konfirmasi pembayaran nyata sama
sekali.

---

## Lab 2 — Pesan error bocorkan detail internal (`lab2_error_message_info_leak.php`)
**Kode:** tidak ada validasi input sama sekali sebelum `$harga / $jumlah_item` dan
`$discount_tiers[$jumlah_item]`; ditambah `declare(strict_types=1)` + fungsi
`calc(int $harga, int $jumlah): float` yang dipanggil langsung dengan data `$_POST` mentah.
**Payload:**
- `jumlah_item = 0` → `DivisionByZeroError`.
- `jumlah_item = 999` atau negatif → `Warning: Undefined array key`.
- Form "Verifikasi Ulang" dengan `harga_strict = abc` → `TypeError` penuh.
**Kenapa berhasil:** `display_errors` aktif (mensimulasikan server produksi yang lupa
mematikannya) dan tidak ada `try/catch`/validasi apa pun terhadap input di luar dugaan. PHP
menampilkan pesan error bawaannya apa adanya, termasuk path absolut file di server dan nomor
baris kode yang persis.
**Hasil:** path lengkap seperti `/var/www/html/lab2_error_message_info_leak.php on line N`
(atau path setara di lingkungan lokal) terlihat oleh siapa pun yang mengirim input tak terduga
— tidak butuh kredensial apa pun.

---

## Lab 3 — Race condition pada gift card (`lab3_race_condition_giftcard.php`)
**Kode:** cek `if ($card['redeemed']) die(...)`, lalu `usleep(300000)`, BARU kemudian menandai
`redeemed = true` dan mengkredit saldo — dua langkah terpisah dengan jeda 300ms di antaranya,
bukan satu operasi atomik.
**Payload:**
```bash
curl -s -X POST .../lab3_race_condition_giftcard.php -d "action=redeem" & \
curl -s -X POST .../lab3_race_condition_giftcard.php -d "action=redeem" & \
wait
```
atau dua tab browser, klik "Redeem" di keduanya nyaris bersamaan.
**Kenapa berhasil:** kedua request membaca `redeemed = false` di step 1 sebelum salah satu dari
mereka sempat menuliskan `redeemed = true` di step 3 (jeda 300ms melebarkan window balapan ini
sampai reliably ke-hit oleh request biasa). Tidak ada locking/transaksi yang mengunci baris gift
card selama proses ini.
**Hasil:** gift card senilai Rp100.000 berhasil di-redeem lebih dari sekali — "Log Redemption"
menampilkan >1 baris untuk kartu yang sama, dan saldo wallet bertambah lebih dari sekali lipat
nilai aslinya (terverifikasi: 3 request paralel → saldo Rp300.000, 3 baris log).

---

## Lab 4 — Fail-open di dalam catch block (`lab4_failopen_catch_block.php`)
**Kode:**
```php
try {
    $can_access = user_can_access_report($report_id_raw);
} catch (\Throwable $e) {
    // TODO: tangani error dengan benar nanti
    $can_access = true;
}
```
**Payload:** `?report_id=abc` atau `?report_id[]=1`.
**Kenapa berhasil:** `user_can_access_report(int $report_id)` mensyaratkan parameter bertipe
`int`. String non-numerik (`abc`) atau array (dari `report_id[]=1`) tidak bisa di-coerce ke int
oleh PHP, sehingga melempar `TypeError` PERSIS di titik pemanggilan — sebelum baris pengecekan
kepemilikan sempat dieksekusi. `catch (\Throwable $e)` menangkap error tak terduga ini, tapi
fallback-nya meloloskan akses alih-alih menolaknya. Sebagai perbandingan: `?report_id=2` (laporan
admin, ID valid) DITOLAK dengan benar — membuktikan pengecekan normal memang berfungsi; hanya
jalur error yang bocor.
**Hasil:** "Laporan Gaji Karyawan (RAHASIA)" milik admin berhasil dibuka oleh user `alice` tanpa
verifikasi kepemilikan yang valid sama sekali.

---

## Lab 5 — Retry non-idempotent setelah timeout menyebabkan double charge (`lab5_duplicate_charge_retry.php`)
**Kode:**
```php
function process_payment($order_id, $amount, $simulate_timeout) {
    $db = load_db();
    $db['charges'][] = ['order_id' => $order_id, 'amount' => $amount, 'time' => ...];
    save_db($db);              // backend SELALU mencatat charge, apa pun hasilnya
    if ($simulate_timeout) {
        throw new Exception('Tidak ada response dari payment processor...');
    }
    return true;
}
// caller: retry manual via tombol "Coba Lagi", tanpa idempotency key, tanpa cek status sebelumnya
```
**Payload:** klik "Bayar (simulasikan response timeout...)" lalu klik "Coba Lagi" satu kali atau lebih.
**Kenapa berhasil:** `process_payment()` selalu menulis charge ke `$db['charges']` SEBELUM
melempar exception — persis mensimulasikan dunia nyata di mana request sudah diproses backend tapi
response-nya hilang di jalan pulang. Kode caller memperlakukan "tidak ada response" sebagai sinyal
untuk retry dari nol, memanggil ulang `process_payment()` dengan order id & amount yang PERSIS
SAMA, tanpa idempotency key apa pun yang bisa dipakai server untuk mengenali ini sebagai upaya
ulang dari operasi yang SAMA.
**Hasil:** tabel "Charge Tercatat untuk Order Ini" menampilkan >1 baris charge untuk order #7001
yang sama — satu klik "Bayar" + satu klik "Coba Lagi" = 2 charge nyata Rp450.000 (total
Rp900.000) untuk satu pembayaran yang seharusnya cuma sekali.

---

## Lab 6 — Transaksi multi-langkah tanpa rollback (`lab6_no_rollback_partial_failure.php`)
**Kode:**
```php
$db['account_a_balance'] -= $amount;
save_db($db);                              // step 1: commit LANGSUNG, tidak bersyarat
try {
    if (!is_numeric($dest_raw) || (int)$dest_raw !== ACCOUNT_B_ID) {
        throw new Exception("Akun tujuan tidak valid/tidak ditemukan.");
    }
    $db['account_b_balance'] += $amount;
    save_db($db);                          // step 2
} catch (\Exception $e) {
    // tidak ada rollback saldo akun A di sini
}
```
**Payload:** form Transfer dengan "Akun Tujuan" diisi nilai tidak valid, mis. kosong, `abc`, atau `9999` (akun tujuan valid = `1002`).
**Kenapa berhasil:** step 1 (potong saldo akun A) dan step 2 (kredit akun tujuan) adalah dua
operasi terpisah tanpa transaksi/compensating rollback pembungkus. Step 1 sudah ter-`save_db()`
sebelum step 2 sempat divalidasi — begitu step 2 melempar exception (validasi akun tujuan gagal),
tidak ada mekanisme apa pun yang mengembalikan saldo akun A yang sudah terlanjur dipotong.
**Hasil:** saldo Akun A berkurang (mis. dari Rp1.000.000 jadi Rp900.000 untuk transfer
Rp100.000) sementara saldo Akun B TIDAK berubah sama sekali (tetap Rp500.000) — uang senilai
Rp100.000 lenyap dari sistem, dan "Log Transfer" mencatat status `GAGAL di step 2 ... (saldo akun
A TIDAK di-rollback!)`. Berbeda dari Lab 3, ini terjadi dari SATU request biasa saja — tidak
butuh race condition/dua request bersamaan.

---

## Lab 7 — Tipe input tak terduga meloloskan filter `in_array()` (`lab7_type_confusion_filter_bypass.php`)
**Kode:**
```php
$BLOCKED_VALUES = ['admin', 'root', 'superuser'];
if (in_array($_POST['display_name'], $BLOCKED_VALUES)) {
    // ditolak
} else {
    $db['profile']['display_name'] = $_POST['display_name']; // disimpan apa adanya
}
```
**Payload:** `display_name=admin` (Form A, ditolak) vs. `display_name[]=admin` (Form B, lolos) — atau via curl:
```bash
curl -s -X POST .../lab7_type_confusion_filter_bypass.php --data-urlencode "display_name[]=admin"
```
**Kenapa berhasil:** kode tidak pernah memvalidasi `is_string($_POST['display_name'])` sebelum
menjalankannya lewat `in_array()`. Saat field dikirim sebagai `display_name[]`, PHP membentuk
`$_POST['display_name']` jadi array `['admin']`, bukan string `'admin'`. `in_array()` dengan
perbandingan non-strict TIDAK PERNAH menganggap array sama dengan string apa pun di
haystack-nya — jadi filter blocklist ini otomatis selalu meloloskan input berbentuk array, tidak
peduli isinya, walaupun isinya PERSIS SAMA dengan nilai yang seharusnya diblokir.
**Hasil:** `display_name=admin` (string) ditolak dengan benar oleh Form A, sementara
`display_name[]=admin` (array, isi identik) berhasil tersimpan sebagai display name baru lewat
Form B — terbukti dari badge "TERSIMPAN SEBAGAI ARRAY" dan pesan "berhasil diubah jadi 'admin'
(dikirim sebagai ARRAY, bukan string biasa!)".

---

## Lab 8 — Response fraud-check gagal di-parse dianggap aman (`lab8_malformed_response_fail_open.php`)
**Kode:**
```php
function check_fraud_score($order_details, $simulate_malformed) {
    if ($simulate_malformed) {
        return ['status' => 'error', 'message' => 'upstream fraud-check service unavailable'];
    }
    return ['fraud_score' => 85, 'flagged' => true];
}
...
$flagged = $fraud_result['flagged'] ?? false;   // BUG: key hilang -> dianggap "aman"
```
**Payload:** klik "Checkout (simulasikan response fraud-check API yang rusak/tidak terduga)" pada order demo yang sengaja mencurigakan (Rp75.000.000, akun umur 0 hari).
**Kenapa berhasil:** ini bukan timeout/exception (beda dari Lab 1) — API-nya tetap menjawab
dengan JSON valid, tapi berbentuk (shape) yang tidak dikenali kode pemanggil (tidak ada key
`flagged`/`fraud_score` sama sekali). Operator `??` memperlakukan key yang hilang ini sama persis
dengan "sudah dicek dan memang tidak di-flag", padahal makna sebenarnya adalah "pengecekan ini
tidak pernah menghasilkan jawaban yang valid" — dua kondisi yang jauh berbeda tapi diperlakukan
identik.
**Hasil:** untuk order yang SAMA-SAMA mencurigakan, jalur normal (`check_fraud_score()`
mengembalikan `fraud_score: 85, flagged: true`) diblokir dengan benar, tapi jalur
response-malformed lolos checkout dengan pesan "Pesanan diproses, TIDAK terdeteksi sebagai
fraud" — dibuktikan lewat tabel "Riwayat Order" yang menampilkan baris `DIPROSES` dengan
`fraud_score: (tidak ada)` dan raw response `{"status":"error","message":"upstream fraud-check
service unavailable"}`.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Fail-open di catch block payment gateway | Order jadi `PAID` tanpa pembayaran nyata |
| 2 | Input tak tervalidasi memicu error bawaan PHP | Path file & nomor baris server bocor |
| 3 | Check-then-act tanpa locking (race condition) | Gift card di-redeem >1x, saldo dobel/triple |
| 4 | Fail-open di catch block pengecekan akses | Laporan gaji admin terbaca tanpa otorisasi valid |
| 5 | Retry non-idempotent tanpa idempotency key | >1 charge nyata untuk satu order yang sama |
| 6 | Multi-step transaction tanpa rollback | Saldo akun A berkurang, akun B tidak berubah |
| 7 | Type confusion pada filter `in_array()` | `display_name[]=admin` lolos, `display_name=admin` ditolak |
| 8 | Default `??` menutupi response API yang gagal di-parse | Checkout order mencurigakan lolos tanpa fraud check nyata |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Kegagalan layanan eksternal harus fail CLOSED (tahan/tolak) secara default, jangan fail-open.
- Validasi semua input sebelum dipakai di aritmatika/array lookup — tangani kasus ekstrem secara
  eksplisit.
- Matikan `display_errors` di production; log error di server, bukan tampilkan ke user.
- State-changing operations (apalagi bernilai uang) harus atomik (transaksi/row lock), bukan
  check-then-act dengan celah waktu.
- Jangan blanket-catch `Throwable` di kode otorisasi tanpa fallback deny-by-default yang eksplisit.
- Pakai idempotency key untuk operasi non-idempotent yang bisa di-retry (mis. charge pembayaran) —
  "tidak ada response" berarti TIDAK DIKETAHUI, bukan otomatis "aman untuk diulang dari nol".
- Bungkus proses multi-langkah yang mengubah state dengan transaksi database sungguhan atau
  compensating rollback eksplisit, supaya kegagalan di tengah proses tidak meninggalkan sistem
  dalam keadaan tanggung/tidak konsisten.
- Validasi TIPE input (`is_string()`, dst.) sebelum menjalankannya lewat logika blocklist/filter —
  jangan asumsikan field request selalu scalar string.
- Jangan biarkan fallback `??`/nilai default diam-diam berperan sebagai "sudah diverifikasi aman"
  pada field keamanan-kritis — validasi eksplisit bentuk/shape response dari dependensi eksternal,
  dan fail CLOSED kalau tidak sesuai harapan.
