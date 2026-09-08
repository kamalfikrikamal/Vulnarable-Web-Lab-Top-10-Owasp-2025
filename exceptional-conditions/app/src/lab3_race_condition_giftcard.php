<?php
$title = 'Lab 3: Race Condition Gift Card';
include 'header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'redeem') {
    // Step 1: baca kondisi kartu saat ini & cek apakah sudah pernah dipakai.
    $db = load_db();
    if ($db['giftcard']['redeemed']) {
        $message = 'Gift card ini sudah pernah dipakai (redeemed = true).';
    } else {
        // Step 2: delay artifisial - mensimulasikan waktu proses nyata, mis. memanggil
        // layanan ledger/pencatatan transaksi eksternal. Di dunia nyata delay seperti ini
        // (network call, antrian, dsb) selalu ada - inilah window balapan (race window)
        // antara "cek" dan "tandai terpakai".
        usleep(300000);

        // Step 3: BARU di sini status ditandai redeemed & saldo dikreditkan. Karena "cek"
        // (step 1) dan "tandai + kredit" (step 3) adalah dua operasi terpisah dengan jeda
        // di antaranya (bukan satu operasi atomik/transaksi), dua request yang datang
        // nyaris bersamaan bisa SAMA-SAMA lolos step 1 sebelum salah satunya sempat
        // menuliskan redeemed=true di step 3.
        //
        // Catatan implementasi: penulisan di step 3 ini pakai flock() supaya PENCATATANNYA
        // (log & saldo) tidak saling menimpa saat dua proses menulis nyaris bersamaan -
        // ini HANYA melindungi langkah TULIS-nya, bukan mengubah fakta bahwa pengecekan
        // "sudah dipakai belum" di step 1 tetap tidak atomik terhadap step 3. Race condition
        // pada logikanya tetap ada; ini cuma memastikan bukti double-redeem tercatat utuh.
        $fp = fopen(db_path(), 'c+');
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $db2 = json_decode($raw, true);
        if (!is_array($db2)) $db2 = seed_db();
        $db2['giftcard']['redeemed'] = true;
        $db2['wallet_balance'] = ($db2['wallet_balance'] ?? 0) + $db['giftcard']['balance'];
        $db2['redemption_log'][] = [
            'time' => date('Y-m-d H:i:s') . '.' . substr(microtime(), 2, 4),
            'pid' => getmypid(),
            'amount_credited' => $db['giftcard']['balance'],
            'wallet_balance_after' => $db2['wallet_balance'],
        ];
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($db2, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        $message = 'Redeem berhasil! Saldo bertambah Rp' . number_format($db['giftcard']['balance'], 0, ',', '.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    reset_db();
    $message = 'State lab direset ke kondisi awal.';
}

$db = load_db();
$card = $db['giftcard'];
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Tombol "Redeem" di bawah sengaja punya jeda 300ms antara pengecekan
"sudah dipakai belum" dan penulisan status "sudah dipakai". Coba: (1) buka halaman ini di
<strong>dua tab browser</strong> lalu klik tombol Redeem di kedua tab secepat mungkin (nyaris
bersamaan), atau (2) pakai dua request curl paralel dari terminal:</p>
<pre class="result-box">curl -s -X POST http://localhost:8079/exceptcond/lab3_race_condition_giftcard.php -d "action=redeem" &amp; \
curl -s -X POST http://localhost:8079/exceptcond/lab3_race_condition_giftcard.php -d "action=redeem" &amp; \
wait</pre>
<p class="hint">Lihat tabel "Log Redemption" di bawah - apakah gift card yang sama berhasil
di-redeem lebih dari sekali, dan saldo wallet bertambah lebih dari satu kali lipat nilai gift
card? Kalau race belum berhasil di percobaan pertama, klik "Reset State Lab" lalu ulangi (jaringan
lokal kadang membuat dua request tidak benar-benar bersamaan).</p>
</details>

<div class="lab-card">
  <h3>Gift Card <code><?php echo htmlspecialchars($card['code']); ?></code></h3>
  <p>Balance per redeem: Rp<?php echo number_format($card['balance'], 0, ',', '.'); ?></p>
  <p>Status: <span class="badge <?php echo $card['redeemed'] ? 'admin' : 'user'; ?>"><?php echo $card['redeemed'] ? 'REDEEMED' : 'BELUM DIPAKAI'; ?></span></p>
  <p>Saldo wallet kamu saat ini: <strong>Rp<?php echo number_format($db['wallet_balance'], 0, ',', '.'); ?></strong></p>
  <form method="post" style="display:inline-block; margin-right:10px;">
    <input type="hidden" name="action" value="redeem">
    <button type="submit">Redeem Gift Card</button>
  </form>
  <form method="post" style="display:inline-block;">
    <input type="hidden" name="action" value="reset">
    <button type="submit" style="background:#7f1d1d;">Reset State Lab</button>
  </form>
</div>

<?php if ($message): ?><div class="ok-box"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<h3>Log Redemption</h3>
<?php if (empty($db['redemption_log'])): ?>
<p class="hint">Belum ada redemption. Klik "Redeem Gift Card" di atas.</p>
<?php else: ?>
<table class="data-table">
<tr><th>Waktu</th><th>PID Proses PHP</th><th>Kredit</th><th>Saldo Wallet Setelahnya</th></tr>
<?php foreach ($db['redemption_log'] as $log): ?>
<tr>
  <td><?php echo htmlspecialchars($log['time']); ?></td>
  <td><?php echo htmlspecialchars((string)$log['pid']); ?></td>
  <td>Rp<?php echo number_format($log['amount_credited'], 0, ',', '.'); ?></td>
  <td>Rp<?php echo number_format($log['wallet_balance_after'], 0, ',', '.'); ?></td>
</tr>
<?php endforeach; ?>
</table>
<p class="hint">Kalau ada &gt;1 baris di log ini untuk gift card yang sama, race condition
berhasil dieksploitasi - gift card senilai Rp<?php echo number_format($card['balance'], 0, ',', '.'); ?>
sudah dicairkan lebih dari sekali.</p>
<?php endif; ?>

<p class="hint">Kenapa ini bug: "cek dulu, baru tulis" (check-then-act) TIDAK atomik. Selama ada
jeda waktu apa pun antara pengecekan status dan penulisan status baru (delay jaringan, I/O,
pemrosesan lain), dua request yang datang berdekatan bisa sama-sama lolos pengecekan sebelum salah
satu dari mereka sempat menuliskan hasilnya. Operasi yang mengubah state bernilai uang/sensitif
harus atomik (transaksi database dengan row lock, `SELECT ... FOR UPDATE`, constraint unik, atau
mekanisme locking lain) - bukan dua langkah terpisah yang bisa diselang request lain.</p>

<?php include 'footer.php'; ?>
