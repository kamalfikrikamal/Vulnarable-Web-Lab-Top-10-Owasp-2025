<?php
$title = 'Lab 5: Duplicate Charge Retry';
include 'header.php';

const DEMO_ORDER_ID = 7001;
const DEMO_AMOUNT = 450000;

/**
 * Simulasi pemanggilan payment processor eksternal. PENTING: fungsi ini SELALU benar-benar
 * mencatat charge di backend (mensimulasikan "bank sudah memproses uangnya") - persis seperti
 * dunia nyata, request yang dikirim ke payment processor SERING kali sebenarnya sudah diterima
 * & diproses walaupun caller tidak pernah menerima konfirmasinya (koneksi putus, load balancer
 * timeout, response hilang di tengah jalan, dst). Flag $simulate_timeout HANYA mempengaruhi apa
 * yang diterima CALLER - bukan apa yang benar-benar terjadi di backend.
 */
function process_payment($order_id, $amount, $simulate_timeout) {
    $db = load_db();
    $db['charges'][] = [
        'order_id' => $order_id,
        'amount' => $amount,
        'time' => date('Y-m-d H:i:s') . '.' . substr(microtime(), 2, 4),
    ];
    save_db($db);

    if ($simulate_timeout) {
        // Backend SUDAH memproses charge di atas - tapi caller tidak pernah menerima
        // konfirmasi ini. Dari sudut pandang caller, hasilnya AMBIGU: bukan "pasti gagal",
        // tapi "tidak tahu" (unknown). Exception ini mensimulasikan koneksi ke payment
        // processor yang putus/timeout SETELAH backend selesai memproses.
        throw new Exception('Tidak ada response dari payment processor (connection timed out setelah 15000ms)');
    }
    return true;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $simulate_timeout = isset($_POST['simulate_timeout']);
    try {
        process_payment(DEMO_ORDER_ID, DEMO_AMOUNT, $simulate_timeout);
        $message = 'Pembayaran berhasil dikonfirmasi oleh payment processor.';
    } catch (\Exception $e) {
        // BUG (non-idempotent retry): caller menganggap "tidak ada response" = "belum tentu
        // dicoba lagi jika trainee klik 'Coba Lagi'" - TIDAK ADA pengecekan "apakah order ini
        // sudah pernah di-charge sebelumnya?" dan TIDAK ADA idempotency key yang dikirim ke
        // process_payment(), sehingga setiap klik "Coba Lagi" memanggil ULANG proses charge
        // yang PERSIS SAMA dari nol.
        $error = 'Response timeout: ' . $e->getMessage() . ' - status pembayaran TIDAK DIKETAHUI dari sisi kamu. Klik "Coba Lagi" di bawah untuk mencoba ulang.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    reset_db();
    $message = 'State lab direset ke kondisi awal.';
}

$db = load_db();
$order_charges = array_values(array_filter($db['charges'], function ($c) {
    return (string)$c['order_id'] === (string)DEMO_ORDER_ID;
}));
$total_charged = array_sum(array_column($order_charges, 'amount'));
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Klik "Bayar (jalur normal)" sekali dulu - satu baris charge muncul di tabel
bawah, wajar. Sekarang reset state lab, lalu klik "Bayar (simulasikan response timeout)" - pesan
error muncul seolah pembayaran gagal/tidak jelas hasilnya. Klik tombol "Coba Lagi" yang muncul
(mengirim ulang order &amp; jumlah yang PERSIS SAMA). Lihat tabel "Charge Tercatat untuk Order
Ini" - berapa baris charge yang tercatat untuk order yang sama setelah kamu retry beberapa kali?
Apakah backend benar-benar tahu pembayaran "timeout" itu sudah sukses diproses sebelumnya?</p>
</details>

<div class="lab-card">
  <h3>Checkout Order #<?php echo DEMO_ORDER_ID; ?></h3>
  <p>Total tagihan: Rp<?php echo number_format(DEMO_AMOUNT, 0, ',', '.'); ?></p>
  <form method="post" style="display:inline-block; margin-right:10px;">
    <input type="hidden" name="action" value="pay">
    <button type="submit">Bayar (jalur normal)</button>
  </form>
  <form method="post" style="display:inline-block; margin-right:10px;">
    <input type="hidden" name="action" value="pay">
    <input type="hidden" name="simulate_timeout" value="1">
    <button type="submit">Bayar (simulasikan response timeout, tapi charge tetap diproses di backend)</button>
  </form>
  <form method="post" style="display:inline-block;">
    <input type="hidden" name="action" value="reset">
    <button type="submit" style="background:#7f1d1d;">Reset State Lab</button>
  </form>
</div>

<?php if ($message): ?><div class="ok-box"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?>
<div class="error-box"><?php echo htmlspecialchars($error); ?></div>
<form method="post" style="margin-top:8px;">
  <input type="hidden" name="action" value="pay">
  <button type="submit">Coba Lagi (retry, order/amount SAMA, tanpa idempotency key)</button>
</form>
<?php endif; ?>

<h3>Charge Tercatat untuk Order Ini</h3>
<?php if (empty($order_charges)): ?>
<p class="hint">Belum ada charge untuk order #<?php echo DEMO_ORDER_ID; ?>. Coba bayar dulu di atas.</p>
<?php else: ?>
<table class="data-table">
<tr><th>#</th><th>Order ID</th><th>Jumlah</th><th>Waktu Diproses Backend</th></tr>
<?php foreach ($order_charges as $i => $c): ?>
<tr>
  <td><?php echo $i + 1; ?></td>
  <td>#<?php echo htmlspecialchars($c['order_id']); ?></td>
  <td>Rp<?php echo number_format($c['amount'], 0, ',', '.'); ?></td>
  <td><?php echo htmlspecialchars($c['time']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<p class="<?php echo count($order_charges) > 1 ? 'error-box' : 'hint'; ?>" style="margin-top:8px;">
Total charge tercatat untuk order #<?php echo DEMO_ORDER_ID; ?>: Rp<?php echo number_format($total_charged, 0, ',', '.'); ?>
(<?php echo count($order_charges); ?> baris charge)<?php if (count($order_charges) > 1): ?> &mdash;
<strong>DOUBLE CHARGE terdeteksi!</strong> Order ini seharusnya cuma ditagih satu kali
Rp<?php echo number_format(DEMO_AMOUNT, 0, ',', '.'); ?>.<?php endif; ?>
</p>
<?php endif; ?>

<p class="hint">Kenapa ini bug: "tidak ada response" (timeout/koneksi putus) BUKAN berarti "pasti
gagal" - itu artinya <strong>tidak diketahui</strong> (unknown), karena request bisa saja sudah
sampai dan diproses penuh di sisi server, hanya response-nya yang hilang di jalan pulang. Retry
begitu saja terhadap operasi yang TIDAK idempotent (mengulang persis proses yang sama, bukan
sekadar mengecek ulang hasilnya) memperlakukan kondisi ambigu ini seolah-olah "belum pernah
dicoba", padahal seharusnya diverifikasi dulu. Sistem pembayaran nyata mengatasi ini dengan
<strong>idempotency key</strong>: setiap upaya charge (termasuk retry) mengirim key unik yang
sama persis untuk operasi logis yang sama; server mengenali key yang berulang dan mengembalikan
HASIL YANG SUDAH ADA sebelumnya, alih-alih memproses charge baru dari nol.</p>

<?php include 'footer.php'; ?>
