<?php
$title = 'Lab 6: Header Keamanan Hilang - Clickjacking';
require_once __DIR__ . '/lib.php';
$db = load_db();

// VULNERABLE: halaman ini SENGAJA tidak mengirim header X-Frame-Options
// maupun Content-Security-Policy dengan frame-ancestors. Tanpa header ini,
// browser mengizinkan halaman ini di-embed di dalam <iframe> milik domain
// mana pun - termasuk domain attacker - sehingga bisa jadi target
// clickjacking (UI redress attack).
//
// Mitigasi seharusnya:
//   header('X-Frame-Options: DENY');
//   header("Content-Security-Policy: frame-ancestors 'self'");
// tapi baris itu sengaja tidak ada di sini.

$transfer_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_transfer'])) {
    $amount = 5000000;
    $db['transfers'][] = [
        'amount' => $amount,
        'to' => 'rekening-tujuan-attacker',
        'time' => date('Y-m-d H:i:s'),
    ];
    save_db($db);
    $transfer_msg = 'Transfer berhasil.';
}

include 'header.php';
?>

<p>Halaman "Transfer Dana" di bawah ini tidak mengirim header <code>X-Frame-Options</code>
maupun <code>Content-Security-Policy: frame-ancestors</code> — artinya halaman ini bisa
di-embed di dalam <code>&lt;iframe&gt;</code> pada situs mana pun, termasuk situs attacker, lalu
disamarkan/ditumpuk di bawah elemen UI lain agar klik korban yang sebenarnya mengarah ke tombol
"Konfirmasi Transfer" yang tersembunyi. Coba lihat
<a href="lab6_attacker_iframe.php">halaman attacker (PoC)</a> untuk melihat bagaimana serangan
ini terlihat dari sisi korban.</p>

<h3>Transfer Dana</h3>
<form method="post">
  <label>Jumlah</label><br>
  <input type="text" value="Rp 5.000.000" disabled><br>
  <button type="submit" name="confirm_transfer" value="1" id="transfer-btn">Konfirmasi Transfer Rp 5.000.000 ke rekening tujuan</button>
</form>

<?php if ($transfer_msg): ?>
<div class="ok-box"><?php echo htmlspecialchars($transfer_msg); ?></div>
<?php endif; ?>

<h3>Log Transfer</h3>
<?php if (empty($db['transfers'])): ?>
<p class="hint">Belum ada transfer.</p>
<?php else: ?>
<table class="data-table">
  <tr><th>Waktu</th><th>Jumlah</th><th>Tujuan</th></tr>
  <?php foreach (array_reverse($db['transfers']) as $t): ?>
  <tr>
    <td><?php echo htmlspecialchars($t['time']); ?></td>
    <td>Rp <?php echo number_format($t['amount'], 0, ',', '.'); ?></td>
    <td><?php echo htmlspecialchars($t['to']); ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Buka <a href="lab6_attacker_iframe.php">halaman attacker (PoC)</a>, lalu klik
tombol "Klaim Hadiah" di sana (jangan submit form di halaman ini secara langsung). Setelah itu
kembali ke halaman ini dan lihat tabel Log Transfer — kalau ada entri baru yang muncul padahal
kamu tidak pernah benar-benar bermaksud menekan tombol "Konfirmasi Transfer", berarti clickjacking
berhasil.</p>
</details>

<?php include 'footer.php'; ?>
