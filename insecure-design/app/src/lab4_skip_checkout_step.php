<?php
$title = 'Lab 4: Skip Checkout Step';
include 'header.php';

$product = find_product($db, 2); // Sepatu Sneakers
$qty = 1;
$total = $product['price'] * $qty;
$step = (int)($_GET['step'] ?? 1);
if ($step < 1 || $step > 3) $step = 1;

$new_order = null;
if ($step === 3) {
    // VULNERABLE: step 3 (konfirmasi & tandai pesanan PAID/COMPLETED) TIDAK
    // pernah mengecek flag session/db bahwa step 2 (pembayaran) benar-benar
    // sudah dieksekusi. Halaman ini langsung memproses pesanan begitu diakses,
    // apa pun jalur yang dipakai untuk sampai ke sini.
    $order = [
        'id' => next_order_id($db),
        'lab' => 'lab4_skip_checkout_step',
        'product_id' => $product['id'],
        'product_name' => $product['name'],
        'quantity' => $qty,
        'total' => $total,
        'status' => 'PAID',
        'note' => 'Step 2 (pembayaran) TIDAK pernah diverifikasi sebelum step 3 dieksekusi',
        'time' => date('Y-m-d H:i:s'),
    ];
    $db['orders'][] = $order;
    save_db($db);
    $db = load_db();
    $new_order = $order;
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Checkout ini punya 3 step: <code>?step=1</code> (review cart), <code>?step=2</code>
(pembayaran), <code>?step=3</code> (konfirmasi &amp; pesanan selesai). UI cuma "menuntun" kamu
lewat tautan dari step 1 &rarr; 2 &rarr; 3, tapi server tidak pernah mengecek apakah step 2 benar-benar
selesai sebelum menjalankan step 3. Coba langsung buka
<code>?step=3</code> tanpa pernah menyentuh step 2, lalu lihat riwayat pesanan di bawah &mdash;
pesanan tetap tercatat <strong>PAID</strong>.</p>
</details>

<?php if ($step === 1): ?>
  <h3>Step 1 &mdash; Review Cart</h3>
  <table class="data-table">
  <tr><th>Produk</th><th>Qty</th><th>Total</th></tr>
  <tr><td><?php echo htmlspecialchars($product['name']); ?></td><td><?php echo $qty; ?></td><td><?php echo rupiah($total); ?></td></tr>
  </table>
  <p><a class="btn" href="?step=2">Lanjut ke Step 2: Pembayaran</a></p>

<?php elseif ($step === 2): ?>
  <h3>Step 2 &mdash; Pembayaran</h3>
  <p>Total yang harus dibayar: <strong><?php echo rupiah($total); ?></strong></p>
  <p class="hint">(Simulasi form pembayaran &mdash; tidak ada integrasi payment gateway sungguhan di lab ini.)</p>
  <p><a class="btn" href="?step=3">Konfirmasi Pembayaran &amp; Lanjut ke Step 3</a></p>

<?php else: ?>
  <h3>Step 3 &mdash; Konfirmasi</h3>
  <?php if ($new_order): ?>
    <div class="ok-box">Pembayaran berhasil! Pesanan #<?php echo (int)$new_order['id']; ?> untuk
    <?php echo htmlspecialchars($product['name']); ?> (<?php echo rupiah($total); ?>) sudah
    <strong>COMPLETED / PAID</strong>.</div>
  <?php endif; ?>
  <p class="hint">Kalau kamu sampai di sini dengan langsung mengetik <code>?step=3</code> di
  address bar (tanpa pernah membuka <code>?step=2</code>), pesanan di atas tetap tercatat selesai
  &mdash; membuktikan step pembayaran tidak pernah benar-benar dipaksakan di server.</p>
<?php endif; ?>

<h3>Riwayat Pesanan</h3>
<table class="data-table">
<tr><th>ID</th><th>Produk</th><th>Qty</th><th>Total</th><th>Status</th><th>Catatan</th><th>Waktu</th></tr>
<?php foreach (array_reverse($db['orders']) as $o): if (($o['lab'] ?? '') !== 'lab4_skip_checkout_step') continue; ?>
<tr>
  <td><?php echo (int)$o['id']; ?></td>
  <td><?php echo htmlspecialchars($o['product_name']); ?></td>
  <td><?php echo (int)$o['quantity']; ?></td>
  <td><?php echo rupiah($o['total']); ?></td>
  <td><span class="badge admin"><?php echo htmlspecialchars($o['status']); ?></span></td>
  <td><?php echo htmlspecialchars($o['note']); ?></td>
  <td><?php echo htmlspecialchars($o['time']); ?></td>
</tr>
<?php endforeach; ?>
</table>

<?php include 'footer.php'; ?>
