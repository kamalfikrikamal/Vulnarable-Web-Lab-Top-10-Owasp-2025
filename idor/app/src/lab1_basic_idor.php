<?php
$title = 'Lab 1: Basic IDOR';
include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }

$id = $_GET['id'] ?? $me['id'];
// VULNERABLE: fetches whichever invoice ID is requested, never checks that
// the invoice actually belongs to the logged-in user ($me['id']).
$invoice = null;
foreach ($db['invoices'] as $inv) { if ((string)$inv['id'] === (string)$id) { $invoice = $inv; break; } }
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: kamu login sebagai <strong><?php echo htmlspecialchars($me['username']); ?></strong>
dan invoice milikmu ada di <code>id=<?php
  foreach ($db['invoices'] as $inv) if ($inv['user_id'] == $me['id']) echo $inv['id'];
?></code>. Coba ubah parameter <code>id</code> di URL menjadi ID invoice lain (mis.
<code>5001</code>, <code>5002</code>, <code>5003</code>) untuk melihat data milik user lain.</p>
</details>

<form method="get">
  <label>Invoice ID</label><br>
  <input type="text" name="id" value="<?php echo htmlspecialchars($id); ?>">
  <button type="submit">Lihat Invoice</button>
</form>

<?php if ($invoice): ?>
<table class="data-table">
  <tr><th>Invoice ID</th><td><?php echo (int)$invoice['id']; ?></td></tr>
  <tr><th>Pemilik (user_id)</th><td><?php echo (int)$invoice['user_id']; ?></td></tr>
  <tr><th>Item</th><td><?php echo htmlspecialchars($invoice['item']); ?></td></tr>
  <tr><th>Jumlah</th><td>$<?php echo number_format($invoice['amount'], 2); ?></td></tr>
</table>
<?php if ($invoice['user_id'] != $me['id']): ?>
<div class="error-box">Ini bukan invoice milikmu! Server tidak pernah memverifikasi kepemilikan &mdash; ini bukti IDOR.</div>
<?php endif; ?>
<?php elseif (isset($_GET['id'])): ?>
<div class="error-box">Invoice tidak ditemukan.</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
