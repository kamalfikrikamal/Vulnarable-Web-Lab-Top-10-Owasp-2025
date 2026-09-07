# Password Reset Flaws Lab (Authentication Failures)

Aplikasi PHP sederhana yang mendemonstrasikan empat kesalahan umum pada alur reset password,
mengikuti kategori PortSwigger Web Security Academy "Authentication" — bagian dari
**A07: Authentication Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Token bocor lewat tracking pixel email | `lab1_token_leak_email.php` |
| 2 | Token bisa dipakai berulang kali | `lab2_token_reuse.php` |
| 3 | Kode reset pendek tanpa rate limiting | `lab3_brute_forceable_code.php` |
| 4 | Password reset poisoning lewat Host header | `lab4_host_header_poisoning.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/pwreset/`, atau lewat Portal → **A07: Authentication Failures** →
**Password Reset Flaws**.

## Panduan tiap lab

### Lab 1 — Token leak via tracking pixel
Request reset untuk `admin`, lihat "Simulasi Inbox" — email memuat gambar 1x1 dari domain
analytics pihak ketiga yang membawa token yang sama dengan link reset. Salin token dari
"Log Tracker Pihak Ketiga", pakai di form Confirm Reset.

### Lab 2 — Token reuse
Request reset, catat token yang ditampilkan (untuk kebutuhan lab), lalu gunakan token yang sama
berkali-kali di form Confirm Reset — selalu berhasil.

### Lab 3 — Brute-forceable code
Request reset, lalu klik "Brute Force Kode" — tool mencoba seluruh 10.000 kombinasi 4 digit
dan menemukan kodenya dalam hitungan detik.

### Lab 4 — Host header poisoning
```bash
curl -X POST -H "Host: attacker-evil.test" -d "username=admin&request_reset=1" \
  http://localhost:8079/pwreset/lab4_host_header_poisoning.php
```
Lihat "Simulasi Inbox" — link reset yang dihasilkan menunjuk ke `attacker-evil.test`, bukan
domain aplikasi yang sah.

## Mitigasi (untuk didiskusikan setelah lab)
- Hindari menyematkan token sensitif di URL resource pihak ketiga (tracking pixel, analytics)
  dalam email transaksional (Lab 1) — atau pastikan token reset **berumur sangat pendek & sekali
  pakai**, sehingga kebocoran seperti ini tidak lagi berguna setelah dipakai sekali.
- **Selalu invalidate token/kode segera setelah dipakai** (hapus dari store atau tandai
  `used = true` dan cek flag itu) (Lab 2).
- Kode verifikasi pendek harus **dikombinasikan dengan rate limiting/lockout** yang ketat pada
  langkah konfirmasi — atau gunakan token panjang dari CSPRNG sebagai gantinya, bukan kode
  pendek yang nyaman diketik manusia (Lab 3).
- **Jangan pernah** membangun URL absolut (terutama yang dikirim lewat kanal di luar kendali
  aplikasi, seperti email) dari header `Host` yang dikirim client — gunakan domain tetap yang
  dikonfigurasi di server (Lab 4).
- Terapkan waktu kedaluwarsa pendek untuk semua token/kode reset (mis. 15 menit).
