<?php
$title = 'Lab 3: Traversal Sequence Dihapus Non-recursive';
$page = $_GET['page'] ?? 'pages/home.php';

// NAIVE FILTER: str_replace() hanya menghapus "../" dalam SATU kali pass, bukan berulang
// sampai tidak ada lagi kemunculannya. Input seperti "....//" akan tersisa "../" setelah
// satu kali penghapusan.
$safe_page = str_replace('../', '', $page);

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: <code>../</code> dihapus, tapi hanya satu kali (non-recursive). Sisipkan
karakter ekstra supaya setelah satu kali penghapusan, hasilnya tetap jadi <code>../</code>:</p>
<ul class="hint">
  <li><code>....//....//....//....//etc/passwd</code> &mdash; setelah <code>../</code> pertama
  yang cocok dihapus dari <code>....//</code>, sisanya adalah <code>../</code></li>
</ul>
</details>

<form method="get">
  <label>Page</label><br>
  <input type="text" name="page" value="<?php echo htmlspecialchars($page); ?>">
  <button type="submit">Load</button>
</form>

<div class="result-box">
<?php include $safe_page; ?>
</div>

<?php include 'footer.php'; ?>
