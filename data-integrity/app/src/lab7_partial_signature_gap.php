<?php
require_once __DIR__ . '/lib.php';
$title = 'Lab 7: Signature Cuma Menutupi Sebagian Data';
define('TRANSFER_SECRET', 'internal-transfer-signing-key-2024');

// Instruksi transfer yang "sudah disetujui" (mensimulasikan pre-approval dari
// app mobile/langkah otorisasi sebelumnya) - datang lengkap dengan signature.
$default_amount = 500000;
$default_currency = 'IDR';
$default_recipient = '1111111111'; // rekening pemilik sendiri
// VULNERABLE: signature CUMA menandatangani $amount - field currency & recipient
// TIDAK ikut tercakup dalam apa yang ditandatangani sama sekali.
$default_signature = hash_hmac('sha256', (string)$default_amount, TRANSFER_SECRET);

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (string)($_POST['amount'] ?? '');
    $currency = (string)($_POST['currency'] ?? '');
    $recipient = (string)($_POST['recipient'] ?? '');
    $signature = (string)($_POST['signature'] ?? '');

    $expected_sig = hash_hmac('sha256', $amount, TRANSFER_SECRET);
    $valid = hash_equals($expected_sig, $signature);

    $result = compact('amount', 'currency', 'recipient', 'signature', 'valid');
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: eksekusi transfer ke rekening BERBEDA dari rekening yang aslinya disetujui
(<code><?php echo $default_recipient; ?></code>), TANPA membuat signature-nya menjadi tidak
valid. Form di bawah sudah pre-filled dengan instruksi transfer yang sah lengkap dengan
signature-nya. Ubah field <code>recipient</code> (dan/atau <code>currency</code>) sebelum submit
— JANGAN ubah <code>amount</code> maupun <code>signature</code>.</p>
</details>

<h3>Instruksi Transfer (pre-approved, siap dieksekusi)</h3>
<form method="post">
  <label>Amount</label><br>
  <input type="text" name="amount" value="<?php echo htmlspecialchars((string)$default_amount); ?>"><br>
  <label>Currency</label><br>
  <input type="text" name="currency" value="<?php echo htmlspecialchars($default_currency); ?>"><br>
  <label>Recipient account</label><br>
  <input type="text" name="recipient" value="<?php echo htmlspecialchars($default_recipient); ?>"><br>
  <label>Signature (dari langkah otorisasi sebelumnya)</label><br>
  <input type="text" name="signature" value="<?php echo htmlspecialchars($default_signature); ?>" style="width:100%;max-width:600px;"><br>
  <button type="submit">Eksekusi Transfer</button>
</form>

<?php if ($result): ?>
<div class="<?php echo $result['valid'] ? ($result['recipient'] !== $default_recipient || $result['currency'] !== $default_currency ? 'error-box' : 'ok-box') : 'error-box'; ?>">
  Amount: <?php echo htmlspecialchars($result['amount']); ?>
  <?php echo htmlspecialchars($result['currency']); ?>
  &rarr; Recipient: <code><?php echo htmlspecialchars($result['recipient']); ?></code><br>
  Signature valid: <strong><?php echo $result['valid'] ? 'YA' : 'TIDAK'; ?></strong>
  <?php if ($result['valid'] && ($result['recipient'] !== $default_recipient || $result['currency'] !== $default_currency)): ?>
    <br><strong>⚠ Signature tetap valid meski recipient/currency sudah diubah dari instruksi aslinya!</strong>
  <?php endif; ?>
</div>
<?php endif; ?>

<p class="hint">Kenapa berhasil: signature dihitung dengan
<code>hash_hmac('sha256', $amount, SECRET)</code> — cuma mencakup field <code>amount</code>.
Server memverifikasi signature dengan benar (pakai <code>hash_equals()</code>, tidak ada masalah
timing di sini) dan TIDAK PERNAH salah menerima signature yang salah untuk amount yang berbeda —
tapi karena <code>currency</code> dan <code>recipient</code> tidak pernah ikut ditandatangani,
mengubah keduanya sama sekali tidak mempengaruhi validitas signature. Attacker tidak perlu
memalsukan atau memecahkan signature apa pun — cukup mengubah data yang KEBETULAN tidak tercakup
olehnya. Pelajaran utamanya: signature/checksum harus mencakup SELURUH data yang integritasnya
ingin dijamin (canonicalization yang benar), bukan cuma sebagian field yang dianggap "paling
penting" oleh developer.</p>

<?php include 'footer.php'; ?>
