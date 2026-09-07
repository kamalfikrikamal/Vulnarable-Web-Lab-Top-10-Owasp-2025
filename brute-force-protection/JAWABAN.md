# Kunci Jawaban — Broken Brute-Force Protection Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.
> Password asli akun `admin`: **`Passw0rd!`**.

---

## Lab 1 — Tidak ada rate limiting (`lab1_no_rate_limit.php`)
**Kode:** endpoint login memproses setiap request tanpa counter/lockout/CAPTCHA apa pun.
**Langkah:** jalankan tool brute-force dengan wordlist default (sudah memuat `Passw0rd!`).
**Kenapa berhasil:** tidak ada mekanisme apa pun yang membatasi kecepatan atau jumlah percobaan.
**Hasil:** password `admin` ditemukan (`Passw0rd!`) dalam satu kali jalan tool.

---

## Lab 2 — XFF bypass (`lab2_xff_bypass.php`)
**Kode:** `effective_ip()` memakai `$_SERVER['HTTP_X_FORWARDED_FOR']` kalau ada, tanpa validasi
bahwa header itu benar-benar berasal dari proxy tepercaya.
**Payload:**
```bash
curl -X POST -H "X-Forwarded-For: 10.0.0.1" -d "username=admin&password=123456" http://target/lab2_xff_bypass.php
curl -X POST -H "X-Forwarded-For: 10.0.0.2" -d "username=admin&password=Passw0rd!" http://target/lab2_xff_bypass.php
```
**Kenapa berhasil:** header `X-Forwarded-For` sepenuhnya dikendalikan pengirim request kecuali
di-override oleh proxy tepercaya di depannya — server ini salah mengasumsikan nilai itu selalu
jujur.
**Hasil:** password ditemukan tanpa pernah kena limit 3x karena setiap request "datang dari IP
berbeda".

---

## Lab 3 — Case variation bypass (`lab3_case_variation_bypass.php`)
**Kode:** autentikasi pakai `strcasecmp()` (case-insensitive), tapi counter
`$db['attempts_by_username'][$username]` memakai `$username` apa adanya (case-sensitive) sebagai
key.
**Payload:** coba `admin`/salah x3, lalu `Admin`/salah x3, lalu `ADMIN`/salah x3, dst — masing2
dapat jatah 3x baru.
**Kenapa berhasil:** kunci penyimpanan counter tidak dinormalisasi sama seperti logic
autentikasi yang sebenarnya, sehingga variasi kapitalisasi memecah satu akun jadi banyak
"identitas" berbeda di mata sistem lockout.
**Hasil:** password ditemukan tanpa pernah benar-benar terkunci, hanya dengan memutar variasi
kapitalisasi.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Tidak ada rate limit | Password ditemukan sekali jalan |
| 2 | Spoof X-Forwarded-For | Limit per-IP tidak pernah tercapai |
| 3 | Variasi kapitalisasi username | Limit per-username tidak pernah tercapai |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Rate limiting + CAPTCHA di endpoint login.
- Jangan percaya X-Forwarded-For dari client tanpa proxy tepercaya di depannya.
- Normalisasi key counter keamanan agar konsisten dengan logic autentikasi.
