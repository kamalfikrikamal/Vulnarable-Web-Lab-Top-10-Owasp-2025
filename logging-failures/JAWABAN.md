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

## Lab 5 — Alert threshold bisa dihindari dengan pacing (`lab5_threshold_evasion.php`)
**Kode:** setiap request memangkas (`array_filter`) `$db['threshold_login_fails']` supaya hanya
menyisakan timestamp dalam 60 detik terakhir, lalu setelah menambahkan percobaan gagal baru, cek
`count($db['threshold_login_fails']) > 20` — kalau ya baru `threshold_alerts_fired++` dan
`append_log('brute_alerts.log', ...)`. Tidak ada memori/statistik di luar window aktif ini.
**Payload:** PIN admin `705`. Kirim batch "Coba banyak PIN sekaligus" berisi 15 PIN salah (di
bawah threshold 20), tunggu &gt;60 detik, ulangi beberapa kali.
**Kenapa berhasil:** alert hanya dievaluasi terhadap jumlah percobaan gagal di window 60 detik
yang aktif *saat itu saja*. Selama satu batch tidak pernah melebihi 20 percobaan dalam window-nya
sendiri, alert tidak pernah terpicu — tidak peduli berapa total percobaan gagal yang sudah
terkumpul sepanjang waktu.
**Hasil:** `threshold_alerts_fired` tetap `0` dan `brute_alerts.log` tetap kosong, walaupun
`threshold_total_attempts` (all-time) sudah melewati puluhan/ratusan — membuktikan threshold
fixed-count/fixed-window bisa dihindari sepenuhnya dengan mengatur kecepatan serangan.

---

## Lab 6 — User bisa menghapus log audit miliknya sendiri (`lab6_log_tampering.php`)
**Kode:** tombol "Hapus Semua Riwayat" mengirim POST yang langsung menjalankan
`$db['audit_log'] = []; save_db($db);` — persis tabel yang sama yang dibaca ulang oleh view
`?view=soc`.
**Payload:** buka `lab6_log_tampering.php` (tab "Riwayat Aktivitas Saya"), klik "Hapus Semua
Riwayat", lalu buka `?view=soc`.
**Kenapa berhasil:** tidak ada pemisahan struktural antara "data yang boleh dihapus user" dan
"log audit keamanan" — keduanya adalah satu tabel `audit_log` yang sama, dan endpoint hapusnya
bisa diakses dengan hak akses user biasa.
**Hasil:** setelah menghapus, tab "Admin: Log Investigasi (SOC)" ikut menampilkan `audit_log`
KOSONG — seluruh jejak aktivitas keamanan akun (perubahan email, percobaan login gagal, ekspor
data) lenyap tanpa perlu hak akses admin sama sekali.

---

## Lab 7 — Data sensitif bocor lewat console browser (`lab7_client_side_console_logging.php`)
**Kode:** `<script>` inline menjalankan `console.log('DEBUG session:', {user_id, session_token,
saved_card_last4, internal_api_key})` saat halaman dimuat dan saat tombol "Lanjutkan ke
Pembayaran" diklik.
**Payload:** buka `lab7_client_side_console_logging.php`, buka DevTools → tab Console (atau lihat
kotak reproduksi `<pre>` di halaman yang sama untuk bukti tanpa harus membuka browser).
**Kenapa berhasil:** baris debug logging yang menulis token sesi dan API key internal ke console
tidak pernah dihapus sebelum rilis ke produksi — halaman itu sendiri (HTML, response jaringan)
tidak membocorkan apa pun, tapi console browser setiap pengunjung membocorkannya secara diam-diam.
**Hasil:** `session_token` (`sess_9f8c2a41e7b3441dbe9a7d6c3f0a1122`) dan `internal_api_key`
(`sk_internal_live_4f9b2e7a1c8d3f56`) muncul persis di console browser — terbukti lewat kotak
reproduksi di halaman lab, dan (kalau trainee membuka DevTools sungguhan) langsung di tab Console.

