# Broken Brute-Force Protection Lab (Authentication Failures)

Aplikasi PHP sederhana yang mendemonstrasikan tiga cara umum proteksi brute-force gagal
melindungi proses login, bagian dari **A07: Authentication Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Tidak ada rate limiting sama sekali | `lab1_no_rate_limit.php` |
| 2 | Lockout berbasis IP, bypass via `X-Forwarded-For` | `lab2_xff_bypass.php` |
| 3 | Lockout bypass via variasi kapitalisasi username | `lab3_case_variation_bypass.php` |

Target: akun `admin` dengan password tidak diketahui (`Passw0rd!`, untuk keperluan verifikasi
trainer — jangan bocorkan ke peserta).

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/bruteforce/`, atau lewat Portal → **A07: Authentication Failures**
→ **Broken Brute-Force Protection**.

## Panduan tiap lab

### Lab 1 — Tidak ada rate limiting
Gunakan tool brute-force di halaman (wordlist sudah terisi contoh, termasuk password asli) —
klik jalankan, semua kandidat dicoba tanpa hambatan.

### Lab 2 — Bypass lewat X-Forwarded-For
```bash
curl -X POST -H "X-Forwarded-For: 1.1.1.1" -d "username=admin&password=123456" \
  http://localhost:8079/bruteforce/lab2_xff_bypass.php
```
Ganti nilai `X-Forwarded-For` di tiap request untuk mendapat "IP" baru setiap kali, menghindari
limit 3x percobaan per IP.

### Lab 3 — Bypass lewat variasi kapitalisasi
Coba kandidat password berbeda dengan mengganti kapitalisasi username tiap 3 percobaan
(`admin` → `Admin` → `ADMIN` → ...) — counter selalu "reset" karena disimpan per string persis,
padahal proses autentikasinya sendiri case-insensitive.

## Mitigasi (untuk didiskusikan setelah lab)
- Terapkan **rate limiting** di endpoint login (mis. maksimal N percobaan per menit), dan
  tambahkan **CAPTCHA** setelah beberapa kali gagal untuk menghambat otomasi (Lab 1).
- **Jangan pernah** mempercayai header `X-Forwarded-For`/`X-Real-IP` dari client secara
  langsung untuk keputusan keamanan — hanya percaya nilai yang di-set oleh reverse proxy yang
  benar-benar kamu kontrol (mis. dengan `set_real_ip_from` di nginx, bukan meneruskan header
  yang datang dari luar apa adanya) (Lab 2).
- **Normalisasi** key yang dipakai untuk counter keamanan (mis. `strtolower($username)`)
  supaya konsisten dengan logic autentikasi yang sebenarnya (Lab 3).
- Kombinasikan lockout berbasis **IP DAN username** sekaligus, bukan salah satu saja, supaya
  lebih sulit dihindari dari kedua sisi.
