<?php
$title = 'Lab 13: HTTP Method TRACE Aktif (XST)';
require_once __DIR__ . '/lib.php';
$db = load_db();
include 'header.php';
?>

<p>HTTP method <code>TRACE</code> seharusnya cuma dipakai untuk debugging koneksi — server
diminta mengulang (echo) persis request yang diterimanya, apa adanya, di body response. Distribusi
Linux modern (mis. Debian, yang dipakai image ini) sebenarnya sudah mematikan method ini secara
default (<code>TraceEnable Off</code>) — tapi konfigurasi server ini secara eksplisit
menyalakannya kembali (<code>TraceEnable On</code>), mensimulasikan sysadmin yang mengaktifkannya
lagi untuk "debugging" dan lupa mematikannya sebelum go-live.</p>

<h3>Buktikan</h3>
<pre class="result-box">curl -v -X TRACE http://localhost:8079/secmisconfig/lab13_trace_method.php \
  -H "X-Rahasia-Demo: nilai-ini-seharusnya-tidak-terlihat-siapa-pun"</pre>
<p class="hint">Perhatikan body response: server mengulang balik <strong>persis</strong> semua
header yang kamu kirim, termasuk header custom <code>X-Rahasia-Demo</code> di atas — dan kalau
kamu kirim request ini lewat browser yang membawa cookie session, header <code>Cookie</code>
kamu juga akan ikut terpantul balik apa adanya.</p>

<h3>Kenapa ini berbahaya: Cross-Site Tracing (XST)</h3>
<p class="hint">Flag <code>HttpOnly</code> pada cookie (lihat Lab 10) mencegah
<code>document.cookie</code> membaca cookie lewat JavaScript — tapi secara historis, method
<code>TRACE</code> dipakai sebagai <em>bypass</em>: browser lama yang mengizinkan
<code>XMLHttpRequest</code> mengirim request <code>TRACE</code> akan tetap menyertakan cookie
<code>HttpOnly</code> di header <code>Cookie</code>, dan karena servernya meng-echo semua
header balik di body — yang bisa dibaca JavaScript — <code>HttpOnly</code> pun berhasil
dilewati. Browser modern sudah memblokir <code>TRACE</code> dari <code>XMLHttpRequest</code>/
<code>fetch</code>, tapi method ini seharusnya tetap dimatikan di level server: tidak ada alasan
sah bagi fitur debugging protokol HTTP untuk aktif di server production.</p>

<details class="hint-box">
<summary>Mitigasi</summary>
<p class="hint">Pastikan <code>TraceEnable Off</code> tetap terpasang di konfigurasi Apache (atau
modul/direktif setara di web server lain) — jangan mengaktifkannya kembali untuk keperluan
debugging sementara tanpa mematikannya lagi setelah selesai. Auditor sebaiknya tetap memverifikasi
langsung lewat <code>curl -X TRACE</code>, karena konfigurasi eksplisit seperti ini bisa saja
menimpa default aman yang sudah disediakan distribusi/image dasar.</p>
</details>

<?php include 'footer.php'; ?>
