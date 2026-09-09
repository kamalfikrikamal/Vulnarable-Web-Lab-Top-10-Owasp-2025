<?php
$title = 'Lab 11: Over-Refund Lewat Kuantitas Return';
include 'header.php';

$order = $db['refund_orders'][0];
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABLE: kuantitas yang diminta untuk di-return TIDAK PERNAH
    // dibandingkan dengan kuantitas asli yang benar-benar dibeli di order ini
    // (juga tidak dikurangi dari total yang sudah pernah di-refund
    // sebelumnya) - server percaya begitu saja angka yang dikirim client.
    $return_qty = max(1, (int)($_POST['return_qty'] ?? 1));
    $refund_amount = $order['price'] * $return_qty;

    $db['wallet_balance'] += $refund_amount;
    $db['refund_orders'][0]['total_refunded_qty'] += $return_qty;
    save_db($db);
    $db = load_db();
    $order = $db['refund_orders'][0];

    $msg = "Refund sebesar " . rupiah($refund_amount) . " untuk {$return_qty}x {$order['product']} berhasil dikreditkan ke wallet.";
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: order #<?php echo $order['id']; ?> cuma berisi
<strong><?php echo $order['quantity']; ?>x <?php echo htmlspecialchars($order['product']); ?></strong>
(lihat tabel di bawah). Ajukan return dengan kuantitas jauh lebih besar dari itu (mis. 50), lalu
lihat berapa yang benar-benar dikreditkan ke wallet.</p>
</details>

<?php if ($msg): ?><div class="error-box"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<h3>Detail Order #<?php echo $order['id']; ?></h3>
<table class="data-table">
<tr><th>Produk</th><th>Harga/unit</th><th>Kuantitas dibeli</th><th>Total sudah di-refund (unit)</th></tr>
<tr>
  <td><?php echo htmlspecialchars($order['product']); ?></td>
  <td><?php echo rupiah($order['price']); ?></td>
  <td><?php echo $order['quantity']; ?></td>
  <td><?php echo $order['total_refunded_qty']; ?></td>
</tr>
</table>

<h3>Ajukan Return / Refund</h3>
<form method="post">
  <label>Kuantitas yang ingin di-return</label><br>
  <input type="number" name="return_qty" value="1" min="1">
  <button type="submit">Ajukan Return</button>
</form>

<p>Saldo wallet saat ini: <strong><?php echo rupiah($db['wallet_balance']); ?></strong></p>

<p class="hint">Kenapa berhasil: alur return/refund menghitung jumlah uang yang dikembalikan
murni dari kuantitas yang DIMINTA client (<code>return_qty</code>), tanpa pernah membandingkannya
dengan kuantitas yang BENAR-BENAR dibeli di order tersebut (dan tanpa mengurangi jumlah yang
sudah pernah di-refund sebelumnya dari kuota yang tersisa). Bandingkan kolom "Kuantitas dibeli"
dengan "Total sudah di-refund" setelah beberapa kali mengajukan return — angka kedua bisa jauh
melampaui angka pertama, dan bisa terus bertambah lagi di setiap submit berikutnya, karena tidak
ada batas atas berbasis riwayat order yang sebenarnya sama sekali.</p>

<?php include 'footer.php'; ?>
