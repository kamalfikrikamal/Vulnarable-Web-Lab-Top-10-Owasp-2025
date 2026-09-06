<?php
require 'data.php';
$cat_id = $_GET['cat'] ?? '';
$vuln_id = $_GET['vuln'] ?? '';
$lab_id = $_GET['lab'] ?? '';
$cat = find_category($cat_id);
$vuln = find_vuln($cat_id, $vuln_id);
$lab = find_lab($cat_id, $vuln_id, $lab_id);

if (!$cat || !$vuln || !$lab) {
    http_response_code(404);
    $title = 'Tidak ditemukan';
    include 'header.php';
    echo '<div class="container"><p>Halaman tidak ditemukan. <a href="index.php">Kembali ke portal</a>.</p></div>';
    include 'footer.php';
    exit;
}

$lab_url = lab_url($lab['app'], $lab['path']);
$title = $lab['title'] . ' - Portal Lab Pentest Web';
include 'header.php';
?>

<div class="breadcrumb">
  <a href="index.php">Portal</a> &raquo;
  <a href="category.php?id=<?php echo urlencode($cat_id); ?>"><?php echo htmlspecialchars($cat['title']); ?></a> &raquo;
  <a href="vuln.php?cat=<?php echo urlencode($cat_id); ?>&vuln=<?php echo urlencode($vuln_id); ?>"><?php echo htmlspecialchars($vuln['title']); ?></a> &raquo;
  <?php echo htmlspecialchars($lab['title']); ?>
</div>

<div class="container">
  <h1><?php echo htmlspecialchars($lab['title']); ?></h1>

  <div class="explain-box">
    <?php echo $lab['description']; ?>
  </div>

  <a class="btn" href="<?php echo htmlspecialchars($lab_url); ?>" target="_blank" rel="noopener">
    Mulai Lab &rarr;
  </a>
  <p style="font-size:13px;color:#6b7280;margin-top:8px;">
    Lab akan terbuka di tab baru (<?php echo htmlspecialchars($lab_url); ?>) supaya halaman
    penjelasan ini tetap bisa dijadikan referensi.
  </p>
</div>

<?php include 'footer.php'; ?>
