# Logging & Alerting Failures Lab (A09)

Aplikasi PHP sederhana yang mendemonstrasikan kegagalan logging & alerting: kejadian keamanan
yang tidak tercatat dengan benar, log yang bisa dipalsukan penyerang lewat log injection, log yang
diperlakukan sebagai "data tepercaya" lalu dirender tanpa encoding, sampai data sensitif yang ikut
tertulis ke log &mdash; bagian dari **A09: Logging & Alerting Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Log injection / log forgery lewat newline di username | `lab1_log_injection_forgery.php` |
| 2 | Log injection berujung Stored XSS di dashboard admin | `lab2_log_injection_stored_xss.php` |
| 3 | Tidak ada logging/alerting untuk brute force login gagal | `lab3_no_alerting_bruteforce.php` |
| 4 | Data sensitif (kartu kredit, CVV) ikut tercatat plaintext di log | `lab4_sensitive_data_in_logs.php` |
| 5 | Alert threshold brute force bisa dihindari dengan pacing serangan | `lab5_threshold_evasion.php` |
| 6 | User bisa menghapus log audit miliknya sendiri lewat fitur "privasi" | `lab6_log_tampering.php` |
| 7 | Data sensitif bocor lewat `console.log` debug di browser | `lab7_client_side_console_logging.php` |
| 8 | Log ada, tapi tidak cukup konteks (user/IP/session/request ID) untuk investigasi | `lab8_insufficient_log_context.php` |

