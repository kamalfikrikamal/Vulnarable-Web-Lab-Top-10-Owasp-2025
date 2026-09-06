<?php
require 'data.php';
$title = 'Portal Lab Pentest Web - OWASP Top 10:2025';
$categories = owasp_categories();
include 'header.php';
?>

<div class="container">
  <h1>OWASP Top 10:2025</h1>
  <p>Pilih salah satu kategori di bawah untuk mulai belajar. Kategori yang berlabel
  <span class="badge badge-active">Tersedia</span> sudah punya penjelasan dan lab praktik;
  kategori lain masih <span class="badge badge-soon">Segera Hadir</span> dan akan menyusul.</p>

  <div class="grid">
    <?php foreach ($categories as $id => $cat): ?>
      <?php if ($cat['status'] === 'active'): ?>
        <a class="card" href="category.php?id=<?php echo urlencode($id); ?>">
          <span class="badge badge-code"><?php echo htmlspecialchars($cat['code']); ?></span>
          <span class="badge badge-active">Tersedia</span>
          <h3><?php echo htmlspecialchars($cat['title']); ?></h3>
          <p><?php echo htmlspecialchars($cat['summary']); ?></p>
        </a>
      <?php else: ?>
        <div class="card disabled">
          <span class="badge badge-code"><?php echo htmlspecialchars($cat['code']); ?></span>
          <span class="badge badge-soon">Segera Hadir</span>
          <h3><?php echo htmlspecialchars($cat['title']); ?></h3>
          <p><?php echo htmlspecialchars($cat['summary']); ?></p>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
