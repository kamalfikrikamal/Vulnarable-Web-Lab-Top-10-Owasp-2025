<?php
$title = 'Lab 1: Reflected XSS (HTML body)';
$q = $_GET['q'] ?? '';
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: parameter <code>q</code> dicetak langsung ke body HTML tanpa di-escape.
Coba: <code>?q=&lt;script&gt;alert(document.domain)&lt;/script&gt;</code> atau
<code>?q=&lt;img src=x onerror=alert(1)&gt;</code></p>
</details>

<form method="get">
  <label>Search</label><br>
  <input type="text" name="q" value="">
  <button type="submit">Search</button>
</form>

<?php if ($q !== ''): ?>
<div class="result-box">
Search results for: <?php echo $q; /* VULNERABLE: no htmlspecialchars() */ ?>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
