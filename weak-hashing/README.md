# Weak Password Hashing Lab (Cryptographic Failures)

Aplikasi PHP sederhana yang mendemonstrasikan tiga cara umum penyimpanan password gagal
melindungi data begitu database bocor, bagian dari **A04: Cryptographic Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Penyimpanan password plaintext | `lab1_plaintext.php` |
| 2 | Hash MD5 tanpa salt (crackable) | `lab2_unsalted_md5.php` |
| 3 | "Enkripsi" yang sebenarnya cuma encoding (base64) | `lab3_reversible_encoding.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/hashing/`, atau lewat Portal → **A04: Cryptographic Failures** →
**Weak Password Hashing**.

## Panduan tiap lab

### Lab 1 — Plaintext storage
Buka lab, lihat dump tabel `users` — semua password terbaca langsung tanpa proses apa pun.

### Lab 2 — Unsalted MD5
Gunakan tool dictionary attack di halaman (sudah diisi wordlist contoh) untuk mencocokkan hash
MD5 dengan kandidat password. Di dunia nyata, cukup tempel hash ke pencarian "MD5 decrypt"
online atau jalankan `hashcat -m 0 hashes.txt rockyou.txt`.

### Lab 3 — Reversible "encryption"
Login lewat form "remember me", lihat cookie `remember_me` di DevTools, lalu decode base64-nya
(`atob(...)` di console browser) untuk mendapatkan `username:password` mentah.

## Mitigasi (untuk didiskusikan setelah lab)
- **Jangan pernah** menyimpan password apa adanya (Lab 1).
- Gunakan algoritma hashing khusus password yang **lambat & bersalt** secara desain: **bcrypt**,
  **scrypt**, atau **Argon2** (di PHP: `password_hash()` dengan `PASSWORD_BCRYPT`/`PASSWORD_ARGON2ID`,
  bukan `md5()`/`sha1()`) — kecepatan yang lambat justru fitur keamanan di sini karena membuat
  brute force jadi mahal, kebalikan dari MD5 yang didesain secepat mungkin (Lab 2).
- Salt harus unik per password (built-in pada `password_hash()`) supaya dua user dengan password
  sama tidak menghasilkan hash yang sama, dan rainbow table pre-computed jadi tidak berguna.
- Base64/encoding **bukan** enkripsi — tidak ada kunci rahasia yang membuatnya sulit dibalik.
  Kalau memang perlu menyimpan data yang harus bisa "dikembalikan" ke bentuk asli (bukan
  password satu arah), gunakan enkripsi simetris nyata (mis. AES-256-GCM) dengan kunci yang
  disimpan terpisah dan dikelola lewat KMS/secrets manager (Lab 3).
- Jangan menaruh kredensial mentah di cookie sama sekali — gunakan token sesi acak yang tidak
  bisa "didekode" untuk mendapatkan kembali password aslinya.
