<?php
$title = 'Lab 6: No Rollback Partial Failure';
include 'header.php';

const ACCOUNT_B_ID = 1002;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'transfer') {
    $db = load_db();
    $amount = (int)($_POST['amount'] ?? 0);
    $dest_raw = $_POST['dest_account'] ?? '';

    if ($amount <= 0) {
        $error = 'Jumlah transfer harus lebih dari 0.';
    } else {
        // Step 1: potong saldo akun A. Langkah ini TIDAK bersyarat pada apa pun di step 2,
        // dan langsung di-commit ke penyimpanan (save_db) di sini juga - bukan ditahan sampai
        // seluruh proses transfer selesai.
        $db['account_a_balance'] -= $amount;
        save_db($db);

        try {
            // Step 2: validasi & kredit akun tujuan. Kalau destinasi tidak valid (kosong,
            // bukan angka, atau ID akun yang tidak dikenal), langkah ini melempar Exception.
            if ($dest_raw === '' || !is_numeric($dest_raw) || (int)$dest_raw !== ACCOUNT_B_ID) {
                throw new Exception("Akun tujuan '{$dest_raw}' tidak valid/tidak ditemukan.");
            }
            $db = load_db(); // re-read supaya tidak menimpa balik hasil step 1 di atas
            $db['account_b_balance'] += $amount;
            save_db($db);

            $db['transfer_log'][] = [
                'time' => date('Y-m-d H:i:s'),
                'amount' => $amount,
                'dest_account' => $dest_raw,
                'status' => 'SUKSES (kedua langkah selesai)',
            ];
            save_db($db);
            $message = "Transfer Rp" . number_format($amount, 0, ',', '.') . " ke akun {$dest_raw} berhasil.";
        } catch (\Exception $e) {
            // BUG (no rollback): step 2 gagal, TAPI step 1 (potongan saldo akun A) sudah
            // terlanjur di-commit ke penyimpanan di atas dan TIDAK PERNAH dikembalikan di sini.
            // Seharusnya ada compensating action (mengembalikan saldo akun A) atau, lebih baik
            // lagi, kedua langkah ini dibungkus satu transaksi atomik yang otomatis rollback.
            $db = load_db();
            $db['transfer_log'][] = [
                'time' => date('Y-m-d H:i:s'),
                'amount' => $amount,
                'dest_account' => $dest_raw,
                'status' => 'GAGAL di step 2: ' . $e->getMessage() . ' (saldo akun A TIDAK di-rollback!)',
            ];
            save_db($db);
            $error = 'Step 2 (kredit akun tujuan) gagal: ' . $e->getMessage() .
                ' Saldo akun A SUDAH TERLANJUR dipotong di step 1 dan TIDAK dikembalikan - uang hilang dari sistem.';
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
<p class="hint">Akun tujuan yang VALID adalah ID <code><?php echo ACCOUNT_B_ID; ?></code> (Akun
B). Coba transfer normal dulu (jumlah bebas, akun tujuan <code><?php echo ACCOUNT_B_ID; ?></code>)
- perhatikan saldo A turun DAN saldo B naik, wajar. Sekarang reset state lab, lalu coba transfer
lagi tapi isi field "Akun Tujuan" dengan sesuatu yang tidak valid, misalnya kosongkan saja atau isi
<code>abc</code> atau ID akun yang tidak ada seperti <code>9999</code>. Perhatikan saldo akun A -
apakah tetap berkurang walaupun step kedua (kredit ke akun tujuan) gagal dengan error?</p>
</details>

<div class="lab-card">
  <h3>Saldo Akun</h3>
  <table class="data-table">
  <tr><th>Akun</th><th>Saldo</th></tr>
  <tr><td>A (akun kamu, ID 1001)</td><td>Rp<?php echo number_format($db['account_a_balance'], 0, ',', '.'); ?></td></tr>
  <tr><td>B (akun tujuan, ID <?php echo ACCOUNT_B_ID; ?>)</td><td>Rp<?php echo number_format($db['account_b_balance'], 0, ',', '.'); ?></td></tr>
  </table>
</div>

<div class="lab-card">
  <h3>Transfer Dana (dari Akun A)</h3>
  <form method="post">
    <input type="hidden" name="action" value="transfer">
    <label>Jumlah (Rp)</label><br>
    <input type="number" name="amount" value="100000"><br>
    <label>Akun Tujuan (akun B yang valid = <?php echo ACCOUNT_B_ID; ?>)</label><br>
    <input type="text" name="dest_account" placeholder="mis. 1002, atau isi nilai tidak valid untuk memicu bug"><br>
    <button type="submit">Transfer</button>
  </form>
  <form method="post" style="margin-top:8px;">
    <input type="hidden" name="action" value="reset">
    <button type="submit" style="background:#7f1d1d;">Reset State Lab</button>
  </form>
</div>

<?php if ($message): ?><div class="ok-box"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<h3>Log Transfer</h3>
<?php if (empty($db['transfer_log'])): ?>
<p class="hint">Belum ada transfer. Coba transfer dulu di atas.</p>
<?php else: ?>
<table class="data-table">
<tr><th>Waktu</th><th>Jumlah</th><th>Akun Tujuan (input)</th><th>Status</th></tr>
<?php foreach (array_reverse($db['transfer_log']) as $log): ?>
<tr>
  <td><?php echo htmlspecialchars($log['time']); ?></td>
  <td>Rp<?php echo number_format($log['amount'], 0, ',', '.'); ?></td>
  <td><?php echo htmlspecialchars((string)$log['dest_account']); ?></td>
  <td><?php echo htmlspecialchars($log['status']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<p class="hint">Kenapa ini bug, dan bedanya dengan Lab 3: Lab 3 adalah race condition - butuh DUA
request yang datang nyaris bersamaan untuk dieksploitasi. Bug di lab ini murni SEKUENSIAL - hanya
butuh SATU request normal, tidak ada timing/concurrency sama sekali. Masalahnya adalah proses
transfer dipecah jadi dua langkah TERPISAH (potong saldo A, lalu kredit B) tanpa transaksi
pembungkus atau compensating rollback. Ketika langkah kedua gagal (exception apa pun - validasi
input tidak valid, koneksi database putus, dsb), langkah pertama yang sudah ter-commit tidak
pernah dibatalkan. Setiap proses multi-langkah yang mengubah state - apalagi yang menyangkut uang
- butuh salah satu dari: (1) transaksi database sungguhan (<code>BEGIN</code>/<code>COMMIT</code>/
<code>ROLLBACK</code>) yang membungkus SEMUA langkah, atau (2) compensating action eksplisit yang
membatalkan langkah-langkah sebelumnya kalau ada langkah belakangan yang gagal - supaya kondisi
tak terduga di tengah proses tidak pernah meninggalkan sistem dalam keadaan tanggung/tidak
konsisten.</p>

<?php include 'footer.php'; ?>
