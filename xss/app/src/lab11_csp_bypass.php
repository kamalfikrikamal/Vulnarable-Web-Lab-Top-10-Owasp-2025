<?php
$title = 'Lab 11: Reflected XSS meski ada CSP (unsafe-inline)';
$q = $_GET['q'] ?? '';

// "Mitigasi" yang terlihat aman tapi sebenarnya tidak melindungi apa pun:
// 'unsafe-inline' di script-src membuka kembali persis vektor yang harusnya
// diblok CSP (inline <script>...</script> dan atribut event handler inline
// seperti onerror=...). Header ini dikirim SEBELUM bug reflected XSS di
// bawah, supaya trainee bisa mengamati keduanya sekaligus.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline';");

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman ini punya bug reflected XSS yang PERSIS sama dengan Lab 1
(parameter <code>q</code> dicetak ke body HTML tanpa <code>htmlspecialchars()</code>), TAPI
halaman ini juga mengirim header <code>Content-Security-Policy</code>. Banyak tim menganggap
begitu CSP terpasang, XSS otomatis "termitigasi" — cek dulu header responsnya sendiri:</p>
<pre class="hint">curl -i "http://localhost:8079/xss/lab11_csp_bypass.php?q=test"</pre>
<p class="hint">atau lewat DevTools &rarr; tab Network &rarr; pilih request halaman ini &rarr;
lihat Response Headers. Perhatikan nilai <code>script-src</code>-nya:
<code>script-src 'self' 'unsafe-inline'</code>. Direktif <code>'unsafe-inline'</code> secara
eksplisit MENGIZINKAN <code>&lt;script&gt;</code> inline dan atribut event handler inline
(seperti <code>onerror=...</code>) — dua vektor utama yang justru seharusnya diblok CSP.
Sekarang coba payload reflected XSS yang sama seperti Lab 1:</p>
<pre class="hint">?q=&lt;script&gt;alert(document.domain)&lt;/script&gt;</pre>
<p class="hint">Payload tetap jalan walau CSP "terpasang" — hubungkan dua pengamatan ini:
header CSP ada, tapi <code>'unsafe-inline'</code> membuat direktif <code>script-src</code>
pada dasarnya tidak berarti apa-apa untuk mencegah inline XSS.</p>
</details>

<form method="get">
  <label>Search</label><br>
  <input type="text" name="q" value="">
  <button type="submit">Search</button>
</form>

<?php if ($q !== ''): ?>
<div class="result-box">
Search results for: <?php echo $q; /* VULNERABLE: no htmlspecialchars(), sama seperti Lab 1 */ ?>
</div>
<?php endif; ?>

<h3>CSP yang dikirim halaman ini</h3>
<div class="result-box">Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline';</div>

<p>Bandingkan dengan CSP yang benar-benar keras (hardened): pakai <code>nonce</code> atau
<code>hash</code> per-response untuk <code>script-src</code> TANPA <code>'unsafe-inline'</code>,
tambahkan <code>object-src 'none'</code> (blok vektor injeksi via plugin/object/embed) dan
<code>base-uri 'self'</code> (cegah manipulasi tag <code>&lt;base&gt;</code> untuk membajak
resolusi URL relatif). Contoh header yang jauh lebih kuat:</p>
<div class="result-box">Content-Security-Policy: script-src 'self' 'nonce-RANDOM123'; object-src 'none'; base-uri 'self';</div>

<?php include 'footer.php'; ?>
