<?php
$title = 'Lab 4: Session Token di URL';
require_once __DIR__ . '/lib.php';
$db = load_db();

if (isset($_GET['login'])) {
    $token = bin2hex(random_bytes(8));
    $db['sessions'][$token] = 'alice';
    save_db($db);
    header('Location: lab4_token_in_url.php?authtoken=' . $token);
    exit;
}

// VULNERABLE: authentication token accepted via the URL query string
// instead of (or in addition to) a cookie - "easy to share as a link".
$token = $_GET['authtoken'] ?? null;
$logged_in_as = $token ? username_for_token($db, $token) : null;

if ($token) {
    // Every web server logs the full request URI - including query
    // strings - to its access log by default. This simulates that log.
    if (!isset($db['access_log'])) $db['access_log'] = [];
    $db['access_log'][] = date('H:i:s') . '  GET /session-management/lab4_token_in_url.php?authtoken=' . $token . ' - 200';
    $db['access_log'] = array_slice($db['access_log'], -10);
    save_db($db);
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: klik "Login" di bawah &mdash; kamu akan diarahkan ke URL yang membawa
token otentikasi lengkap di query string (<code>?authtoken=...</code>), bukan lewat cookie.
Setiap web server (Apache/nginx) mencatat URL lengkap yang diakses (termasuk query string) ke
access log-nya secara default. Log itu sering bisa dibaca oleh pihak yang seharusnya tidak
berhak melihat sesi pengguna: tim support, log aggregator pihak ketiga (Datadog/Splunk), atau
siapa pun yang mendapat akses log lewat cara lain. Scroll ke bawah untuk melihat simulasi log
itu &mdash; salin token dari salah satu baris (bisa punyamu sendiri untuk demo, di dunia nyata
ini akan jadi token pengguna lain), lalu buka <code>?authtoken=&lt;token&gt;</code> di jendela
browser lain (mode incognito) untuk membuktikan sesi bisa dibajak hanya dari log.</p>
</details>

<?php if ($logged_in_as): ?>
<p>Login sebagai: <strong><?php echo htmlspecialchars($logged_in_as); ?></strong></p>
<p>URL kamu saat ini membawa token lengkap di address bar &mdash; coba salin URL ini dan buka
di browser lain untuk membuktikan sesi ini bisa dipindahkan hanya lewat URL.</p>
<?php else: ?>
<p><a href="?login=1">Login sebagai alice</a></p>
<?php endif; ?>

<h3>Simulasi Server Access Log (dilihat tim ops/support/log aggregator)</h3>
<div class="result-box"><?php
$log = $db['access_log'] ?? [];
echo $log ? htmlspecialchars(implode("\n", $log)) : '(belum ada data - login dulu)';
?></div>

<?php include 'footer.php'; ?>
