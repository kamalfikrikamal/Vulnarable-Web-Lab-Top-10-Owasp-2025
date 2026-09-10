<?php
$title = 'Lab 8: Log Ada, Tapi Tidak Cukup Konteks untuk Investigasi';
include 'header.php';

if (!isset($db['payment_log'])) $db['payment_log'] = [];
if (!isset($db['payment_log_v2'])) $db['payment_log_v2'] = [];

$TARGET_AMOUNT = 'Rp 50.000.000';
$new_entry = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_pembayaran_baru'])) {
    // Log versi 2: format yang BENAR, menyertakan seluruh identitas yang
    // dibutuhkan untuk menelusuri balik siapa/apa yang memicu transaksi ini.
    $user_id = 17;
    $ip = '203.0.113.42';
    $session_id = 'sess_' . substr(session_id() ?: 'demo0000000000000000000000000000', 0, 12);
    $request_id = 'req_' . substr(md5(uniqid('', true)), 0, 10);
    $new_entry = '[' . date('Y-m-d H:i:s') . '] Payment processed: amount=' . $TARGET_AMOUNT
        . ', user_id=' . $user_id . ', ip=' . $ip . ', session_id=' . $session_id . ', request_id=' . $request_id;
    $db['payment_log_v2'][] = $new_entry;
    save_db($db);
}

// Cari baris log lama (v1) yang jumlahnya cocok dengan laporan finance.
$flagged_line = null;
foreach ($db['payment_log'] as $line) {
    if (strpos($line, $TARGET_AMOUNT) !== false) { $flagged_line = $line; break; }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: tim finance melaporkan ada transaksi mencurigakan senilai
<strong><?php echo $TARGET_AMOUNT; ?></strong> dan minta bantuan mencari tahu siapa pelakunya dari
log pembayaran. Cari baris log yang jumlahnya cocok di bawah &mdash; lalu coba jawab: siapa
user-nya? dari IP mana? request/session mana? Kamu akan sadar log lama (v1) memang "ada", tapi
sama sekali tidak menjawab pertanyaan itu.</p>
<p class="hint">Klik tombol "Proses Pembayaran Baru" untuk melihat perbandingan: bagaimana
seharusnya satu baris log pembayaran ditulis supaya benar-benar bisa diinvestigasi.</p>
</details>

<h3>Laporan Tim Finance</h3>
<p>"Ada 1 transaksi mencurigakan senilai <strong><?php echo $TARGET_AMOUNT; ?></strong> yang perlu
diinvestigasi &mdash; tolong cari tahu siapa pelakunya dari log yang ada."</p>

<h3>Log Pembayaran (format lama / v1) &mdash; yang tersedia saat ini</h3>
<p class="hint">Ini seluruh isi <code>payment_log</code>. Baris yang jumlahnya cocok dengan laporan
finance ditandai di bawah.</p>
<div class="result-box"><?php echo htmlspecialchars(implode("\n", $db['payment_log'])); ?></div>

<?php if ($flagged_line): ?>
<div class="error-box">
Baris yang cocok dengan laporan finance ditemukan:
<pre style="white-space:pre-wrap;"><?php echo htmlspecialchars($flagged_line); ?></pre>
Perhatikan: baris ini hanya berisi <strong>timestamp</strong> dan <strong>jumlah</strong>. Tidak
ada <code>user_id</code>, tidak ada <code>ip</code>, tidak ada <code>session_id</code>, tidak ada
<code>request_id</code> &mdash; literal tidak ada satu pun informasi yang bisa dipakai menelusuri
siapa yang melakukan transaksi ini, dari perangkat/lokasi mana, atau request mana yang memicunya.
Investigasi mentok total, walaupun secara teknis "sudah ada logging".
</div>
<?php endif; ?>

<h3>Bandingkan: log yang ditulis dengan konteks investigasi yang cukup (format baru / v2)</h3>
<p class="hint">Sekarang coba proses satu pembayaran baru dengan jumlah yang sama, dan lihat
seperti apa seharusnya satu baris log pembayaran yang layak untuk investigasi keamanan.</p>
<form method="post">
  <button type="submit" name="proses_pembayaran_baru" value="1">Proses Pembayaran Baru (<?php echo $TARGET_AMOUNT; ?>)</button>
</form>

<?php if ($new_entry): ?>
<div class="ok-box">Pembayaran baru diproses dan dicatat dengan format v2 di bawah.</div>
<?php endif; ?>

<div class="result-box" style="margin-top:10px;"><?php echo htmlspecialchars(implode("\n", $db['payment_log_v2'])) ?: '(payment_log_v2 masih kosong, klik tombol di atas dulu)'; ?></div>

<?php if (!empty($db['payment_log_v2'])): ?>
<div class="ok-box">
Bandingkan kedua format: baris v2 di atas menyertakan <code>user_id</code>, <code>ip</code>,
<code>session_id</code>, dan <code>request_id</code> &mdash; dengan ini, satu baris log saja
sudah cukup untuk langsung tahu siapa pelakunya, dari mana, dan request/session persis mana yang
bisa dikorelasikan dengan sistem lain (mis. log gateway, log aplikasi lain) saat investigasi
sungguhan berlangsung.
</div>
<?php endif; ?>

<div class="error-box" style="margin-top:20px;">
Kenapa ini penting: mencatat "sesuatu" ke log bukan berarti sudah cukup. Baris log yang terlihat
valid dan "ada isinya" tetap bisa membuat investigasi insiden praktis mustahil kalau ia tidak
menyertakan identitas yang saling berkorelasi &mdash; <strong>user/akun, session, alamat IP, dan
request ID</strong> &mdash; yang dibutuhkan untuk menelusuri satu event balik ke sumbernya,
apalagi ketika harus digabungkan dengan log dari sistem lain saat investigasi sungguhan.
</div>

<?php include 'footer.php'; ?>
