<?php
require 'data.php';
$cat_id = $_GET['cat'] ?? '';
$vuln_id = $_GET['vuln'] ?? '';
$cat = find_category($cat_id);
$vuln = find_vuln($cat_id, $vuln_id);

if (!$cat || !$vuln) {
    http_response_code(404);
    $title = 'Tidak ditemukan';
    include 'header.php';
    echo '<div class="container"><p>Halaman tidak ditemukan. <a href="index.php">Kembali ke portal</a>.</p></div>';
    include 'footer.php';
    exit;
}

$title = $vuln['title'] . ' - Portal Lab Pentest Web';
include 'header.php';
?>

<div class="breadcrumb">
  <a href="index.php">Portal</a> &raquo;
  <a href="category.php?id=<?php echo urlencode($cat_id); ?>"><?php echo htmlspecialchars($cat['title']); ?></a> &raquo;
  <?php echo htmlspecialchars($vuln['title']); ?>
</div>

<div class="container">
  <h1><?php echo htmlspecialchars($vuln['title']); ?></h1>

  <div class="explain-box">
    <?php echo $vuln['description']; ?>
  </div>

  <h2>Pilih lab</h2>
  <div class="grid">
    <?php foreach ($vuln['labs'] as $lab_id => $lab): ?>
      <a class="card" href="lab.php?cat=<?php echo urlencode($cat_id); ?>&vuln=<?php echo urlencode($vuln_id); ?>&lab=<?php echo urlencode($lab_id); ?>">
        <h3><?php echo htmlspecialchars($lab['title']); ?></h3>
        <p><?php echo htmlspecialchars($lab['summary']); ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
