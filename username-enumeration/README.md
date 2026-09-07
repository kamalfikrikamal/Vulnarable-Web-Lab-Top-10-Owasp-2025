# Username Enumeration Lab (Authentication Failures)

Aplikasi PHP sederhana yang mendemonstrasikan empat kanal umum kebocoran validitas username,
mengikuti kategori PortSwigger Web Security Academy "Authentication" — bagian dari
**A07: Authentication Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Pesan error berbeda | `lab1_different_message.php` |
| 2 | Response nyaris identik (beda 1 byte) | `lab2_subtle_difference.php` |
| 3 | Perbedaan waktu respons | `lab3_response_timing.php` |
| 4 | Account lockout membocorkan validitas | `lab4_account_lockout.php` |

Akun demo: `alice/Summer2024!`, `admin/admin123`.

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/userenum/`, atau lewat Portal → **A07: Authentication Failures** →
**Username Enumeration**.

## Panduan tiap lab

### Lab 1 — Pesan berbeda
Bandingkan pesan untuk `randomuser123` ("User tidak ditemukan.") vs `alice` + password salah
("Password salah.").

### Lab 2 — Beda 1 byte
Kedua pesan terlihat identik di layar. Gunakan "View Page Source" atau `curl -s ... | wc -c`
untuk membandingkan panjang response byte demi byte antara username valid vs tidak valid.

### Lab 3 — Timing
Ukur waktu respons: username valid (`alice` + password salah) memicu delay ~0.3 detik,
username tidak valid langsung dijawab instan. Waktu respons ditampilkan langsung di halaman
untuk lab ini.

### Lab 4 — Lockout
Salah password 3x untuk `alice` → muncul pesan lockout. Ulangi 3x untuk username acak → pesan
lockout tidak pernah muncul, membocorkan bahwa username itu tidak terdaftar.

## Mitigasi (untuk didiskusikan setelah lab)
- Gunakan **satu pesan generik** yang identik persis (byte demi byte) untuk semua kegagalan
  login, apa pun penyebabnya: `"Invalid username or password"` (Lab 1 & 2).
- Samakan **waktu respons** untuk kasus valid maupun tidak valid — mis. tetap jalankan
  `password_verify()` terhadap hash dummy meski username tidak ditemukan, supaya waktu proses
  konsisten (Lab 3).
- Terapkan rate limiting / lockout berdasarkan **kombinasi IP + username**, dan pastikan
  perilakunya (termasuk pesan yang ditampilkan) **identik** baik username itu valid maupun tidak
  (Lab 4) — jangan biarkan mekanisme keamanan itu sendiri menjadi oracle baru.
- Pertimbangkan CAPTCHA atau proof-of-work setelah beberapa kali percobaan gagal untuk
  memperlambat enumerasi otomatis tanpa membocorkan informasi tambahan.
