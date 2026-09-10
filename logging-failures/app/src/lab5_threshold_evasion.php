<?php
$title = 'Lab 5: Alert Threshold Bisa Dihindari dengan Pacing';
include 'header.php';

$SEED_PIN5 = '705';
$WINDOW_SECONDS = 60;
$THRESHOLD = 20;

if (!isset($db['threshold_login_fails'])) $db['threshold_login_fails'] = [];
if (!isset($db['threshold_alerts_fired'])) $db['threshold_alerts_fired'] = 0;
if (!isset($db['threshold_total_attempts'])) $db['threshold_total_attempts'] = 0;

$now = time();

// Selalu prune dulu: buang timestamp percobaan gagal yang sudah lebih tua
// dari window aktif (60 detik), sama seperti sistem alerting "sliding window"
// sungguhan akan lakukan.
$db['threshold_login_fails'] = array_values(array_filter(
    $db['threshold_login_fails'],
    function ($ts) use ($now, $WINDOW_SECONDS) { return ($now - $ts) <= $WINDOW_SECONDS; }
));

$single_result = null;
$bulk_tried = 0;
$bulk_matched = false;
$just_fired = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pin_single'])) {
        $pin = trim($_POST['pin_single']);
        if ($pin === $SEED_PIN5) {
            $single_result = true;
        } else {
            $single_result = false;
            $db['threshold_login_fails'][] = $now;
            $db['threshold_total_attempts']++;
        }
    } elseif (isset($_POST['pin_bulk'])) {
        $candidates = array_filter(array_map('trim', explode("\n", $_POST['pin_bulk'])));
        foreach ($candidates as $cand) {
            $bulk_tried++;
            if ($cand === $SEED_PIN5) {
                $bulk_matched = true;
            } else {
                $db['threshold_login_fails'][] = $now;
                $db['threshold_total_attempts']++;
            }
        }
    }

    // VULNERABLE (secara desain, demi mendemonstrasikan celahnya): alert HANYA
    // dicek terhadap window 60 detik yang aktif SAAT INI. Kalau jumlah percobaan
    // gagal di dalam window ini > THRESHOLD, baru alert terpicu. Tidak ada
    // memori/statistik jangka panjang di luar window ini.
    $current_window_count = count($db['threshold_login_fails']);
    if ($current_window_count > $THRESHOLD) {
        $db['threshold_alerts_fired']++;
        $just_fired = true;
        append_log('brute_alerts.log', '[' . date('Y-m-d H:i:s') . '] ALERT: possible brute force! '
            . $current_window_count . ' percobaan gagal dalam ' . $WINDOW_SECONDS . ' detik terakhir.');
    }
}

save_db($db);
$current_window_count = count($db['threshold_login_fails']);
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: berbeda dari Lab 3 (yang <strong>sama sekali tidak ada</strong> alerting),
lab ini punya mekanisme alert sungguhan: server menghitung berapa banyak percobaan login gagal
yang masuk dalam <strong>60 detik terakhir</strong> (sliding window), dan memicu
"<code>ALERT: possible brute force!</code>" kalau jumlahnya lebih dari <strong>20</strong> dalam
window itu.</p>
<p class="hint">PIN admin 3 digit: <code>705</code>. Pakai kotak "Coba banyak PIN sekaligus" untuk
mengirim satu batch tebakan PIN salah (misalnya 15 baris, sengaja di bawah 20 supaya tidak pernah
memicu alert dalam satu request). <strong>Tunggu lebih dari 60 detik</strong>, lalu kirim batch
15 PIN salah lagi. Ulangi beberapa kali (reload halaman untuk menunggu, lalu submit lagi).</p>
<p class="hint">Perhatikan dashboard di bawah: "Jumlah alert yang pernah terpicu sejauh ini" akan
tetap <strong>0</strong> walaupun "Total percobaan gagal sepanjang waktu" terus naik ke puluhan
bahkan ratusan &mdash; karena setiap batch, dilihat sendiri-sendiri, tidak pernah melewati 20
percobaan dalam 60 detik. Threshold yang naif seperti ini bisa "dikelabui" hanya dengan mengatur
kecepatan (pacing) serangan.</p>
</details>

