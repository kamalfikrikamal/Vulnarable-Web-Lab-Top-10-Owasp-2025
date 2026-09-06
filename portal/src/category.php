<?php
require 'data.php';
$cat_id = $_GET['id'] ?? '';
$cat = find_category($cat_id);

if (!$cat) {
    http_response_code(404);
    $title = 'Kategori tidak ditemukan';
    include 'header.php';
    echo '<div class="container"><p>Kategori tidak ditemukan. <a href="index.php">Kembali ke portal</a>.</p></div>';
    include 'footer.php';
    exit;
}

$title = $cat['title'] . ' - Portal Lab Pentest Web';
include 'header.php';
?>

<div class="breadcrumb"><a href="index.php">Portal</a> &raquo; <?php echo htmlspecialchars($cat['title']); ?></div>

<div class="container">
  <span class="badge badge-code"><?php echo htmlspecialchars($cat['code']); ?></span>
  <h1><?php echo htmlspecialchars($cat['title']); ?></h1>

  <div class="explain-box">
    <?php echo $cat['description']; ?>
  </div>

  <?php if (!empty($cat['vulns'])): ?>
    <h2>Pilih jenis kerentanan</h2>
    <div class="grid">
      <?php foreach ($cat['vulns'] as $vuln_id => $vuln): ?>
        <a class="card" href="vuln.php?cat=<?php echo urlencode($cat_id); ?>&vuln=<?php echo urlencode($vuln_id); ?>">
          <h3><?php echo htmlspecialchars($vuln['title']); ?></h3>
          <p><?php echo htmlspecialchars($vuln['summary']); ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p><em>Lab untuk kategori ini belum tersedia.</em></p>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
