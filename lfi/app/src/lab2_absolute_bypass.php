<?php
$title = 'Lab 2: Traversal Diblokir, Absolute Path Lolos';
$page = $_GET['page'] ?? 'pages/home.php';

// NAIVE FILTER: hanya menolak input yang mengandung substring "../". Tidak menyadari bahwa
// path ABSOLUT (mis. "/etc/passwd") tidak butuh "../" sama sekali untuk menunjuk file di luar
// direktori aplikasi.
$blocked = (strpos($page, '../') !== false);
$safe_page = $blocked ? 'pages/home.php' : $page;

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: filter memblokir <code>../</code>, tapi tidak memvalidasi bahwa path yang
dikirim harus relatif. Kalau lokasi file targetnya sudah diketahui, kita tidak perlu traversal
sama sekali:</p>
<ul class="hint">
  <li><code>?page=/etc/passwd</code> (path absolut, tidak mengandung <code>../</code> sehingga
  lolos filter)</li>
</ul>
</details>

<?php if ($blocked): ?>
<div class="error-box">Blocked: traversal sequence "../" terdeteksi pada input.</div>
<?php endif; ?>

<form method="get">
  <label>Page</label><br>
  <input type="text" name="page" value="<?php echo htmlspecialchars($page); ?>">
  <button type="submit">Load</button>
</form>

<div class="result-box">
<?php include $safe_page; ?>
</div>

<?php include 'footer.php'; ?>
