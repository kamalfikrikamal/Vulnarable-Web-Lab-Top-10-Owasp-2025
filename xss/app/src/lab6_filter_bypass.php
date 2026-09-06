<?php
$title = 'Lab 6: XSS Filter Bypass';
$input = $_GET['input'] ?? '';

// NAIVE FILTER: blocks the literal, case-sensitive substring "<script>" only.
// It does not handle case variation, other tags, or event-handler attributes.
$filtered = str_replace('<script>', '', $input);

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: ada filter naif yang menghapus string <code>&lt;script&gt;</code>
(persis, case-sensitive) dari input, lalu hasilnya dicetak tanpa encoding lain. Filter ini bisa
dilewati dengan berbagai cara. Coba salah satu:</p>
<ul class="hint">
  <li>Ubah kapitalisasi: <code>&lt;ScRiPt&gt;alert(1)&lt;/ScRiPt&gt;</code></li>
  <li>Pakai tag lain: <code>&lt;img src=x onerror=alert(1)&gt;</code></li>
  <li>Pakai event handler tanpa tag script: <code>&lt;svg onload=alert(1)&gt;</code></li>
  <li>Nested tag: <code>&lt;scr&lt;script&gt;ipt&gt;alert(1)&lt;/scr&lt;script&gt;ipt&gt;</code></li>
</ul>
</details>

<form method="get">
  <label>Input</label><br>
  <input type="text" name="input" value="">
  <button type="submit">Submit</button>
</form>

<?php if ($input !== ''): ?>
<div class="result-box">
You submitted: <?php echo $filtered; /* VULNERABLE: only <script> literal removed */ ?>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
