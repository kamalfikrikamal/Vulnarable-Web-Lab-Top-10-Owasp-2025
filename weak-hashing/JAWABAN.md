# Kunci Jawaban — Weak Password Hashing Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Plaintext storage (`lab1_plaintext.php`)
**Kode:** `'password' => 'Summer2024!'` disimpan sebagai string biasa, ditampilkan apa adanya.
**Langkah:** buka halaman, baca kolom password langsung dari tabel.
**Kenapa berhasil:** tidak ada transformasi kriptografi sama sekali antara input password saat
registrasi dan apa yang disimpan.
**Hasil:** ketiga password (`Summer2024!`, `P@ssw0rd`, `admin123`) langsung diketahui.

---

## Lab 2 — Unsalted MD5 (`lab2_unsalted_md5.php`)
**Kode:** `'hash' => md5('Summer2024!')` dst.
**Payload:** wordlist default sudah memuat ketiga password asli — klik "Crack Hashes" langsung
menghasilkan match. Manual: `md5("admin123")` = `0192023a7bbd73250516f069df18b500` — cocokkan
dengan hash admin.
**Kenapa berhasil:** MD5 tanpa salt bersifat deterministik & sangat cepat dihitung (miliaran
hash/detik dengan GPU modern) — cocok untuk lookup table pre-computed (rainbow table) atau
dictionary attack kilat, sama sekali tidak didesain untuk menahan brute force seperti algoritma
password modern.
**Hasil:** ketiga password berhasil di-crack: `alice→Summer2024!`, `bob→P@ssw0rd`,
`admin→admin123`.

---

## Lab 3 — Reversible "encryption" (`lab3_reversible_encoding.php`)
**Kode:** `$encrypted_credentials = base64_encode($username . ':' . $password);`
**Payload:** login lewat form, lalu di console browser:
```js
atob(document.cookie.match(/remember_me=([^;]+)/)[1])
```
**Kenapa berhasil:** base64 adalah *encoding* (representasi ulang byte, dapat dibalik tanpa
kunci apa pun), bukan *enkripsi* (yang mensyaratkan kunci rahasia agar tidak bisa dibalik tanpa
kunci itu). Menamai variabelnya "encrypted" tidak mengubah sifat matematisnya.
**Hasil:** `username:password` mentah (`alice:Summer2024!`) terbaca langsung dari cookie oleh
siapa pun yang memiliki akses ke cookie tersebut (mis. lewat XSS, atau siapa pun yang memakai
komputer bersama).

---

## Ringkasan hasil akhir

| Lab | Teknik | Hasil |
|---|---|---|
| 1 | Plaintext read | Semua password langsung terbaca |
| 2 | MD5 dictionary attack | 3/3 password berhasil di-crack |
| 3 | Base64 decode | Kredensial lengkap terbaca dari cookie |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Gunakan `password_hash()` (bcrypt/Argon2id) — lambat & bersalt secara desain.
- Jangan pernah menyimpan password plaintext.
- Base64/encoding bukan enkripsi; gunakan AES nyata dengan kunci terkelola bila memang perlu
  data yang bisa dikembalikan ke bentuk asli.
