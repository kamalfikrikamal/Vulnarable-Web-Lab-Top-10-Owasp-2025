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
