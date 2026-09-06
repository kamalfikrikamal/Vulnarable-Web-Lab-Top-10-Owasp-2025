<?php
$title = 'Lab 1: Command Injection (visible output)';
$host = $_GET['host'] ?? '';
$output = '';

if ($host !== '') {
    // VULNERABLE: user input concatenated directly into a shell command string.
    $cmd = "ping -c 2 " . $host;
    $output = shell_exec($cmd . ' 2>&1');
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: fitur "network diagnostic" ini menjalankan
<code>ping -c 2 &lt;host&gt;</code> dan menampilkan hasilnya. Tidak ada validasi/sanitasi
input, sehingga kamu bisa menyisipkan perintah tambahan lewat metakarakter shell. Coba:</p>
<ul class="hint">
  <li><code>127.0.0.1; id</code></li>
  <li><code>127.0.0.1 &amp;&amp; whoami</code></li>
  <li><code>127.0.0.1 | cat /etc/passwd</code></li>
  <li><code>$(whoami)</code> atau <code>`whoami`</code></li>
</ul>
</details>

<form method="get">
  <label>Host to ping</label><br>
  <input type="text" name="host" value="<?php echo htmlspecialchars($host); ?>">
  <button type="submit">Ping</button>
</form>

<?php if ($output !== ''): ?>
<div class="result-box"><?php echo htmlspecialchars($output); ?></div>
<?php endif; ?>

<?php include 'footer.php'; ?>
