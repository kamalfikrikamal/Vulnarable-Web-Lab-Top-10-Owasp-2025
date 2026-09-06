<?php
$title = 'Lab 4: Argument Injection';
$url = $_GET['url'] ?? '';
$output = '';

if ($url !== '') {
    // VULNERABLE: no shell metacharacters needed at all. The user-controlled
    // value is passed as a raw argument to curl. Because it is not prefixed
    // with "--" to mark the end of options, a value starting with "-" is
    // interpreted as a curl FLAG instead of a URL - this is argument injection,
    // a command-injection variant that doesn't rely on ; | & at all.
    $cmd = "curl -s -m 5 " . escapeshellarg($url);
    $output = shell_exec($cmd . ' 2>&1');
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: fitur "link checker" ini membungkus input dengan
<code>escapeshellarg()</code> sehingga metakarakter shell klasik (<code>; | &amp;</code>
dan spasi) sudah tidak berguna untuk lolos dari <em>shell</em> &mdash; <strong>tapi</strong>
aplikasi lupa mengakhiri daftar opsi curl dengan <code>--</code>, sehingga nilai yang diawali
tanda minus tetap diteruskan apa adanya dan ditafsirkan oleh <em>curl sendiri</em> sebagai flag,
bukan sebagai URL. Ini disebut <strong>argument injection</strong> &mdash; berbeda dari OS
command injection biasa karena tidak butuh metakarakter shell sama sekali. Buktikan dengan:</p>
<ul class="hint">
  <li><code>-h</code> (curl menampilkan seluruh daftar opsi/help, bukan mengambil URL)</li>
  <li><code>-V</code> (curl menampilkan info versi &amp; build &mdash; information disclosure)</li>
  <li><code>-K/etc/hosts</code> (curl mencoba membaca <code>/etc/hosts</code> sebagai file
      konfigurasi &mdash; error yang muncul membuktikan file lokal berhasil dibaca)</li>
</ul>
<p class="hint">Diskusi: karena aplikasi ini hanya punya satu parameter yang bisa disuntik,
dampaknya di sini terbatas pada pembuktian konsep. Pada tool nyata seperti <code>git</code>,
<code>tar</code>, atau <code>rsync</code> yang menerima banyak argumen dari input pengguna,
argument injection serupa pernah menghasilkan CVE dengan dampak RCE / arbitrary file write.</p>
</details>

<form method="get">
  <label>URL to check</label><br>
  <input type="text" name="url" value="<?php echo htmlspecialchars($url); ?>">
  <button type="submit">Check</button>
</form>

<?php if ($output !== ''): ?>
<div class="result-box"><?php echo htmlspecialchars($output); ?></div>
<?php endif; ?>

<?php include 'footer.php'; ?>
