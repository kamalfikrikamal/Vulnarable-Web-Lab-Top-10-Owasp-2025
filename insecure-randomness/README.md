# Insecure Randomness Lab (Cryptographic Failures)

Aplikasi PHP sederhana yang mendemonstrasikan token/ID/kode yang seharusnya tidak bisa ditebak,
tapi dibangkitkan dengan cara yang deterministik atau berpola — bagian dari
**A04: Cryptographic Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Password reset token bisa diprediksi | `lab1_predictable_reset_token.php` |
| 2 | API key sekuensial | `lab2_sequential_api_key.php` |
| 3 | OTP 2FA bisa diprediksi (seed PRNG publik) | `lab3_predictable_otp.php` |
| 4 | Kode kupon bisa ditebak | `lab4_predictable_coupon.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/randomness/`, atau lewat Portal → **A04: Cryptographic Failures** →
**Insecure Randomness**.

## Panduan tiap lab

### Lab 1 — Password reset token
Token = `substr(md5($username . time()), 0, 16)`. Request reset untuk `admin`, catat timestamp
yang ditampilkan, hitung ulang tokennya (`php -r "echo substr(md5('admin'.TS),0,16);"`), pakai
hasilnya di form konfirmasi untuk "reset" password admin.

### Lab 2 — API key sekuensial
API key kamu `1042`. Coba `?key=1043` untuk menemukan API key admin.

### Lab 3 — OTP bisa diprediksi
OTP = `mt_srand($ts); mt_rand(100000, 999999);`. Generate OTP, catat timestamp-nya, gunakan tool
"Predict OTP" di halaman yang sama untuk menghitung ulang nilai persis yang sama, lalu verify.

### Lab 4 — Kode kupon bisa ditebak
Kupon = `SAVE` + nomor order 4 digit. Checkout sekali untuk lihat pola (`SAVE1001`), tebak
`SAVE1000`/`SAVE1002` untuk redeem kupon milik order lain.

## Mitigasi (untuk didiskusikan setelah lab)
- Gunakan **CSPRNG** (cryptographically secure pseudo-random number generator) untuk apa pun
  yang berfungsi sebagai secret/token: `random_bytes()` atau `random_int()` di PHP (bukan
  `rand()`/`mt_rand()`, dan jangan pernah menyeed PRNG dengan nilai yang bisa ditebak seperti
  `time()`).
- ID yang berfungsi sebagai kontrol akses (API key, session token) tidak boleh sekuensial —
  pakai UUID v4 atau token acak panjang dari CSPRNG, **dan** tetap terapkan access control di
  belakangnya (ID acak saja tidak cukup, lihat lab IDOR di kategori Broken Access Control).
- OTP/kode verifikasi harus dibangkitkan dari sumber entropi asli (CSPRNG), disimpan di server
  (bukan bisa dihitung ulang dari input publik), dan tetap dilindungi rate limiting.
- Kode promo/kupon yang bernilai uang harus memakai token acak panjang, bukan format yang bisa
  ditebak dari data bisnis yang terlihat publik (nomor order, timestamp, dst).
