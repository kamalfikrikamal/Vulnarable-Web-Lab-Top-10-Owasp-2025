<?php
$title = 'Lab 1: Price Tampering';
include 'header.php';

$product = find_product($db, 1); // Kaos Polos Import
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABLE: harga diambil langsung dari input klien (hidden field),
    // bukan dilihat ulang dari katalog produk di server.
    $price_used = (float)($_POST['price'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $total = $price_used * $qty;

    $order = [
        'id' => next_order_id($db),
        'lab' => 'lab1_price_tampering',
        'product_id' => $product['id'],
        'product_name' => $product['name'],
        'price_used' => $price_used,
        'quantity' => $qty,
        'total' => $total,
        'note' => ($price_used != $product['price']) ? 'Harga TIDAK sesuai katalog (harga asli: ' . rupiah($product['price']) . ')' : 'Harga sesuai katalog',
        'time' => date('Y-m-d H:i:s'),
    ];
    $db['orders'][] = $order;
    save_db($db);
    $db = load_db();

    $msg = "Pesanan #{$order['id']} tercatat. Total dibayar: " . rupiah($total) . " untuk {$qty}x {$product['name']}.";
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Form checkout di bawah menampilkan harga produk sebagai
<code>&lt;input type="hidden" name="price" value="<?php echo (int)$product['price']; ?>"&gt;</code>.
Server menghitung <code>total = $_POST['price'] * $_POST['quantity']</code> apa adanya, tanpa
mengecek ulang harga sebenarnya dari katalog. Buka DevTools/Burp, ubah nilai field
<code>price</code> (mis. jadi <code>100</code>) sebelum request dikirim, lalu submit form.</p>
</details>

<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<h3>Checkout &mdash; <?php echo htmlspecialchars($product['name']); ?></h3>
<p>Harga: <strong><?php echo rupiah($product['price']); ?></strong></p>

<form method="post">
  <input type="hidden" name="price" value="<?php echo (int)$product['price']; ?>">
  <label>Jumlah</label><br>
  <input type="number" name="quantity" value="1" min="1">
  <button type="submit">Checkout</button>
</form>

<h3>Riwayat Pesanan (data/db.json &rarr; orders)</h3>
<table class="data-table">
<tr><th>ID</th><th>Produk</th><th>Harga Dipakai</th><th>Qty</th><th>Total</th><th>Catatan</th><th>Waktu</th></tr>
<?php foreach (array_reverse($db['orders']) as $o): if (($o['lab'] ?? '') !== 'lab1_price_tampering') continue; ?>
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
