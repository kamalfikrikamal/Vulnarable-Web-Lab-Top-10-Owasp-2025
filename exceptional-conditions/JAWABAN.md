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

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Fail-open di catch block payment gateway | Order jadi `PAID` tanpa pembayaran nyata |
| 2 | Input tak tervalidasi memicu error bawaan PHP | Path file & nomor baris server bocor |
| 3 | Check-then-act tanpa locking (race condition) | Gift card di-redeem >1x, saldo dobel/triple |
| 4 | Fail-open di catch block pengecekan akses | Laporan gaji admin terbaca tanpa otorisasi valid |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Kegagalan layanan eksternal harus fail CLOSED (tahan/tolak) secara default, jangan fail-open.
- Validasi semua input sebelum dipakai di aritmatika/array lookup — tangani kasus ekstrem secara
  eksplisit.
- Matikan `display_errors` di production; log error di server, bukan tampilkan ke user.
- State-changing operations (apalagi bernilai uang) harus atomik (transaksi/row lock), bukan
  check-then-act dengan celah waktu.
- Jangan blanket-catch `Throwable` di kode otorisasi tanpa fallback deny-by-default yang eksplisit.
