# Kunci Jawaban — Logging & Alerting Failures Lab (A09)

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Log injection / log forgery (`lab1_log_injection_forgery.php`)
**Kode:** `$line = '[' . date(...) . '] LOGIN ATTEMPT: user=' . $username_raw . ', result=' . $result;`
lalu `append_log('auth.log', $line)` — `$username_raw` langsung dari `$_POST['username']`, tanpa
sanitasi apa pun.
**Payload:** username diisi (di `<textarea>`, biar bisa multi-baris):
```
bob
[2026-01-01 03:00:00] LOGIN ATTEMPT: user=admin, result=SUCCESS
```
Password bebas.
**Kenapa berhasil:** newline (`\n`) di dalam username tidak pernah di-strip atau di-encode
sebelum ditulis ke file log. Karena log berbasis baris teks biasa, satu karakter newline sudah
cukup untuk membuat penyerang "menulis baris log barunya sendiri" yang formatnya identik dengan
baris asli buatan sistem.
**Hasil:** `auth.log` memuat baris palsu `LOGIN ATTEMPT: user=admin, result=SUCCESS` yang terlihat
sah, padahal admin tidak pernah login saat itu — bisa menyesatkan investigasi insiden atau
dipakai membangun alibi palsu.

---

## Lab 2 — Log injection → Stored XSS di dashboard admin (`lab2_log_injection_stored_xss.php`)
**Kode:** `append_log('search.log', ... 'query="' . $q . '"' ...)` saat menulis, lalu di dashboard:
`echo '... Query: ' . $query . ' ...';` — hasil parsing balik log di-echo langsung tanpa
`htmlspecialchars()`.
**Payload:** cari `<img src=x onerror="document.title='PWNED-VIA-LOG'">`, lalu buka
`?view=admin_dashboard`.
**Kenapa berhasil:** log diperlakukan sebagai "data internal milik sistem sendiri", jadi tim yang
membuat dashboard-nya menganggap tidak perlu encoding saat merender isinya. Padahal konten log itu
sendiri berasal dari input pengguna (`$_GET['q']`) yang sepenuhnya tidak tepercaya — begitu masuk
log, sifat "tidak tepercaya"-nya tidak hilang.
**Hasil:** payload tersimpan di `search.log`, lalu dieksekusi sebagai script sungguhan saat
dashboard admin dibuka — judul tab browser berubah jadi "PWNED-VIA-LOG", membuktikan stored XSS
yang pintu masuknya adalah pipeline logging, bukan form komentar biasa.

---

## Lab 3 — Tidak ada logging/alerting untuk login gagal (`lab3_no_alerting_bruteforce.php`)
**Kode:** percobaan sukses → `append_log('login_events.log', ...)`; percobaan gagal → tidak ada
kode logging sama sekali (baik di form single maupun bulk).
**Langkah:** pakai kotak "Coba banyak PIN sekaligus", tempel daftar PIN 3 digit (mis.
`000`–`999`) untuk mencoba ratusan kombinasi dalam satu request terhadap PIN admin (`482`).
**Kenapa berhasil:** tidak ada rate limiting (memang bukan fokus lab ini) **dan** tidak ada
mekanisme apa pun yang mencatat percobaan gagal — baik ke file log, database, maupun counter
server-side yang persisten. Counter yang ditampilkan di dashboard murni disimpan di session PHP
untuk visibilitas trainee sendiri, bukan representasi dari apa yang benar-benar dipantau sistem.
**Hasil:** setelah ratusan percobaan gagal, `login_events.log` tetap kosong (atau hanya berisi
satu baris SUCCESS kalau PIN akhirnya ketemu) — membuktikan pola serangan brute force yang jelas
mencurigakan ini sama sekali tidak meninggalkan jejak yang bisa dideteksi tim keamanan.

---

## Lab 4 — Data sensitif ikut tercatat di log (`lab4_sensitive_data_in_logs.php`)
**Kode:** `$line = '... card_number=' . $card . ', cvv=' . $cvv . ', name=' . $name;` lalu ditulis
plaintext ke `data/debug_requests.log`, "buat debugging".
**Payload:** submit form "Update Kartu Kredit" dengan data kartu apa saja, lalu buka
`?view=raw_log`.
**Kenapa berhasil:** field yang jelas-jelas sensitif (nomor kartu penuh, CVV) tidak pernah
di-mask/redact sebelum ditulis ke log, dan log itu sendiri bisa diakses lewat endpoint debug yang
"lupa" dibatasi aksesnya — pola umum "cuma buat debugging sementara" yang berakhir jadi celah
permanen.
**Hasil:** log mentah menampilkan kartu kredit & CVV milik trainee sendiri, **plus** 3 entri
kartu milik user lain yang sudah pernah submit sebelumnya (data pre-seeded) — membuktikan log
debugging seperti ini, kalau bocor/diakses tanpa izin, setara dengan kebocoran data pembayaran
sungguhan.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Newline di input tidak difilter sebelum ditulis ke log | Baris log palsu "admin login SUCCESS" berhasil disuntikkan |
| 2 | Log content tidak di-escape saat dirender di dashboard | Stored XSS jalan di halaman admin, mengubah judul tab |
| 3 | Percobaan gagal tidak pernah dicatat/dipantau | Ratusan percobaan brute force tidak meninggalkan jejak di log |
| 4 | Field sensitif dicatat plaintext ke log debug | Nomor kartu & CVV milik trainee + 3 user lain terbaca di log |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Strip/encode karakter kontrol (newline dkk) dari input sebelum ditulis ke log, atau pakai
  structured logging (JSON per baris).
- Selalu HTML-encode konten log saat dirender di viewer/dashboard apa pun.
- Catat juga kejadian negatif (login gagal, akses ditolak), bukan cuma yang sukses.
- Pasang alerting/threshold nyata untuk pola mencurigakan (banyak gagal dalam waktu singkat).
- Jangan pernah mencatat field sensitif (kartu, password, token) secara utuh — mask/redact dulu.
- Perlakukan file log sebagai aset sensitif dengan kontrol akses sendiri.
