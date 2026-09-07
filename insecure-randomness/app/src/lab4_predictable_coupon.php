<?php
$title = 'Lab 4: Kode Kupon yang Bisa Ditebak';
require_once __DIR__ . '/lib.php';
$db = load_db();

function make_coupon($order_id) {
    // VULNERABLE: "unique" coupon code is just the order ID formatted with
    // a prefix - fully sequential and guessable, not a random code at all.
    return 'SAVE' . str_pad($order_id, 4, '0', STR_PAD_LEFT);
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $order_id = count($db['coupons']) + 1001;
    $coupon = make_coupon($order_id);
    $db['coupons'][] = ['order_id' => $order_id, 'code' => $coupon, 'discount' => '20%', 'redeemed' => false];
    save_db($db);
    $msg = "Checkout berhasil! Order #$order_id, kupon 20% untuk pembelian berikutnya: <strong>" . htmlspecialchars($coupon) . "</strong> (dikirim ke email kamu).";
}

$redeem_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem'])) {
    $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $found = null;
    foreach ($db['coupons'] as &$c) {
        if ($c['code'] === $code) { $found = &$c; break; }
    }
    unset($c);
    $safe_code = htmlspecialchars($code);
    if ($found && !$found['redeemed']) {
        $found['redeemed'] = true;
        save_db($db);
        $redeem_msg = "Kupon $safe_code berhasil dipakai! Diskon " . htmlspecialchars($found['discount']) . " diterapkan, walau kupon ini bukan milikmu.";
    } elseif ($found && $found['redeemed']) {
        $redeem_msg = "Kupon $safe_code sudah pernah dipakai.";
    } else {
        $redeem_msg = "Kupon tidak ditemukan.";
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: kupon dibuat dari <code>'SAVE' . str_pad($order_id, 4, '0', STR_PAD_LEFT)</code>
&mdash; bukan kode acak, cuma nomor order yang diformat ulang. Lakukan satu kali "checkout" untuk
melihat pola kuponmu sendiri (mis. <code>SAVE1001</code>), lalu tebak kode order lain di
sekitarnya (<code>SAVE1000</code>, <code>SAVE1002</code>, dst.) dan coba redeem.</p>
</details>

<h3>1. Checkout (dapatkan kupon kamu sendiri)</h3>
<form method="post">
  <button type="submit" name="checkout" value="1">Checkout &amp; Dapatkan Kupon</button>
</form>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<h3>2. Redeem kupon (coba tebak kupon order lain)</h3>
<form method="post">
  <label>Kode kupon</label><br>
  <input type="text" name="coupon_code" placeholder="SAVE1001">
  <button type="submit" name="redeem" value="1">Redeem</button>
</form>
<?php if ($redeem_msg): ?><div class="<?php echo strpos($redeem_msg,'berhasil')!==false ? 'result-box' : 'error-box'; ?>"><?php echo $redeem_msg; ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
