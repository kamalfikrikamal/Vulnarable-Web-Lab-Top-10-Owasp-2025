<?php
$title = 'Lab 1: Fail-Open Payment Timeout';
include 'header.php';

const ITEM_NAME = 'Kaos Edisi Terbatas "A10"';
const ITEM_PRICE = 250000;

/**
 * Simulasi pemanggilan layanan verifikasi pembayaran eksternal (payment gateway).
 * Kalau $simulate_failure true, fungsi ini melempar Exception - mensimulasikan
 * gateway timeout / koneksi putus / service down, kondisi yang SANGAT umum
 * terjadi di dunia nyata (network blip, gateway maintenance, rate limit, dst).
 */
function verify_payment($simulate_failure) {
    if ($simulate_failure) {
        // Simulasi timeout koneksi ke payment gateway eksternal.
        throw new Exception('Gagal menghubungi payment gateway: connection timed out after 30000ms');
    }
    // Jalur normal (gateway hidup): anggap pembayaran benar-benar diverifikasi berhasil.
    return true;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $simulate_down = isset($_POST['simulate_gateway_down']);
    $verified = false;

    try {
        $verified = verify_payment($simulate_down);
    } catch (\Exception $e) {
        // BUG (fail-open): saat verifikasi gagal/exception, kode ini seharusnya
        // MENAHAN/MENOLAK order karena status pembayaran tidak bisa dipastikan.
        // Alih-alih, ia mengasumsikan pembayaran berhasil supaya user tidak
        // terganggu oleh gangguan gateway.
        // asumsikan berhasil kalau gateway sedang bermasalah, supaya user tidak terganggu
        $verified = true;
        $error = 'Gateway error tertangkap: ' . $e->getMessage() . ' (order tetap diproses)';
    }

    $db = load_db();
    $order = [
        'id' => $db['next_order_id'],
        'item' => ITEM_NAME,
        'price' => ITEM_PRICE,
        'status' => $verified ? 'PAID' : 'PENDING_PAYMENT',
        'gateway_simulated_down' => $simulate_down,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    $db['orders'][] = $order;
    $db['next_order_id']++;
    save_db($db);

    $message = "Order #{$order['id']} dibuat dengan status: {$order['status']}";
}

$db = load_db();
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Centang checkbox "simulasikan gateway down" lalu klik Checkout. Perhatikan status
order yang muncul di tabel riwayat di bawah - apakah order tetap jadi <code>PAID</code> walaupun
kamu SENGAJA membuat verifikasi pembayaran gagal, dan kamu tidak pernah memberikan konfirmasi
pembayaran nyata apa pun?</p>
</details>

<div class="lab-card">
  <h3><?php echo htmlspecialchars(ITEM_NAME); ?></h3>
  <p>Harga: Rp<?php echo number_format(ITEM_PRICE, 0, ',', '.'); ?></p>
  <form method="post">
    <label>
      <input type="checkbox" name="simulate_gateway_down" value="1">
      Simulasikan payment gateway timeout/down (mensimulasikan kondisi jaringan nyata)
    </label><br><br>
    <button type="submit">Checkout</button>
  </form>
</div>

<?php if ($message): ?><div class="ok-box"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<h3>Riwayat Order</h3>
<?php if (empty($db['orders'])): ?>
<p class="hint">Belum ada order. Coba checkout dulu di atas.</p>
<?php else: ?>
<table class="data-table">
<tr><th>ID</th><th>Item</th><th>Harga</th><th>Status</th><th>Gateway disimulasikan down?</th><th>Waktu</th></tr>
<?php foreach (array_reverse($db['orders']) as $o): ?>
<tr>
  <td>#<?php echo htmlspecialchars($o['id']); ?></td>
  <td><?php echo htmlspecialchars($o['item']); ?></td>
  <td>Rp<?php echo number_format($o['price'], 0, ',', '.'); ?></td>
  <td><span class="badge <?php echo $o['status'] === 'PAID' ? 'admin' : 'user'; ?>"><?php echo htmlspecialchars($o['status']); ?></span></td>
  <td><?php echo $o['gateway_simulated_down'] ? 'YA' : 'tidak'; ?></td>
  <td><?php echo htmlspecialchars($o['created_at']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<p class="hint">Kenapa ini bug: saat dependensi eksternal (payment gateway) gagal atau tidak bisa
diakses, default yang AMAN adalah selalu menahan/menolak transaksi dan meminta user mencoba lagi
nanti - bukan diam-diam mengasumsikan pembayaran berhasil hanya karena verifikasinya tidak bisa
dilakukan. "Fail-open" seperti ini membuat siapa pun bisa mendapatkan barang tanpa membayar,
cukup dengan membuat request ke endpoint yang memicu gateway error (mis. dengan traffic shaping,
memutus koneksi di tengah jalan, atau exploit lain yang membuat service pembayaran timeout).</p>

<?php include 'footer.php'; ?>
