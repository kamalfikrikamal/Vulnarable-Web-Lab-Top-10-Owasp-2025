<?php
$title = 'Lab 4: Filter Lalu URL-decode Berlebih';
$page = $_GET['page'] ?? 'pages/home.php';

// NAIVE FILTER: PHP sudah men-decode query string satu kali secara otomatis sebelum masuk ke
// $_GET. Filter di bawah ini membuang "../" dari nilai yang SUDAH di-decode itu, TAPI lalu
// melakukan urldecode() SEKALI LAGI setelahnya - inilah bug-nya: kalau payload di-encode DUA
// kali, decode otomatis PHP hanya membuka satu lapis (jadi filter tidak melihat "../" sama
// sekali karena masih dalam bentuk %2e%2e%2f), lalu urldecode() manual ini membuka lapis
// kedua dan memunculkan "../" SETELAH filter selesai bekerja.
$stripped = str_replace('../', '', $page);
$safe_page = urldecode($stripped);

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: aplikasi men-decode input sekali lagi setelah filter traversal
dijalankan. Kirim payload yang di-<strong>double URL-encode</strong> supaya lolos filter,
lalu baru "muncul" jadi <code>../</code> setelah decode kedua:</p>
<ul class="hint">
  <li><code>%252e%252e%252f</code> = double-encode dari <code>../</code>
  (<code>%25</code> adalah encode dari karakter <code>%</code> itu sendiri)</li>
  <li>Payload lengkap: <code>?page=%252e%252e%252f%252e%252e%252f%252e%252e%252fetc%252fpasswd</code></li>
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
