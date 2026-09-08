<?php
$title = 'Lab 3: Coupon Stacking';
include 'header.php';

$product = find_product($db, 1); // Kaos Polos Import
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['coupon_code'] ?? '');
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $price = (float)$product['price'];
    $subtotal = $price * $qty;

    $coupon = null;
    foreach ($db['coupons'] as &$c) {
        if (strcasecmp($c['code'], $code) === 0) { $coupon = &$c; break; }
    }

    if (!$coupon) {
        $err = "Kode kupon \"" . htmlspecialchars($code) . "\" tidak ditemukan.";
    } else {
        $discount = round($subtotal * ($coupon['percent'] / 100));
        $total = $subtotal - $discount;

        // VULNERABLE: kupon diterapkan lagi & lagi -- tidak pernah ditandai
        // "sudah dipakai" (tidak ada used_by / used_count / one-time flag yang
        // memblokir pemakaian berikutnya). Tiap submit dianggap request baru
        // yang sah, walau ini kode & order yang persis sama.
        $coupon['total_saved'] += $discount;
        $coupon['times_used'] += 1;

        $order = [
            'id' => next_order_id($db),
            'lab' => 'lab3_coupon_stacking',
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'quantity' => $qty,
            'subtotal' => $subtotal,
            'coupon_code' => $coupon['code'],
            'discount' => $discount,
            'total' => $total,
            'note' => "Kupon dipakai ke-{$coupon['times_used']} kali (seharusnya sekali pakai)",
            'time' => date('Y-m-d H:i:s'),
        ];
        $db['orders'][] = $order;
        save_db($db);
        $db = load_db();
        foreach ($db['coupons'] as $c2) { if (strcasecmp($c2['code'], $code) === 0) { $coupon = $c2; break; } }

        $msg = "Kupon {$coupon['code']} diterapkan. Diskon kali ini: " . rupiah($discount) . ". Total dibayar: " . rupiah($total) . ".";
    }
}

$coupon_display = $db['coupons'][0];
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Kupon <code>DISKON20</code> (diskon 20%) terlihat seperti kode sekali pakai, tapi
server tidak pernah menandainya "sudah dipakai" setelah diterapkan &mdash; tidak ada pengecekan
<code>used_by</code>/<code>used_count</code> yang memblokir pemakaian berikutnya. Submit form ini
berkali-kali dengan kode &amp; kuantitas yang sama persis, lalu perhatikan
<strong>"total penghematan dari kupon ini"</strong> di bawah terus bertambah setiap kali.</p>
</details>

<?php if ($err): ?><div class="error-box"><?php echo $err; ?></div><?php endif; ?>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<p><strong>Total penghematan dari kupon DISKON20 sejauh ini: <?php echo rupiah($coupon_display['total_saved']); ?></strong>
(sudah dipakai <?php echo (int)$coupon_display['times_used']; ?> kali)</p>

<h3>Checkout &mdash; <?php echo htmlspecialchars($product['name']); ?></h3>
<p>Harga: <?php echo rupiah($product['price']); ?></p>
<form method="post">
  <label>Jumlah</label><br>
  <input type="number" name="quantity" value="1" min="1"><br>
  <label>Kode Kupon</label><br>
  <input type="text" name="coupon_code" value="DISKON20">
  <button type="submit">Terapkan Kupon &amp; Checkout</button>
</form>

<h3>Riwayat Pesanan</h3>
<table class="data-table">
<tr><th>ID</th><th>Produk</th><th>Qty</th><th>Subtotal</th><th>Kupon</th><th>Diskon</th><th>Total</th><th>Catatan</th></tr>
<?php foreach (array_reverse($db['orders']) as $o): if (($o['lab'] ?? '') !== 'lab3_coupon_stacking') continue; ?>
<tr>
  <td><?php echo (int)$o['id']; ?></td>
  <td><?php echo htmlspecialchars($o['product_name']); ?></td>
  <td><?php echo (int)$o['quantity']; ?></td>
  <td><?php echo rupiah($o['subtotal']); ?></td>
  <td><?php echo htmlspecialchars($o['coupon_code']); ?></td>
  <td><?php echo rupiah($o['discount']); ?></td>
  <td><?php echo rupiah($o['total']); ?></td>
  <td><?php echo htmlspecialchars($o['note']); ?></td>
</tr>
<?php endforeach; ?>
</table>

<?php include 'footer.php'; ?>
