<?php
$title = 'Lab 10: Price Spoofing Lewat Header Region';
include 'header.php';

$product = $db['digital_product'];

// VULNERABLE: harga ditentukan dari header X-Region yang DIKIRIM CLIENT
// sendiri - bukan dari sumber tepercaya seperti IP geolocation di server,
// atau alamat billing yang sudah diverifikasi di akun user.
$region = $_SERVER['HTTP_X_REGION'] ?? ($_GET['region'] ?? 'ID');
$region = strtoupper(trim($region));
$price = $product['price_by_region'][$region] ?? $product['price_by_region']['ID'];

$order = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order = [
        'region_used' => $region,
        'price_charged' => $price,
        'time' => date('H:i:s'),
    ];
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: harga produk digital ini seharusnya <?php echo rupiah($product['price_by_region']['ID']); ?>
untuk region Indonesia (ID), tapi jauh lebih murah untuk region US
(<?php echo rupiah($product['price_by_region']['US']); ?> — asumsikan ini nilai dalam mata uang
lokal yang jauh lebih rendah, murni untuk demo). Harga ditentukan dari header
<code>X-Region</code> yang kamu kirim sendiri. Coba:</p>
<pre class="result-box">curl -X POST -H "X-Region: US" http://localhost:8079/insecuredesign/lab10_region_price_spoofing.php</pre>
<p class="hint">Atau lewat browser, tambahkan <code>?region=US</code> di URL (disediakan sebagai
fallback kalau kamu tidak punya alat untuk mengubah header request).</p>
</details>

<h3>Checkout — <?php echo htmlspecialchars($product['name']); ?></h3>
<p>Region terdeteksi: <strong><?php echo htmlspecialchars($region); ?></strong>
(dari header <code>X-Region</code>, fallback <code>?region=</code>)</p>
<p>Harga untuk region ini: <strong><?php echo rupiah($price); ?></strong></p>

<form method="post">
  <button type="submit">Checkout dengan harga di atas</button>
</form>

<?php if ($order): ?>
<div class="<?php echo $order['region_used'] !== 'ID' ? 'error-box' : 'ok-box'; ?>">
  Checkout berhasil. Region dipakai: <strong><?php echo htmlspecialchars($order['region_used']); ?></strong>,
  harga dibayar: <strong><?php echo rupiah($order['price_charged']); ?></strong>.
  <?php if ($order['region_used'] !== 'ID'): ?><br><strong>⚠ Region di-spoof lewat header/parameter klien, bukan dideteksi server dari sumber tepercaya.</strong><?php endif; ?>
</div>
<?php endif; ?>

<p class="hint">Kenapa berhasil: aplikasi mengasumsikan region pengguna bisa dipercaya begitu saja
dari apa yang dikirim client (header custom, parameter URL) — padahal keduanya sepenuhnya
dikendalikan pengirim request dan bisa diisi apa saja lewat DevTools/Burp/curl. Penentuan region
untuk keperluan bisnis (harga, pajak, ketersediaan produk yang di-lock per negara) seharusnya
memakai sumber yang tidak bisa dimanipulasi klien secara langsung — mis. lookup IP address di
server (dengan kesadaran bahwa VPN/proxy tetap punya keterbatasan), atau alamat billing yang
sudah diverifikasi & terikat ke metode pembayaran resmi di akun user, bukan sekadar header yang
"kebetulan" dikirim client.</p>

<?php include 'footer.php'; ?>
