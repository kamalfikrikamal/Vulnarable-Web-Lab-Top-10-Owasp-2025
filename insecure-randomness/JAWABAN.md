# Kunci Jawaban — Insecure Randomness Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Password reset token (`lab1_predictable_reset_token.php`)
**Kode:** `substr(md5($username . time()), 0, 16)`.
**Langkah:** request reset untuk `admin`, catat timestamp `$ts` yang ditampilkan (mensimulasikan
header `Date` di dunia nyata), hitung `substr(md5('admin' . $ts), 0, 16)`, submit ke form
konfirmasi.
**Kenapa berhasil:** input pembangkit token (`username`, `time()`) sama sekali tidak rahasia —
`username` sering publik/mudah ditebak, dan `time()` bocor lewat header respons standar HTTP.
Tidak ada entropi rahasia dalam token ini sama sekali.
**Hasil:** token dihitung ulang persis sama, memungkinkan reset password admin tanpa email.

---

## Lab 2 — API key sekuensial (`lab2_sequential_api_key.php`)
**Payload:** `?key=1043`.
**Kenapa berhasil:** API key hanyalah nilai auto-increment database — sama sekali bukan token
acak.
**Hasil:** detail API key admin (`1043`, "Admin master API key - full access") terbaca.

---

## Lab 3 — OTP bisa diprediksi (`lab3_predictable_otp.php`)
**Kode:** `mt_srand($ts); mt_rand(100000, 999999);`
**Langkah:** klik "Generate OTP", catat `$ts` yang muncul di pesan sukses, masukkan ke form
"Predict OTP" untuk mendapat nilai prediksi, lalu submit nilai itu ke form "Verify OTP".
**Kenapa berhasil:** `mt_rand()` adalah Mersenne Twister — PRNG cepat tapi deterministik
sepenuhnya dari seed-nya. Men-seed manual dengan `time()` (nilai publik/bisa ditebak dalam
rentang beberapa detik) menghilangkan seluruh manfaat "acak" dari fungsi ini.
**Hasil:** OTP terprediksi tepat, "login" admin berhasil tanpa pernah menerima OTP asli.

---

## Lab 4 — Kode kupon bisa ditebak (`lab4_predictable_coupon.php`)
**Kode:** `'SAVE' . str_pad($order_id, 4, '0', STR_PAD_LEFT)`.
**Payload:** checkout untuk melihat pola (mis. `SAVE1001`), lalu redeem `SAVE1000` atau lainnya.
**Kenapa berhasil:** "kode unik" ternyata cuma representasi ulang nomor order yang sekuensial —
tidak ada komponen acak sama sekali.
**Hasil:** kupon diskon milik order lain berhasil dipakai.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Token dari input publik | Password admin berhasil direset |
| 2 | ID sekuensial | API key admin terbaca |
| 3 | PRNG di-seed nilai publik | OTP admin berhasil ditebak persis |
| 4 | Kode dari data bisnis publik | Kupon order lain berhasil diredeem |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Pakai CSPRNG (`random_bytes()`/`random_int()`), jangan `rand()`/`mt_rand()` untuk apa pun yang
  berfungsi sebagai secret.
- Jangan pernah men-seed PRNG dengan nilai yang bisa ditebak (`time()`, ID user, dst).
- ID akses (API key, token) tidak boleh sekuensial.
- Kode bernilai uang (kupon/voucher) harus token acak panjang, bukan derivasi data publik.
