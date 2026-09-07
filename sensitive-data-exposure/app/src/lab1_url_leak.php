<?php
$title = 'Lab 1: Data Sensitif di URL';
require_once __DIR__ . '/lib.php';
$db = load_db();
$user = find_user($db, current_username());
$card = $_GET['card'] ?? $user['card'];

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman "receipt" ini menaruh nomor kartu kredit langsung di query string
URL (<code>?card=...</code>) supaya "bisa dibagikan lewat link". Masalahnya, browser secara
otomatis mengirim URL lengkap (termasuk query string) sebagai header <code>Referer</code> ke
resource pihak ketiga mana pun yang dimuat di halaman ini &mdash; widget analytics, font,
gambar, dsb. Di lab ini, resource pihak ketiga itu disimulasikan lewat
<code>analytics_beacon.php</code> (bayangkan ini domain analytics sungguhan, mis.
<code>analytics.example.com</code>). Scroll ke bawah untuk lihat "log" yang tertangkap di sisi
mereka.</p>
<p class="hint">URL yang bocor juga tersimpan di: riwayat browser, log server proxy/CDN, dan
bookmark yang dibagikan ke orang lain secara tidak sengaja.</p>
</details>

<p>URL halaman ini: <code>lab1_url_leak.php?card=<?php echo htmlspecialchars($card); ?></code></p>

<div class="result-box">Receipt untuk <?php echo htmlspecialchars(current_username()); ?>: kartu berakhiran <?php echo htmlspecialchars(substr($card, -4)); ?>, transaksi berhasil.</div>

<!-- Widget "analytics pihak ketiga" - di dunia nyata ini akan menjadi domain
     eksternal sungguhan (mis. tracking pixel iklan), tapi disimulasikan
     lokal di sini supaya lab tetap bisa berjalan tanpa koneksi keluar. -->
<img src="analytics_beacon.php" width="1" height="1" style="display:none">

<h3>Log sisi "pihak ketiga" (simulasi analytics.example.com)</h3>
<div class="result-box"><?php
$log = array_slice($db['referer_log'], -5);
echo $log ? htmlspecialchars(implode("\n", $log)) : '(belum ada data - refresh halaman ini sekali lagi)';
?></div>

<?php include 'footer.php'; ?>
