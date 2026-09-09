<?php
require_once __DIR__ . '/lib.php';
$title = 'Lab 10: Dynamic Dispatch dari Input Tak Tepercaya';
$db = load_db();

// Menu resmi yang memang dimaksudkan bisa diakses lewat ?action=...
$MENU_ACTIONS = ['view_profile', 'view_orders'];

function view_profile() {
    return '<p>Profil: <strong>alice</strong> (role: user)</p>';
}
function view_orders() {
    return '<p>Riwayat order: 3 order, total Rp 850.000</p>';
}

// Function INTERNAL - dipanggil oleh kode lain di sistem (mis. proses approval
// manual oleh superadmin lewat cron/CLI terpisah), TIDAK PERNAH dimaksudkan
// bisa dipanggil langsung lewat request HTTP dari mana pun, dan sengaja tidak
// pernah dicantumkan di $MENU_ACTIONS ataupun di navigasi UI manapun.
function grant_admin_role() {
    global $db;
    foreach ($db['users'] as &$u) {
        if ($u['username'] === 'alice') $u['role'] = 'admin';
    }
    unset($u);
    save_db($db);
    return '<p><strong>Role alice berhasil diubah jadi admin.</strong></p>';
}

$action = $_GET['action'] ?? 'view_profile';
$output = null;

// VULNERABLE: dispatcher cuma mengecek apakah SEBUAH FUNGSI dengan nama itu
// ADA di kodebase (function_exists) - bukan mengecek apakah nama itu ada di
// daftar action yang memang dimaksudkan bisa diakses publik ($MENU_ACTIONS).
// Fungsi apa pun yang didefinisikan di file ini (atau ter-include lewat
// require lain) langsung bisa dipanggil hanya dengan menebak namanya.
if (function_exists($action)) {
    $db['dispatch_log'][] = ['action' => $action, 'was_intended' => in_array($action, $MENU_ACTIONS, true), 'time' => date('H:i:s')];
    save_db($db);
    $db = load_db();
    $output = $action();
} else {
    $output = '<p class="hint">Action tidak ditemukan.</p>';
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: panggil sebuah fungsi internal yang TIDAK PERNAH ditautkan di menu manapun
di aplikasi ini. Menu resmi cuma <code>view_profile</code> dan <code>view_orders</code> — coba
tebak nama fungsi lain yang masuk akal ada di kodebase sistem role-management semacam ini (mis.
pola <code>grant_admin_role</code>, umum dipakai internal tooling).</p>
</details>

<h3>Menu</h3>
<p>
  <a href="?action=view_profile">Lihat Profil</a> |
  <a href="?action=view_orders">Lihat Order</a>
</p>

<div class="result-box"><?php echo $output; ?></div>

<h3>Role alice saat ini</h3>
<?php
$alice = null;
foreach ($db['users'] as $u) { if ($u['username'] === 'alice') $alice = $u; }
?>
<p><?php echo $alice['role'] === 'admin' ? '<span class="badge admin">ADMIN</span> (berhasil diubah lewat dispatch!)' : '<span class="badge user">user</span>'; ?></p>

<?php if (!empty($db['dispatch_log'])): ?>
<h3>Log Dispatch</h3>
<table class="data-table">
<tr><th>Waktu</th><th>Action dipanggil</th><th>Ada di menu resmi?</th></tr>
<?php foreach (array_reverse($db['dispatch_log']) as $e): ?>
<tr>
  <td><?php echo htmlspecialchars($e['time']); ?></td>
  <td><code><?php echo htmlspecialchars($e['action']); ?></code></td>
  <td><?php echo $e['was_intended'] ? 'Ya' : '<strong>TIDAK — dipanggil di luar menu</strong>'; ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<p class="hint">Kenapa berhasil: dispatcher memakai <code>function_exists($action)</code> untuk
memutuskan apakah suatu action boleh dijalankan — padahal <code>function_exists()</code> cuma
menjawab "apakah fungsi ini ADA di kodebase", sama sekali bukan "apakah fungsi ini memang
dimaksudkan bisa dipanggil dari luar". Setiap fungsi yang pernah didefinisikan di mana pun dalam
proses PHP yang sama (termasuk fungsi internal/administratif yang sengaja tidak pernah ditautkan
ke UI apa pun) otomatis jadi endpoint yang bisa dipanggil siapa saja, asal tahu/menebak namanya.
Integritas alur eksekusi program — kode APA yang boleh berjalan sebagai respons atas request
tertentu — jadi ditentukan oleh nama yang dikirim client, bukan oleh desain aplikasi. Perbaikannya:
selalu pakai <strong>allowlist eksplisit</strong> (mis. <code>in_array($action, $MENU_ACTIONS,
true)</code>) sebelum memanggil fungsi apa pun berdasarkan nama dari input pengguna.</p>

<?php include 'footer.php'; ?>
