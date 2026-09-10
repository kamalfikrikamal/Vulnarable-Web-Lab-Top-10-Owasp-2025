<?php
$title = 'Lab 8: Malformed Response Fail Open';
include 'header.php';

/**
 * Simulasi pemanggilan layanan fraud-scoring eksternal. Bukan timeout/exception (seperti Lab 1)
 * - di sini API-nya TETAP menjawab dengan JSON yang valid, tapi kalau $simulate_malformed true,
 * bentuk (shape) response-nya BEDA dari yang diharapkan kode pemanggil (mensimulasikan skenario
 * nyata: upstream API mengembalikan error envelope yang berbeda struktur dari response sukses
 * normal, misalnya saat API itu sendiri sedang bermasalah/rate-limited/versi API berubah).
 */
function check_fraud_score($order_details, $simulate_malformed) {
    if ($simulate_malformed) {
        // Response berbentuk tak terduga: tidak ada key 'fraud_score' atau 'flagged' sama
        // sekali - fraud check ini SEBENARNYA TIDAK PERNAH benar-benar jalan.
        return ['status' => 'error', 'message' => 'upstream fraud-check service unavailable'];
    }
    // Jalur normal: order dengan detail seperti ini (nominal besar, akun baru) memang pantas
    // dicurigai - fraud-check API yang berfungsi normal akan memberi skor tinggi & flag.
    return ['fraud_score' => 85, 'flagged' => true];
}

$message = '';
$error = '';