---

## Lab 8 — Log ada, tapi tidak cukup konteks untuk investigasi (`lab8_insufficient_log_context.php`)
**Kode:** `payment_log` (v1) ditulis dengan format tetap `[timestamp] Payment processed:
amount=Rp X` — tidak ada field lain sama sekali. Tombol "Proses Pembayaran Baru" menulis ke
`payment_log_v2` dengan format `... amount=Rp X, user_id=Y, ip=Z, session_id=W, request_id=V`.
**Payload:** buka `lab8_insufficient_log_context.php`, cari baris `amount=Rp 50.000.000` di log
v1, lalu klik "Proses Pembayaran Baru" untuk melihat baris v2 sebagai pembanding.
**Kenapa berhasil:** log v1 "ada" secara teknis, tapi formatnya sejak awal memang tidak pernah
menyertakan identitas apa pun yang bisa dipakai menelusuri pelaku — bukan soal data yang hilang,
tapi field yang memang tidak pernah dirancang untuk ditangkap.
**Hasil:** baris log v1 yang cocok dengan laporan finance (Rp 50.000.000) hanya berisi timestamp
dan jumlah — investigasi mentok total. Baris v2 yang baru dibuat menyertakan `user_id`, `ip`,
`session_id`, dan `request_id` — perbandingan langsung yang membuktikan "logging ada" tidak sama
dengan "logging cukup untuk investigasi".

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Newline di input tidak difilter sebelum ditulis ke log | Baris log palsu "admin login SUCCESS" berhasil disuntikkan |
| 2 | Log content tidak di-escape saat dirender di dashboard | Stored XSS jalan di halaman admin, mengubah judul tab |
| 3 | Percobaan gagal tidak pernah dicatat/dipantau | Ratusan percobaan brute force tidak meninggalkan jejak di log |
| 4 | Field sensitif dicatat plaintext ke log debug | Nomor kartu & CVV milik trainee + 3 user lain terbaca di log |
| 5 | Alert threshold per-window dihindari dengan pacing batch | `threshold_alerts_fired` tetap 0 walau total percobaan gagal sudah puluhan/ratusan |
| 6 | Log audit bisa dihapus lewat izin level-user biasa | View SOC ikut kosong setelah user klik "Hapus Semua Riwayat" |
| 7 | Token/secret ikut ter-log ke console browser | `session_token` & `internal_api_key` muncul di console setiap kunjungan |
| 8 | Log tidak menyertakan identitas korelasi (user/IP/session/request) | Baris log v1 mustahil ditelusuri; baris v2 pembanding membuktikan bedanya |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Strip/encode karakter kontrol (newline dkk) dari input sebelum ditulis ke log, atau pakai
  structured logging (JSON per baris).
- Selalu HTML-encode konten log saat dirender di viewer/dashboard apa pun.
- Catat juga kejadian negatif (login gagal, akses ditolak), bukan cuma yang sukses.
- Pasang alerting/threshold nyata untuk pola mencurigakan (banyak gagal dalam waktu singkat).
- Jangan pernah mencatat field sensitif (kartu, password, token) secara utuh — mask/redact dulu.
- Perlakukan file log sebagai aset sensitif dengan kontrol akses sendiri.
- Kalibrasi threshold alert terhadap pola low-and-slow (deteksi anomali adaptif/statistik, atau
  threshold rendah + lockout per-IP/akun) — bukan aturan fixed-count/fixed-window tunggal.
- Pisahkan log audit/keamanan secara struktural dari data user biasa — akun yang diawasi tidak
  boleh bisa menghapus log yang mengawasinya sendiri.
- Jangan pernah mencatat token/secret ke console browser — strip debug logging dari build produksi.
- Sertakan identitas berkorelasi (user, session, IP, request ID) di setiap log keamanan.
