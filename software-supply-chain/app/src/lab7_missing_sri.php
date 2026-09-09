<?php
$title = 'Lab 7: Missing Subresource Integrity (SRI)';
require_once __DIR__ . '/lib.php';
$db = load_db();
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman ini memuat skrip pihak ketiga (simulasi CDN eksternal) dua kali
&mdash; satu tanpa atribut <code>integrity</code>, satu lagi dengan atribut <code>integrity</code>
yang benar. Kedua tag <code>&lt;script&gt;</code> menunjuk ke file YANG SAMA, yaitu versi yang
sudah "ditampering" (mensimulasikan CDN yang disusupi attacker setelah developer pertama kali
memasang tag script-nya). Buka DevTools &rarr; tab Console setelah reload untuk melihat
perbedaannya.</p>
</details>

<p>Developer memasang skrip analytics dari CDN pihak ketiga. Suatu saat, CDN itu disusupi
attacker dan sekarang menyajikan file yang isinya sudah dimodifikasi di URL yang sama persis.</p>

<h3>1. Tanpa Subresource Integrity (vulnerable)</h3>
<pre class="result-box">&lt;script src="cdn_analytics_tampered.js"&gt;&lt;/script&gt;</pre>
<script src="cdn_analytics_tampered.js"></script>
<p class="hint">Kalau banner merah muncul di bawah judul halaman ini dan judul tab berubah jadi
"PWNED", berarti skrip yang sudah dimodifikasi attacker berhasil jalan sepenuhnya — browser tidak
punya cara mengetahui isi file ini sudah berubah dari yang dipercaya developer, karena tidak ada
<code>integrity</code> yang dipasang.</p>

<h3>2. Dengan Subresource Integrity yang benar (protected)</h3>
<pre class="result-box">&lt;script src="cdn_analytics_tampered.js"
  integrity="sha384-eaH8jZOIPjHce62nZKGmnjT2gtnvGfBqyoyi+05SkFGo5PQNh+12ExesY5C/PWFG"
  crossorigin="anonymous"&gt;&lt;/script&gt;</pre>
<script src="cdn_analytics_tampered.js"
  integrity="sha384-eaH8jZOIPjHce62nZKGmnjT2gtnvGfBqyoyi+05SkFGo5PQNh+12ExesY5C/PWFG"
  crossorigin="anonymous"></script>
<p class="hint">Hash <code>integrity</code> di atas adalah hash SHA-384 dari isi skrip
<strong>asli/legit</strong> (<code>cdn_analytics_legit.js</code>) — dipasang developer saat
pertama kali menambahkan skrip ini, waktu isinya masih bisa dipercaya. Tag ini menunjuk ke file
yang SAMA (yang sudah ditampering) seperti nomor 1 di atas, tapi buka DevTools &rarr; Console:
browser menolak menjalankannya sama sekali (<code>Failed to find a valid digest... in the
integrity attribute</code>) karena hash file yang sebenarnya diterima tidak cocok dengan hash yang
dipasang di atribut <code>integrity</code>. Tidak ada banner merah kedua yang muncul — proteksi
ini benar-benar memblokir eksekusinya, bukan cuma memberi peringatan.</p>

<details class="hint-box">
<summary>Cara menghitung hash integrity sendiri</summary>
<pre class="result-box">openssl dgst -sha384 -binary cdn_analytics_legit.js | openssl base64 -A</pre>
<p class="hint">Perintah ini persis algoritma yang dipakai browser untuk memverifikasi atribut
<code>integrity</code> (SHA-256/384/512, di-encode base64, diawali <code>sha384-</code>).</p>
</details>

<p class="hint">Kenapa ini penting: setiap skrip pihak ketiga yang dimuat langsung dari domain
eksternal (CDN, analytics, widget, font, dsb.) adalah bagian dari attack surface aplikasi — kalau
CDN itu disusupi (via kompromi akun, DNS hijack, atau insiden internal di sisi vendor), skrip yang
"dipercaya begitu saja" tanpa <code>integrity</code> bisa diam-diam diganti dan langsung berjalan
dengan akses penuh ke DOM, cookie, dan form di halaman aplikasi kamu. Insiden nyata: British
Airways (2018) kehilangan data 380.000 transaksi kartu kredit lewat skrip pihak ketiga yang
disusupi dan tidak pernah diverifikasi integritasnya.</p>

<?php include 'footer.php'; ?>