$amount = 75000000;
$shipping = 'Alamat baru, belum pernah dipakai sebelumnya';
$account_age_days = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
    $simulate_malformed = isset($_POST['simulate_malformed_response']);
    $order_details = ['amount' => $amount, 'shipping' => $shipping, 'account_age_days' => $account_age_days];

    $fraud_result = check_fraud_score($order_details, $simulate_malformed);

    // BUG (fail-open lewat default diam-diam): kalau key 'flagged' tidak ada di response
    // (karena response-nya malformed/berbeda bentuk), operator ?? diam-diam menganggap
    // "tidak di-flag" = aman untuk diproses. Padahal kenyataannya adalah "fraud check ini
    // tidak pernah benar-benar menjawab pertanyaannya" - BUKAN "sudah dicek dan aman".
    $flagged = $fraud_result['flagged'] ?? false;

    $db = load_db();
    if ($flagged) {
        $db['fraud_orders'][] = [
            'time' => date('Y-m-d H:i:s'),
            'amount' => $amount,
            'status' => 'DIBLOKIR',
            'fraud_score' => $fraud_result['fraud_score'] ?? null,
            'raw_response' => $fraud_result,
        ];
        save_db($db);
        $error = 'Checkout diblokir - order ditandai sebagai fraud (fraud_score: ' . $fraud_result['fraud_score'] . ').';
    } else {
        $db['fraud_orders'][] = [
            'time' => date('Y-m-d H:i:s'),
            'amount' => $amount,
            'status' => 'DIPROSES',
            'fraud_score' => $fraud_result['fraud_score'] ?? null,
            'raw_response' => $fraud_result,
        ];
        save_db($db);
        $message = 'Pesanan diproses, TIDAK terdeteksi sebagai fraud.';
        if ($simulate_malformed) {
            $message .= ' (PADAHAL fraud-check API tidak pernah benar-benar menjawab - response-nya berbentuk error yang tidak dikenali kode ini!)';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    reset_db();
    $message = 'State lab direset ke kondisi awal.';
}

$db = load_db();
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Detail order di bawah SENGAJA dibuat terlihat mencurigakan (nominal besar, akun
baru berumur 0 hari) - kalau fraud-check API berfungsi normal, order ini SEHARUSNYA diblokir. Coba
klik "Checkout (fraud-check normal)" dulu - lihat hasilnya (harus DIBLOKIR). Sekarang klik
"Checkout (simulasikan response API rusak/tidak terduga)" - fraud-check API-nya TIDAK timeout,
TETAP menjawab dengan JSON valid, cuma bentuknya beda dari yang diharapkan kode ini. Perhatikan:
apakah order yang SAMA-SAMA mencurigakan ini tetap diblokir, atau malah lolos begitu saja?</p>
</details>

<div class="lab-card">
  <h3>Checkout</h3>
  <table class="data-table">
  <tr><th>Nominal Order</th><td>Rp<?php echo number_format($amount, 0, ',', '.'); ?></td></tr>
  <tr><th>Alamat Pengiriman</th><td><?php echo htmlspecialchars($shipping); ?></td></tr>
  <tr><th>Umur Akun</th><td><?php echo $account_age_days; ?> hari</td></tr>
  </table>
  <form method="post" style="margin-top:10px; display:inline-block; margin-right:10px;">
    <input type="hidden" name="action" value="checkout">
    <button type="submit">Checkout (fraud-check normal)</button>
  </form>
  <form method="post" style="display:inline-block; margin-right:10px;">
    <input type="hidden" name="action" value="checkout">
    <input type="hidden" name="simulate_malformed_response" value="1">
    <button type="submit">Checkout (simulasikan response fraud-check API yang rusak/tidak terduga)</button>
  </form>
  <form method="post" style="display:inline-block;">
    <input type="hidden" name="action" value="reset">
    <button type="submit" style="background:#7f1d1d;">Reset State Lab</button>
  </form>
</div>

<?php if ($message): ?><div class="ok-box"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<h3>Riwayat Order (dengan hasil fraud-check)</h3>
<?php if (empty($db['fraud_orders'])): ?>
<p class="hint">Belum ada order. Coba checkout dulu di atas.</p>
<?php else: ?>
<table class="data-table">
<tr><th>Waktu</th><th>Nominal</th><th>Status</th><th>fraud_score</th><th>Raw response dari check_fraud_score()</th></tr>
<?php foreach (array_reverse($db['fraud_orders']) as $o): ?>
<tr>
  <td><?php echo htmlspecialchars($o['time']); ?></td>
  <td>Rp<?php echo number_format($o['amount'], 0, ',', '.'); ?></td>
  <td><span class="badge <?php echo $o['status'] === 'DIBLOKIR' ? 'admin' : 'user'; ?>"><?php echo htmlspecialchars($o['status']); ?></span></td>
  <td><?php echo $o['fraud_score'] === null ? '(tidak ada)' : htmlspecialchars((string)$o['fraud_score']); ?></td>
  <td><?php echo htmlspecialchars(json_encode($o['raw_response'])); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<p class="hint">Kenapa ini bug: <code>$fraud_result['flagged'] ?? false</code> terlihat seperti
kode defensif yang aman - "kalau key-nya tidak ada, ya sudah, anggap saja tidak di-flag". Tapi
untuk keputusan yang KEAMANAN-KRITIS seperti ini, key yang hilang bukan berarti "sudah dicek dan
memang tidak di-flag" - artinya adalah <strong>"pengecekan ini tidak pernah benar-benar
menghasilkan jawaban yang valid"</strong>, dua kondisi yang MAKNANYA SANGAT BERBEDA tapi
diperlakukan identik oleh <code>??</code>. Bandingkan dengan Lab 1: root cause-nya beda (di sana
hard timeout/exception; di sini response valid tapi salah bentuk/shape), tapi pola bug-nya sama -
kondisi "tidak bisa dipastikan" ditangani seolah-olah "dipastikan aman". Operator default seperti
<code>??</code>, <code>?:</code>, atau fallback nilai lain (<code>false</code>, <code>0</code>,
<code>[]</code>) pada field yang menentukan keputusan keamanan harus dipakai dengan sangat
hati-hati - idealnya, response yang tidak sesuai shape yang diharapkan harus divalidasi secara
eksplisit (mis. <code>isset($fraud_result['fraud_score'])</code>) dan, kalau tidak sesuai,
DITOLAK/ditahan (fail closed) - bukan diam-diam dianggap aman.</p>

<?php include 'footer.php'; ?>
