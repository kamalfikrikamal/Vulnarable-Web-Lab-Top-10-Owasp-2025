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
