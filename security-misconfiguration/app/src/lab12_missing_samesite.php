<?php
$title = 'Lab 12: Cookie Tanpa Atribut SameSite';
require_once __DIR__ . '/lib.php';

// VULNERABLE: parameter samesite sengaja tidak disertakan sama sekali di
// array $opts (bukan cuma diisi 'None') - browser lama/WebView akan
// memperlakukannya seolah SameSite=None (dikirim di semua request lintas
// situs), berbeda dari cookie yang secara eksplisit dinyatakan Lax/Strict.
$token = ensure_demo_cookie('demo_session_no_samesite', [
    'expires' => time() + 3600,
    'path' => '/secmisconfig/',
    'httponly' => true,
    'secure' => false,
]);

$db = load_db();
include 'header.php';
?>

<p>Cookie session <code>demo_session_no_samesite</code> diset tanpa atribut <code>SameSite</code>
sama sekali — bukan <code>Strict</code>, bukan <code>Lax</code>, bukan <code>None</code>,
benar-benar tidak dideklarasikan.</p>

<h3>1. Konfirmasi lewat curl</h3>
<pre class="result-box">curl -i http://localhost:8079/secmisconfig/lab12_missing_samesite.php | grep -i set-cookie</pre>
<p class="hint">Bandingkan dengan cookie di Lab 10/11 yang eksplisit mendeklarasikan
<code>SameSite=Lax</code> — di sini atributnya tidak ada sama sekali.</p>

<h3>2. Kenapa ini tetap masalah walau browser modern "membantu"</h3>
<p class="hint">Sejak Chrome 80 &amp; Firefox 96 (2020-an), browser modern menerapkan
<code>SameSite=Lax</code> sebagai <em>default diam-diam</em> untuk cookie yang tidak
mendeklarasikan atributnya — jadi risikonya sudah <em>berkurang</em> di browser-browser tersebut.
Tapi ini bukan alasan untuk tidak mendeklarasikannya secara eksplisit:</p>
<ul>
  <li>Browser lama, WebView di aplikasi mobile, dan beberapa automation tool/proxy tetap
  memperlakukan cookie tanpa <code>SameSite</code> sebagai <code>None</code> (dikirim di semua
  request lintas situs, termasuk dari <code>&lt;img&gt;</code>/<code>fetch</code> di halaman
  attacker).</li>
  <li>Default browser bisa berubah kapan saja mengikuti spesifikasi — kode yang bergantung pada
  default implisit gampang berubah perilaku tanpa developer menyadarinya.</li>
  <li>Menyatakan atribut secara eksplisit adalah dokumentasi hidup: siapa pun yang membaca kode
  langsung tahu perilaku yang dimaksud, tidak perlu menebak-nebak default browser versi berapa
  yang berlaku.</li>
</ul>

<details class="hint-box">
<summary>Bedanya dengan lab CSRF di kategori Broken Access Control</summary>
<p class="hint">Lab CSRF di kategori lain fokus ke <em>validasi token</em> yang hilang/bisa
di-bypass. Lab ini fokus ke lapisan pertahanan yang berbeda &amp; independen: atribut cookie itu
sendiri yang menentukan apakah cookie <em>ikut terkirim sama sekali</em> di request lintas situs.
Idealnya kedua lapisan ini dipasang bersamaan (defense in depth) — token CSRF yang valid
<strong>dan</strong> cookie yang secara eksplisit di-scope dengan <code>SameSite=Strict</code>
atau <code>Lax</code>.</p>
</details>

<?php include 'footer.php'; ?>
