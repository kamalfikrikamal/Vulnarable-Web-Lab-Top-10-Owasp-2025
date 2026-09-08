<?php
$title = 'Lab 3: Tidak Ada Logging/Alerting untuk Login Gagal';
include 'header.php';

$SEED_PIN = '482';

if (!isset($_SESSION['lab3_attempts'])) $_SESSION['lab3_attempts'] = 0;

$single_result = null;
$bulk_tried = 0;
$bulk_matched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pin_single'])) {
        $pin = trim($_POST['pin_single']);
        $_SESSION['lab3_attempts']++;
        if ($pin === $SEED_PIN) {
            $single_result = true;
            // Hanya login SUKSES yang tercatat.
            append_log('login_events.log', '[' . date('Y-m-d H:i:s') . '] LOGIN SUCCESS: user=admin, pin_len=' . strlen($pin));
        } else {
            $single_result = false;
            // VULNERABLE: percobaan GAGAL sama sekali tidak ditulis ke mana pun.
            // Tidak ada baris log, tidak ada counter server-side, tidak ada apa pun.
        }
    } elseif (isset($_POST['pin_bulk'])) {
        $candidates = array_filter(array_map('trim', explode("\n", $_POST['pin_bulk'])));
        foreach ($candidates as $cand) {
            $bulk_tried++;
            $_SESSION['lab3_attempts']++;
            if ($cand === $SEED_PIN) {
                $bulk_matched = true;
                append_log('login_events.log', '[' . date('Y-m-d H:i:s') . '] LOGIN SUCCESS: user=admin, pin_len=' . strlen($cand));
            }
            // VULNERABLE: percobaan gagal dalam loop bulk ini juga tidak dicatat
            // sama sekali - ratusan percobaan bisa lewat tanpa jejak apa pun.
        }
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: PIN admin adalah 3 digit (<code>000</code>&ndash;<code>999</code>), tidak ada
rate limiting/lockout/CAPTCHA sama sekali. Yang jadi fokus lab ini bukan sekadar "brute force
berhasil" (itu topik kategori Brute Force Protection) &mdash; tapi fakta bahwa <strong>tidak ada
satu pun jejak</strong> dari ratusan percobaan gagal yang tersimpan di sistem, sehingga tim
keamanan tidak akan pernah tahu serangan ini terjadi.</p>
<p class="hint">Pakai kotak "Coba banyak PIN sekaligus" di bawah, tempel daftar PIN (satu per
baris) untuk mencoba banyak kombinasi dalam satu request. Contoh cepat: generate
<code>000</code> s.d. <code>999</code> lalu tempel semuanya.</p>
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

<h3>Coba banyak PIN sekaligus (bulk brute force)</h3>
<form method="post">
  <label>Daftar PIN, satu per baris</label><br>
  <textarea name="pin_bulk" rows="10" style="width:100%;" placeholder="000&#10;001&#10;002&#10;...&#10;999"></textarea><br>
  <button type="submit">Jalankan Semua PIN</button>
</form>
<?php if ($bulk_tried > 0): ?>
<div class="<?php echo $bulk_matched ? 'ok-box' : 'error-box'; ?>">
Mencoba <?php echo $bulk_tried; ?> PIN dalam satu request.
<?php echo $bulk_matched ? ' PIN yang benar ditemukan!' : ' Tidak ada yang cocok.'; ?>
</div>
<?php endif; ?>

<h3>Security Dashboard (apa yang tercatat sistem)</h3>

<p class="hint"><strong>Counter di bawah ini cuma untuk kamu sendiri</strong> (disimpan di
session PHP demi visibilitas trainee) &mdash; <strong>BUKAN log sistem sebenarnya</strong> dan
tidak merepresentasikan apa pun yang benar-benar dicatat/dipantau oleh aplikasi.</p>
<table class="data-table">
  <tr><th>Total percobaan login (session kamu, hanya untuk kamu)</th><td><?php echo (int)$_SESSION['lab3_attempts']; ?></td></tr>
</table>

<p class="hint">Sekarang bandingkan dengan <code>data/login_events.log</code> &mdash; ini
log sungguhan yang dipakai sistem (dan yang akan dilihat tim keamanan):</p>
<div class="result-box"><?php echo htmlspecialchars(read_log('login_events.log')) ?: '(login_events.log masih kosong)'; ?></div>

<?php if ((int)$_SESSION['lab3_attempts'] >= 50): ?>
<div class="error-box">
Kamu sudah melakukan <strong><?php echo (int)$_SESSION['lab3_attempts']; ?></strong> percobaan
login (termasuk yang gagal). Lihat isi <code>login_events.log</code> di atas: kalau belum
berhasil menebak PIN, log itu <strong>masih kosong</strong> &mdash; tidak ada satu baris pun yang
mencatat puluhan/ratusan percobaan gagal ini. Kalau sudah berhasil, hanya baris SUCCESS terakhir
yang muncul, seolah-olah admin cuma login sekali dengan mulus &mdash; tidak ada jejak bahwa
sebenarnya ada serangan brute force besar-besaran sebelumnya. Pola "ratusan login gagal beruntun
ke satu akun dalam hitungan detik" ini seharusnya jadi sinyal deteksi/alert paling dasar di
sistem manapun.
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
