<?php
$title = 'Lab 9: HTTP Parameter Pollution pada Kupon';
include 'header.php';

$product = find_product($db, 2); // Sepatu Sneakers
$coupon = $db['coupon_hpp']; // HEMAT10, 10%
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fitur ini SAH secara desain: checkout boleh menerima lebih dari satu kode
    // kupon sekaligus (mis. gift card + kode promo) - makanya field-nya array
    // "coupon[]", bukan string tunggal.
    $codes = $_POST['coupon'] ?? [];
    if (!is_array($codes)) $codes = [$codes];

    $total = $product['price'];
    $applied = [];
    foreach ($codes as $code) {
        $code = trim((string)$code);
        if ($code === '') continue;
        // VULNERABLE: setiap kemunculan kode yang cocok langsung diterapkan lagi,
        // tanpa mengecek apakah kode itu SUDAH diterapkan sebelumnya di request
        // yang sama. Fitur "boleh lebih dari satu kupon" tidak pernah didesain
        // untuk kasus "kode YANG SAMA muncul lebih dari sekali".
        if (strcasecmp($code, $coupon['code']) === 0) {
            $discount = $total * ($coupon['percent'] / 100);
            $total -= $discount;
            $applied[] = $code;
        }
    }

    $result = [
        'applied' => $applied,
        'total' => $total,
        'discount_count' => count($applied),
    ];
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: checkout ini sengaja mendukung lebih dari satu kode kupon dalam satu
request (dua kotak input, keduanya bernama <code>coupon[]</code> — fitur sah untuk mis. gift card
+ kode promo sekaligus). Coba isi kotak PERTAMA dan kotak KEDUA dengan kode <strong>yang
sama persis</strong> (<code>HEMAT10</code>), lalu submit satu kali saja.</p>
</details>

<h3>Checkout — <?php echo htmlspecialchars($product['name']); ?></h3>
<p>Harga: <strong><?php echo rupiah($product['price']); ?></strong> — kode kupon yang berlaku:
<code><?php echo htmlspecialchars($coupon['code']); ?></code> (<?php echo $coupon['percent']; ?>% off, seharusnya cuma boleh dipakai sekali per order)</p>

<form method="post">
  <label>Kode kupon 1</label><br>
  <input type="text" name="coupon[]" placeholder="mis. HEMAT10"><br>
  <label>Kode kupon 2 (opsional — gift card/promo lain)</label><br>
  <input type="text" name="coupon[]" placeholder="opsional"><br>
  <button type="submit">Terapkan &amp; Checkout</button>
</form>

<?php if ($result): ?>
<div class="<?php echo $result['discount_count'] > 1 ? 'error-box' : 'ok-box'; ?>">
  Kupon diterapkan: <?php echo $result['discount_count']; ?>x (<?php echo htmlspecialchars(implode(', ', $result['applied']) ?: '-'); ?>)<br>
  Total akhir: <strong><?php echo rupiah($result['total']); ?></strong> (dari <?php echo rupiah($product['price']); ?>)
  <?php if ($result['discount_count'] > 1): ?><br><strong>⚠ Kode yang sama diterapkan lebih dari satu kali dalam satu request!</strong><?php endif; ?>
</div>
<?php endif; ?>

<p class="hint">Kenapa berhasil: server mengizinkan field <code>coupon[]</code> berupa array
(desain yang sah untuk mendukung banyak kode BERBEDA sekaligus), tapi loop yang menerapkan
diskon tidak pernah mengecek apakah suatu kode SUDAH diterapkan sebelumnya di iterasi yang sama.
Mengirim kode identik di lebih dari satu slot array membuat diskonnya diterapkan berkali-kali
dalam satu request tunggal — beda dari sekadar "replay request berkali-kali" (lihat Lab 3), bug
ini murni soal tidak adanya deduplikasi terhadap parameter yang secara sah bisa berupa banyak
nilai.</p>

<?php include 'footer.php'; ?>
