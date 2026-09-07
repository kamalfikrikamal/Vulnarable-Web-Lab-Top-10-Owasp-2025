<?php
$title = 'Lab 3: OTP 2FA yang Bisa Diprediksi';
require_once __DIR__ . '/lib.php';

function generate_otp($ts) {
    // VULNERABLE: seeding the PRNG explicitly with a public, guessable
    // value (the current Unix timestamp) makes its output fully
    // deterministic - anyone who knows (or brute-forces) the seed can
    // reproduce the exact same "random" sequence.
    mt_srand($ts);
    return mt_rand(100000, 999999);
}

$generated_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $ts = time();
    $_SESSION['otp_ts'] = $ts;
    $_SESSION['otp_value'] = generate_otp($ts);
    $generated_msg = "OTP 2FA untuk login admin sudah \"dikirim ke SMS terdaftar\" pada timestamp {$ts}.";
}

$verify_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $guess = (int)($_POST['otp'] ?? 0);
    if (isset($_SESSION['otp_value']) && $guess === $_SESSION['otp_value']) {
        $verify_msg = 'OTP benar! Login sebagai admin berhasil tanpa pernah tahu OTP asli yang "dikirim".';
    } else {
        $verify_msg = 'OTP salah.';
    }
}

$predicted = null;
if (isset($_GET['predict_ts'])) {
    $predicted = generate_otp((int)$_GET['predict_ts']);
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: OTP dibuat dengan <code>mt_srand($timestamp); mt_rand(100000, 999999);</code>.
<code>mt_rand()</code> memakai algoritma Mersenne Twister yang cepat tapi <strong>tidak</strong>
aman secara kriptografis (bukan CSPRNG) &mdash; kalau seed-nya (di sini: waktu generate) diketahui,
seluruh output bisa dihitung ulang persis sama. Sama seperti Lab 1, waktu generate bocor lewat
header <code>Date</code> di response HTTP (ditampilkan langsung di sini untuk latihan). Gunakan
tool "Predict OTP" di bawah dengan timestamp itu.</p>
</details>

<h3>1. Generate OTP (simulasi: admin sedang login, menerima OTP lewat SMS)</h3>
<form method="post">
  <button type="submit" name="generate" value="1">Generate OTP untuk Login Admin</button>
</form>
<?php if ($generated_msg): ?><div class="ok-box"><?php echo htmlspecialchars($generated_msg); ?></div><?php endif; ?>

<h3>2. Predict OTP (sebagai attacker, tahu timestamp generate)</h3>
<form method="get">
  <label>Timestamp generate (dari langkah 1)</label><br>
  <input type="text" name="predict_ts" value="<?php echo (int)($_SESSION['otp_ts'] ?? time()); ?>">
  <button type="submit">Predict</button>
</form>
<?php if ($predicted !== null): ?><div class="result-box">Predicted OTP: <?php echo (int)$predicted; ?></div><?php endif; ?>

<h3>3. Verify OTP (masukkan hasil prediksi untuk "login" sebagai admin)</h3>
<form method="post">
  <label>OTP</label><br>
  <input type="text" name="otp">
  <button type="submit" name="verify" value="1">Verify OTP</button>
</form>
<?php if ($verify_msg): ?><div class="<?php echo strpos($verify_msg,'berhasil')!==false ? 'result-box' : 'error-box'; ?>"><?php echo htmlspecialchars($verify_msg); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