Lab 1&ndash;4 tergabung dalam grup **Logging & Alerting Failures** (log yang tidak tercatat,
bisa dipalsukan, atau bocor data sensitif). Lab 5&ndash;8 adalah grup kedua, **Alerting & Log
Integrity Gaps**: kasus di mana mekanisme logging/alerting-nya "ada", tapi masih punya celah
desain yang membuatnya gagal memenuhi tujuannya.

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/loggingfail/`, atau lewat Portal → **A09: Logging & Alerting
Failures** → **Logging & Alerting Failures Lab**.

## Panduan tiap lab

### Lab 1 — Log injection / log forgery
Form login menulis setiap percobaan ke `data/auth.log` memakai `$_POST['username']` mentah,
tanpa membuang karakter newline. Field username berupa `<textarea>` sehingga newline bisa
langsung diketik. Isi username dengan:

```
bob
[2026-01-01 03:00:00] LOGIN ATTEMPT: user=admin, result=SUCCESS
```

Submit (password bebas), lalu lihat bagian "Lihat auth.log" — baris palsu "admin login SUCCESS"
muncul seolah-olah benar-benar terjadi, padahal itu hasil suntikan newline dari field username.

### Lab 2 — Log injection → Stored XSS di dashboard admin
Kotak "Cari Produk" mencatat `$_GET['q']` mentah ke `data/search.log`. "Admin: Log Dashboard"
mem-parsing balik file log itu dan meng-`echo` bagian query-nya langsung ke HTML tanpa
`htmlspecialchars()`. Cari:

```
<img src=x onerror="document.title='PWNED-VIA-LOG'">
```

lalu buka `?view=admin_dashboard` — judul tab browser berubah, membuktikan script benar-benar
jalan di konteks halaman admin. Log di sini diperlakukan sebagai "data internal tepercaya",
padahal isinya tetap berasal dari input pengguna yang tidak tepercaya.

### Lab 3 — Tidak ada logging/alerting untuk login gagal
PIN admin 3 digit (`482`), tanpa rate limiting/lockout. Pakai kotak "Coba banyak PIN sekaligus"
untuk mengirim ratusan tebakan PIN dalam satu request. Bandingkan counter session ("ini cuma
untuk kamu") dengan isi `data/login_events.log` yang sesungguhnya: setelah puluhan/ratusan
percobaan gagal, log itu tetap kosong — hanya baris SUCCESS (kalau berhasil) yang pernah muncul.
Tidak ada jejak maupun alert untuk pola serangan yang jelas-jelas mencurigakan ini.

### Lab 4 — Data sensitif ikut tercatat di log
Form "Update Kartu Kredit" menulis seluruh data form (termasuk nomor kartu dan CVV mentah) ke
`data/debug_requests.log` "buat debugging". Submit form dengan data kartu apa saja, lalu buka
endpoint debug internal `?view=raw_log` — log mentah berisi kartu kamu **dan** kartu 3 user lain
yang sudah pernah submit sebelumnya, semuanya plaintext.

### Lab 5 — Alert threshold bisa dihindari dengan pacing
Login PIN admin 3 digit (`705`) punya alerting sungguhan: server melacak percobaan gagal dalam
sliding window 60 detik, dan memicu `ALERT: possible brute force!` kalau lebih dari 20 percobaan
gagal masuk dalam window itu. Pakai kotak "Coba banyak PIN sekaligus" untuk mengirim satu batch
(mis. 15 PIN salah, sengaja di bawah 20), tunggu lebih dari 60 detik, lalu kirim batch 15 PIN salah
lagi — ulangi beberapa kali. Lihat dashboard: "Jumlah alert yang pernah terpicu sejauh ini" tetap
`0` walaupun "Total percobaan gagal sepanjang waktu" terus naik puluhan bahkan ratusan — threshold
per-window yang naif bisa dihindari sepenuhnya dengan mengatur kecepatan serangan (low-and-slow).

### Lab 6 — User bisa menghapus log audit miliknya sendiri
Halaman "Riwayat Aktivitas Saya" (fitur privasi biasa) dan "Admin: Log Investigasi (SOC)" membaca
tabel `audit_log` yang **sama persis**. Klik "Hapus Semua Riwayat" di tab user, lalu buka tab SOC
— log investigasi keamanan yang seharusnya jadi bukti forensik ikut kosong, dihapus oleh akun
pengguna biasa tanpa hak akses admin sama sekali.

### Lab 7 — Data sensitif bocor lewat console browser
Halaman "checkout" terlihat normal — tidak ada apa pun sensitif di teks halaman atau response
HTML. Tapi script inline-nya menjalankan `console.log('DEBUG session:', {...session_token,
internal_api_key...})` setiap kali halaman dimuat / tombol "Lanjutkan ke Pembayaran" diklik.
Buka DevTools → tab Console untuk melihatnya langsung, atau lihat kotak reproduksi di halaman
lab itu sendiri (disediakan supaya lab tetap bisa dibuktikan tanpa harus membuka browser).

### Lab 8 — Log ada, tapi tidak cukup konteks untuk investigasi
Tim finance melaporkan transaksi mencurigakan senilai Rp 50.000.000. Baris log lama
(`payment_log`) yang cocok hanya berisi `timestamp` dan `amount` — tidak ada `user_id`, `ip`,
`session_id`, atau `request_id`, jadi investigasi mentok total. Klik "Proses Pembayaran Baru"
untuk melihat baris log versi kedua (`payment_log_v2`) yang menyertakan seluruh identitas
tersebut — perbandingan langsung antara log yang "ada" vs. log yang benar-benar bisa diinvestigasi.

## Mitigasi

- Sanitasi/hapus karakter kontrol (terutama newline) dari input pengguna sebelum ditulis ke log,
  atau pakai **structured logging** (mis. satu entri JSON per baris) sehingga konten yang
  disuntikkan tidak bisa memalsukan baris log baru.
- Selalu HTML-encode konten log saat dirender di dashboard/viewer mana pun — log tetaplah data
  yang tidak tepercaya karena isinya berasal dari input pengguna.
- Catat juga kejadian keamanan yang **negatif** (login gagal, penolakan otorisasi), bukan cuma
  yang sukses — kegagalan yang tidak tercatat berarti tidak ada bukti serangan pernah terjadi.
- Pasang alerting/threshold sungguhan untuk pola mencurigakan (mis. banyak login gagal dalam
  waktu singkat ke satu akun), bukan sekadar menyimpan data mentah tanpa ada yang memantaunya.
- Jangan pernah mencatat field sensitif secara utuh (nomor kartu, password, token) ke log — mask
  atau redact nilainya sebelum sampai ke sistem logging mana pun.
- Perlakukan file log/log aggregator sebagai aset sensitif yang butuh kontrol akses sendiri,
  bukan endpoint debug yang bisa diakses siapa saja yang tahu URL-nya.
- Kalibrasi threshold alert terhadap pola serangan low-and-slow — pakai deteksi anomali
  statistik/adaptif, atau minimal kombinasikan threshold rendah dengan lockout per-IP/per-akun,
  bukan satu aturan fixed-count/fixed-window yang bisa terus-menerus "dihindari" penyerang sabar.
- Pisahkan secara struktural log audit/keamanan dari data milik pengguna biasa — akun yang sama
  yang diawasi oleh log tersebut tidak boleh punya izin untuk menghapusnya, walau dikemas sebagai
  fitur "hapus riwayat/privasi" yang terlihat wajar. Gunakan penyimpanan write-once/append-only,
  log shipping ke sistem terpisah yang tidak terjangkau akun yang disusupi, atau minimal hak akses
  terpisah untuk purge log audit vs. purge data user biasa.
- Jangan pernah mencatat token sesi, API key, atau secret lain ke console browser — hapus seluruh
  `console.log` debug sebelum build produksi (otomatis lewat build tooling, mis.
  `drop_console` di Babel/Terser, atau lint rule yang melarang `console.log` masuk ke commit).
- Sertakan identitas yang saling berkorelasi (user/akun, session ID, IP, request ID) di setiap
  entri log yang berkaitan dengan keamanan — logging "ada" saja tidak cukup kalau tidak bisa
  dipakai menelusuri satu kejadian balik ke sumbernya saat investigasi sungguhan.
