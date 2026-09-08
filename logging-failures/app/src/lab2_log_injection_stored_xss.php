<?php
$title = 'Lab 2: Log Injection -> Stored XSS di Dashboard Admin';
include 'header.php';

$view = $_GET['view'] ?? 'search';
$q = $_GET['q'] ?? null;

if ($q !== null) {
    // VULNERABLE: query pencarian mentah ditulis ke search.log tanpa encoding
    // apa pun. IP dipalsukan/statis di sini demi kesederhanaan demo.
    $fake_ip = '10.0.' . rand(0, 255) . '.' . rand(1, 254);
    $line = '[' . date('Y-m-d H:i:s') . '] SEARCH: query="' . $q . '", ip=' . $fake_ip;
    append_log('search.log', $line);
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: fitur "Cari Produk" di bawah mencatat setiap query pencarian ke
<code>data/search.log</code> memakai nilai mentah dari <code>$_GET['q']</code>, tanpa encoding
apa pun. Lebih jauh lagi, ada "Admin: Log Dashboard" yang membaca ulang file log itu dan me-render
bagian query-nya langsung ke HTML lewat <code>echo $query;</code> &mdash; <strong>tanpa
<code>htmlspecialchars()</code></strong> &mdash; karena tim internal menganggap "ini kan cuma log
kita sendiri, aman".</p>
<p class="hint">Bedanya dengan lab XSS biasa: di sini pintu masuknya bukan kotak komentar publik,
tapi <strong>pipeline logging</strong> &mdash; log tetap berisi input pengguna yang tidak
tepercaya, dan harus di-encode saat dirender, sama seperti data user lainnya.</p>
<p class="hint">Coba cari: <code>&lt;img src=x onerror="document.title='PWNED-VIA-LOG'"&gt;</code>
lalu buka "Admin: Log Dashboard" di bawah dan lihat judul tab berubah &mdash; scriptnya benar-benar
jalan di konteks halaman admin.</p>
</details>

<h3>Cari Produk</h3>
<form method="get">
  <input type="hidden" name="view" value="search">
  <label>Kata kunci</label><br>
  <input type="text" name="q" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
  <button type="submit">Cari</button>
</form>
<?php if ($q !== null): ?>
<div class="ok-box">Query "<?php echo htmlspecialchars($q); ?>" dicatat ke search.log. Sekarang buka Admin: Log Dashboard di bawah.</div>
<?php endif; ?>

<h3>Admin: Log Dashboard</h3>
<p class="hint">Ini mensimulasikan tool internal yang dipakai admin/analis untuk memantau
aktivitas pencarian dari <code>search.log</code>. Setiap baris di-parse lalu bagian query-nya
di-echo langsung ke HTML tanpa escaping.</p>

<?php if ($view === 'search'): ?>
<p><a class="btn" href="?view=admin_dashboard">Buka Admin: Log Dashboard &rarr;</a></p>
<?php else: ?>
<div style="border:1px solid #ddd; border-radius:8px; padding:12px; background:#fff;">
<?php
$raw = read_log('search.log');
$lines = array_filter(explode("\n", $raw));
if (!$lines) {
    echo '<p><em>Belum ada data pencarian.</em></p>';
}
foreach ($lines as $line) {
    // Parse balik query="..." dari baris log.
    if (preg_match('/query="(.*)", ip=(\S+)/', $line, $m)) {
        $query = $m[1];
        $ip = $m[2];
        // VULNERABLE: di-echo mentah, TANPA htmlspecialchars(). Log dianggap
        // "data internal tepercaya" padahal isinya tetap berasal dari input user.
        echo '<div style="border-bottom:1px solid #eee; padding:6px 0;">Query: ' . $query . ' &mdash; <small>ip=' . htmlspecialchars($ip) . '</small></div>';
    }
}
?>
</div>
<p><a href="?view=search">&larr; Kembali ke pencarian</a></p>
<?php endif; ?>

<?php include 'footer.php'; ?>
