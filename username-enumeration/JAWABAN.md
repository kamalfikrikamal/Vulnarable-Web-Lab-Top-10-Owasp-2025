# Kunci Jawaban — Username Enumeration Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Pesan berbeda (`lab1_different_message.php`)
**Kode:** `if (!$user) { 'User tidak ditemukan.' } elseif (...) { 'Password salah.' }`
**Langkah:** login `randomuser123`/apapun → "User tidak ditemukan."; login `alice`/salah →
"Password salah."
**Kenapa berhasil:** dua cabang error mengembalikan teks yang jelas berbeda, membocorkan
langsung validasi mana yang gagal.
**Hasil:** `alice` dan `admin` bisa dipastikan valid tanpa tahu passwordnya.

---

## Lab 2 — Beda 1 byte (`lab2_subtle_difference.php`)
**Kode:** `'Invalid username or password.'` (dengan titik) vs `'Invalid username or password'`
(tanpa titik).
**Langkah:** `curl -s -d "username=randomuser123&password=x" http://target/.../lab2... | wc -c`
dibandingkan dengan request untuk `alice`.
**Kenapa berhasil:** dua string yang secara visual sama tapi berbeda 1 karakter tetap berbeda di
level HTTP response body — panjang Content-Length pun ikut berbeda.
**Hasil:** validitas username terbukti lewat perbedaan panjang response.

---

## Lab 3 — Timing (`lab3_response_timing.php`)
**Kode:** `usleep(300000)` hanya dijalankan di cabang "username ditemukan".
**Langkah:** kirim beberapa request untuk username valid vs tidak valid, bandingkan
`elapsed`/waktu respons yang ditampilkan.
**Kenapa berhasil:** pekerjaan tambahan (simulasi cost hashing password) hanya terjadi kalau
username ditemukan — perbedaan waktu ini adalah *side channel* yang membocorkan validitas
terlepas dari isi pesan errornya (yang sengaja dibuat identik).
**Hasil:** username valid konsisten menunjukkan waktu respons ~0.3 detik lebih lama.

---

## Lab 4 — Lockout (`lab4_account_lockout.php`)
**Kode:** counter `$db['lockouts'][$username]` hanya pernah diisi di dalam blok `if ($user)`.
**Langkah:** salah password 3x untuk `alice` → pesan lockout muncul. Ulangi 3x untuk username
acak → pesan lockout tidak pernah muncul.
**Kenapa berhasil:** mekanisme keamanan (lockout) itu sendiri jadi oracle baru karena hanya
"aktif" untuk username yang benar-benar ada di database.
**Hasil:** validitas username terbukti lewat ada/tidaknya pesan lockout setelah beberapa
percobaan.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Pesan error berbeda | Dua pesan berbeda jelas terlihat |
| 2 | Response beda 1 byte | Content-Length berbeda antar kasus |
| 3 | Timing oracle | Delay ~0.3s hanya untuk username valid |
| 4 | Lockout oracle | Pesan lockout hanya muncul untuk username valid |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Satu pesan generik identik byte-demi-byte untuk semua kegagalan login.
- Samakan waktu respons (jalankan verifikasi dummy meski user tidak ada).
- Lockout harus berperilaku identik terlepas dari validitas username.
