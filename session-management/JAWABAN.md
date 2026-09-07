# Kunci Jawaban — Broken Session Management Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Token survives logout (`lab1_token_survives_logout.php`)
**Kode:** `logout` hanya memanggil `setcookie('authtoken1', '', time()-3600, '/')` — tidak ada
`unset($db['sessions'][$token])`.
**Langkah:** login, catat token, logout, tempel token lama ke "Cek akses dengan token".
**Kenapa berhasil:** logout di sini murni operasi sisi client (hapus cookie); state otoritatif
(daftar token valid) di server tidak pernah diperbarui.
**Hasil:** token lama tetap memberi akses penuh ke akun alice meski sudah "logout".

---

## Lab 2 — Predictable session ID (`lab2_predictable_session_id.php`)
**Kode:** `'SESS-' . $db['next_seq']++`.
**Langkah:** "Simulasikan alice login" (mis. token jadi `SESS-1000`), "Login sebagai attacker"
(token jadi `SESS-1001`), coba `SESS-1000` di form cek akses.
**Kenapa berhasil:** token cuma counter — begitu satu nilai diketahui, seluruh ruang token di
sekitarnya bisa ditebak lewat iterasi sederhana.
**Hasil:** sesi alice diakses hanya dengan menebak satu angka lebih kecil dari token sendiri.

---

## Lab 3 — Session fixation (`lab3_session_fixation.php`)
**Kode:** `$token = $_COOKIE['authtoken3'] ?? bin2hex(random_bytes(16));` saat login — token
lama (kalau ada) dipertahankan, tidak diregenerasi.
**Langkah:** buka `?fixed_token=FIXED-abc123` (sebagai "korban" yang mengklik link attacker),
login sebagai `alice/alice123`, lalu (sebagai "attacker") cek akses dengan `FIXED-abc123`.
**Kenapa berhasil:** aplikasi menerima identifier sesi dari luar (URL) dan mengizinkannya
"naik level" jadi sesi terotentikasi tanpa pernah mengganti nilainya — persis definisi session
fixation.
**Hasil:** attacker mengakses akun alice memakai token yang sudah ia ketahui sejak sebelum
alice login sama sekali.

---

## Lab 4 — Token in URL (`lab4_token_in_url.php`)
**Kode:** token otentikasi dibaca dari `$_GET['authtoken']`, bukan cookie.
**Langkah:** login, lihat token di address bar, salin dari log simulasi, buka di browser lain.
**Kenapa berhasil:** URL (termasuk query string) secara default dicatat oleh access log web
server, tersimpan di riwayat browser, dan bisa bocor lewat header Referer ke resource pihak
ketiga — kanal kebocoran yang tidak ada pada cookie biasa.
**Hasil:** sesi berhasil "dipindahkan" ke browser lain hanya dengan menyalin URL dari log.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Token tak di-invalidate | Token lama tetap aktif pasca logout |
| 2 | Token sekuensial | Token alice ditebak dari token sendiri |
| 3 | Token tak diregenerasi saat login | Token attacker yang di-fixasi terikat ke alice |
| 4 | Token di URL | Sesi dibajak lewat salinan access log |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Invalidate token di server saat logout, bukan cuma hapus cookie.
- Token dari CSPRNG, tidak sekuensial.
- Regenerasi token setiap perubahan privilege (terutama login).
- Token otentikasi lewat cookie (HttpOnly/Secure/SameSite), tidak pernah lewat URL.