<h3>Login (satu PIN)</h3>
<form method="post" style="margin-bottom:20px;">
  <label>PIN admin (3 digit)</label><br>
  <input type="text" name="pin_single" value="" maxlength="3"><br>
  <button type="submit">Coba Login</button>
</form>
<?php if ($single_result !== null): ?>
<div class="<?php echo $single_result ? 'ok-box' : 'error-box'; ?>">
<?php echo $single_result ? 'Login berhasil! PIN benar.' : 'PIN salah.'; ?>
</div>
<?php endif; ?>

<h3>Coba banyak PIN sekaligus (bulk, satu request)</h3>
<form method="post">
  <label>Daftar PIN, satu per baris (batch ini akan dihitung sebagai satu window request)</label><br>
  <textarea name="pin_bulk" rows="10" style="width:100%;" placeholder="111&#10;222&#10;333&#10;...(maks. beberapa belas per batch kalau mau tetap di bawah threshold)"></textarea><br>
  <button type="submit">Jalankan Batch Ini</button>
</form>
<?php if ($bulk_tried > 0): ?>
<div class="<?php echo $bulk_matched ? 'ok-box' : 'error-box'; ?>">
Batch ini mencoba <?php echo $bulk_tried; ?> PIN.
<?php echo $bulk_matched ? ' PIN yang benar ditemukan!' : ' Tidak ada yang cocok (semua dihitung sebagai gagal).'; ?>
</div>
<?php endif; ?>

<?php if ($just_fired): ?>
<div class="error-box">
ALERT terpicu barusan! Window 60 detik aktif berisi <?php echo $current_window_count; ?> percobaan
gagal, melewati threshold (&gt;20).
</div>
<?php endif; ?>

<h3>Security Dashboard (alerting sungguhan, bukan simulasi)</h3>
<table class="data-table">
  <tr><th>Percobaan gagal dalam window aktif (60 detik terakhir)</th><td><?php echo $current_window_count; ?></td></tr>
  <tr><th>Threshold alert</th><td>&gt; 20 percobaan gagal / 60 detik</td></tr>
  <tr><th>Jumlah alert yang pernah terpicu sejauh ini</th><td><strong><?php echo (int)$db['threshold_alerts_fired']; ?></strong></td></tr>
  <tr><th>Total percobaan gagal sepanjang waktu (all-time, semua batch, semua trainee)</th><td><strong><?php echo (int)$db['threshold_total_attempts']; ?></strong></td></tr>
</table>

<p class="hint">Log alert sungguhan (<code>data/brute_alerts.log</code>) &mdash; ini yang akan
dilihat/dipantau tim SOC:</p>
<div class="result-box"><?php echo htmlspecialchars(read_log('brute_alerts.log')) ?: '(brute_alerts.log masih kosong)'; ?></div>

<?php if ((int)$db['threshold_total_attempts'] > $THRESHOLD && (int)$db['threshold_alerts_fired'] === 0): ?>
<div class="error-box">
Perhatikan: total sudah <strong><?php echo (int)$db['threshold_total_attempts']; ?></strong>
percobaan login gagal tercatat sepanjang waktu &mdash; jauh melewati angka threshold (20) &mdash;
tapi kolom "Jumlah alert yang pernah terpicu" masih <strong>0</strong> dan
<code>brute_alerts.log</code> masih kosong. Ini membuktikan bahwa aturan "lebih dari 20 percobaan
gagal dalam satu window 60 detik" bisa dihindari sepenuhnya oleh penyerang yang sabar dan mengatur
kecepatan serangannya (low-and-slow) &mdash; padahal secara kumulatif serangan brute force yang
sesungguhnya sedang berlangsung.
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
