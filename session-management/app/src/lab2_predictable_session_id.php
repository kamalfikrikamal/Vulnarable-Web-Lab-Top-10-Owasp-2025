<?php
$title = 'Lab 2: Session ID Bisa Diprediksi';
require_once __DIR__ . '/lib.php';
$db = load_db();

function issue_sequential_token(&$db, $username) {
    // VULNERABLE: session token is just an incrementing counter formatted
    // as a string - completely predictable once you've seen a single one.
    $token = 'SESS-' . $db['next_seq'];
    $db['sessions'][$token] = $username;
    $db['next_seq']++;
    return $token;
}

if (isset($_GET['victim_login'])) {
    // Simulates alice logging in on her own device, elsewhere - the
    // attacker does NOT see this token directly.
    issue_sequential_token($db, 'alice');
    save_db($db);
    header('Location: lab2_predictable_session_id.php?victim_logged_in=1');
    exit;
}

$attacker_token = null;
if (isset($_GET['attacker_login'])) {
    $attacker_token = issue_sequential_token($db, 'attacker');
    save_db($db);
}

$check_token = $_GET['check_token'] ?? '';
$check_result = '';
if ($check_token !== '') {
    $u = username_for_token($db, $check_token);
    $check_result = $u ? "Token valid! Mengakses akun sebagai: $u" : 'Token tidak valid / tidak dikenal.';
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: setiap token baru cuma "SESS-" + angka urut berikutnya. Klik "Simulasikan
alice login" (mensimulasikan korban login di perangkat lain - kamu TIDAK melihat token-nya
langsung), lalu klik "Login sebagai attacker" untuk melihat token milikmu sendiri. Karena
token cuma angka urut, token alice pasti persis satu angka sebelum token attacker (kalau alice
login tepat sebelum kamu). Coba tebak lewat form "Cek akses dengan token" di bawah.</p>
</details>

<p><a href="?victim_login=1">1. Simulasikan alice login (di perangkat lain)</a></p>
<?php if (isset($_GET['victim_logged_in'])): ?><div class="ok-box">alice baru saja login di tempat lain (token tidak ditampilkan ke kamu).</div><?php endif; ?>

<p><a href="?attacker_login=1">2. Login sebagai attacker (lihat token sendiri)</a></p>
<?php if ($attacker_token): ?><div class="result-box">Token attacker: <?php echo htmlspecialchars($attacker_token); ?></div><?php endif; ?>

<h3>3. Cek akses dengan token (tebak token alice)</h3>
<form method="get">
  <label>Token</label><br>
  <input type="text" name="check_token" value="<?php echo htmlspecialchars($check_token); ?>">
  <button type="submit">Cek Akses</button>
</form>
<?php if ($check_result): ?><div class="<?php echo strpos($check_result,'alice')!==false ? 'error-box' : 'ok-box'; ?>"><?php echo htmlspecialchars($check_result); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
