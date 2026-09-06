<?php
$title = 'Lab 2: Blind Command Injection (time-based)';
$domain = $_GET['domain'] ?? '';
$message = '';

if ($domain !== '') {
    // VULNERABLE: user input concatenated into a shell command. The command's
    // output is discarded and NOTHING about the result is shown back to the user -
    // the only way to detect/exploit this is via a time-based side channel.
    $cmd = "echo checking domain " . $domain . " > /tmp/domain_check.log 2>&1";
    $start = microtime(true);
    shell_exec($cmd);
    $elapsed = round(microtime(true) - $start, 2);
    $message = "Request submitted for processing. (server time: {$elapsed}s)";
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: fitur "domain availability check" ini tidak pernah menampilkan output
maupun error apa pun &mdash; responsnya selalu generik. Karena itu kamu tidak bisa memakai
teknik in-band. Gunakan perintah <code>sleep</code> untuk membuktikan eksekusi dan mengukur
delay pada response time. Coba:</p>
<ul class="hint">
  <li><code>example.com; sleep 5</code></li>
  <li><code>example.com &amp;&amp; sleep 5</code></li>
  <li><code>$(sleep 5)</code></li>
</ul>
</details>

<form method="get">
  <label>Domain</label><br>
  <input type="text" name="domain" value="<?php echo htmlspecialchars($domain); ?>">
  <button type="submit">Check</button>
</form>

<?php if ($message !== ''): ?>
<div class="result-box"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php include 'footer.php'; ?>
