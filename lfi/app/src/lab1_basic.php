<?php
$title = 'Lab 1: Basic LFI (simple case)';
$page = $_GET['page'] ?? 'pages/home.php';
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: parameter <code>page</code> dimasukkan langsung ke <code>include()</code>
tanpa validasi apa pun. Coba baca file sistem di luar direktori aplikasi:</p>
<ul class="hint">
  <li><code>?page=../../../../etc/passwd</code></li>
  <li><code>?page=../secret/config.php</code> (file konfigurasi di luar webroot &mdash;
  perhatikan isinya tidak akan ikut tercetak sebagai teks PHP karena benar-benar dieksekusi
  sebagai kode PHP oleh <code>include()</code>; gunakan Lab 7 untuk membaca *source code*-nya
  lewat wrapper <code>php://filter</code>)</li>
</ul>
</details>

<p class="page-nav">
  <a href="?page=pages/home.php">Home</a>
  <a href="?page=pages/about.php">About</a>
  <a href="?page=pages/contact.php">Contact</a>
</p>

<form method="get">
  <label>Page</label><br>
  <input type="text" name="page" value="<?php echo htmlspecialchars($page); ?>">
  <button type="submit">Load</button>
</form>

<div class="result-box">
<?php
// VULNERABLE: input pengguna langsung diserahkan ke include(), tanpa whitelist,
// tanpa basename(), tanpa pengecekan direktori sama sekali.
include $page;
?>
</div>

<?php include 'footer.php'; ?>
