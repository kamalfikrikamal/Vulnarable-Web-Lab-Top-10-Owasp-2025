# JWT Vulnerabilities Lab (Cryptographic Failures)

Aplikasi PHP sederhana (implementasi JWT manual, tanpa library eksternal) yang mendemonstrasikan
empat kesalahan umum verifikasi JSON Web Token, bagian dari **A04: Cryptographic Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Algoritma `alg=none` diterima | `lab1_alg_none.php` |
| 2 | Secret HMAC lemah (brute-forceable) | `lab2_weak_secret.php` |
| 3 | Signature tidak pernah diverifikasi | `lab3_no_signature_check.php` |
| 4 | Path traversal lewat header `kid` | `lab4_kid_path_traversal.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/jwt/`, atau lewat Portal → **A04: Cryptographic Failures** →
**JWT Vulnerabilities**. Tiap lab otomatis menerbitkan token "role: user" ke session-mu di
kunjungan pertama — tujuannya adalah memalsukan token yang diterima server sebagai **admin**.

## Panduan tiap lab

### Lab 1 — alg=none
Ganti header token jadi `{"typ":"JWT","alg":"none"}`, payload jadi
`{"username":"admin","role":"admin"}`, base64url-encode keduanya, gabungkan jadi
`header.payload.` (titik terakhir tanpa signature). Semua langkah dan hasil encoding sudah
ditampilkan di hint box halaman lab.

### Lab 2 — Weak secret
Wordlist kandidat secret ditampilkan di halaman. Gunakan tool "Forge Token" untuk mencoba tiap
kandidat menandatangani token `role: admin` — kalau tebakan benar, "Verify & Login" menerimanya.

### Lab 3 — No signature check
Ubah payload token asli jadi `role: admin`, isi bagian signature dengan string apa saja (bahkan
sampah) — server tidak pernah mengecek signature-nya sama sekali.

### Lab 4 — kid path traversal
Header `kid` dipakai server untuk memilih file kunci dari folder `keys/` tanpa sanitasi. Arahkan
`kid` ke `../../../../../../dev/null` (file yang pasti ada & selalu kosong di container Linux),
lalu tanda tangani token dengan kunci string kosong.

## Mitigasi (untuk didiskusikan setelah lab)
- **Tolak eksplisit** algoritma `none` dan pastikan library JWT yang dipakai divalidasi terhadap
  allowlist algoritma yang diharapkan (mis. hanya terima `HS256`, tolak apa pun di luar itu) —
  jangan percaya nilai `alg` dari header token itu sendiri untuk memutuskan cara verifikasi.
- Gunakan secret HMAC yang panjang & acak (256-bit dari CSPRNG), atau lebih baik gunakan
  algoritma asimetris (RS256/ES256) dengan private key yang tidak pernah meninggalkan server.
- **Selalu** verifikasi signature sebelum mempercayai isi payload — gunakan library JWT yang
  sudah teruji (`firebase/php-jwt`, dll), jangan implementasi manual yang rawan lupa langkah
  krusial ini.
- Kalau memakai header `kid`, validasi nilainya lewat **allowlist** kunci yang sudah dikenal
  (mis. lookup di array/database, bukan langsung dipakai sebagai bagian path filesystem), atau
  sanitasi ketat dengan `basename()` + pembatasan direktori lewat `realpath()`.
