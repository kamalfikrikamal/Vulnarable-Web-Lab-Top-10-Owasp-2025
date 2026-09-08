<?php
$title = 'Lab 2: Negative Quantity';
include 'header.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product = find_product($db, $_POST['product_id'] ?? 1);
    if (!$product) {
        $err = 'Produk tidak ditemukan.';
    } else {
        // Harga TIDAK dipercaya dari klien -- selalu dilihat ulang dari katalog server.
        $price = (float)$product['price'];
        // VULNERABLE: quantity tidak pernah divalidasi harus > 0.
        $qty = (int)($_POST['quantity'] ?? 1);
        $total = $price * $qty;

        $order = [
            'id' => next_order_id($db),
            'lab' => 'lab2_negative_quantity',
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'price_used' => $price,
            'quantity' => $qty,
            'total' => $total,
            'note' => 'Order normal',
            'time' => date('Y-m-d H:i:s'),
        ];

        if ($total < 0) {
            $refund = abs($total);
            $db['wallet_balance'] = ($db['wallet_balance'] ?? 0) + $refund;
            $order['note'] = 'Refund otomatis karena selisih pembayaran negatif: +' . rupiah($refund) . ' ke wallet';
            $msg = "Total belanja negatif (" . rupiah($total) . "). Sistem otomatis menambahkan " . rupiah($refund) . " ke wallet balance kamu sebagai \"refund\".";
        } else {
            $msg = "Pesanan #{$order['id']} tercatat. Total: " . rupiah($total) . ".";
        }

        $db['orders'][] = $order;
        save_db($db);
        $db = load_db();
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Kali ini harga produk dilihat ulang dari katalog server (tidak bisa dimanipulasi
lewat hidden field), tapi field <code>quantity</code> tidak pernah dicek harus bernilai positif.
Coba masukkan jumlah negatif, mis. <code>-5</code>, lalu submit. Server akan menghitung
<code>total = harga * quantity</code> yang hasilnya negatif, dan kode memperlakukan total negatif
sebagai "refund" yang ditambahkan ke <code>wallet_balance</code> kamu.</p>
</details>

<?php if ($err): ?><div class="error-box"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<p><strong>Wallet balance kamu saat ini: <?php echo rupiah($db['wallet_balance'] ?? 0); ?></strong></p>

<h3>Checkout</h3>
<form method="post">
  <label>Produk</label><br>
  <select name="product_id">
    <?php foreach ($db['products'] as $p): ?>
    <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (<?php echo rupiah($p['price']); ?>)</option>
    <?php endforeach; ?>
  </select><br>
  <label>Jumlah</label><br>
  <input type="number" name="quantity" value="1">
  <button type="submit">Checkout</button>
</form>

<h3>Riwayat Pesanan</h3>
<table class="data-table">
<tr><th>ID</th><th>Produk</th><th>Harga</th><th>Qty</th><th>Total</th><th>Catatan</th><th>Waktu</th></tr>
<?php foreach (array_reverse($db['orders']) as $o): if (($o['lab'] ?? '') !== 'lab2_negative_quantity') continue; ?>
<tr>
  <td><?php echo (int)$o['id']; ?></td>
  <td><?php echo htmlspecialchars($o['product_name']); ?></td>
  <td><?php echo rupiah($o['price_used']); ?></td>
  <td><?php echo (int)$o['quantity']; ?></td>
  <td><?php echo rupiah($o['total']); ?></td>
  <td><?php echo htmlspecialchars($o['note']); ?></td>
  <td><?php echo htmlspecialchars($o['time']); ?></td>
</tr>
<?php endforeach; ?>
</table>

<?php include 'footer.php'; ?>
