<?php
require_once __DIR__ . '/lib.php';

// Intentionally short/weak secret key - on purpose. It's short enough to be
// cracked via exhaustive/dictionary brute force, so trainees have a reliable
// fallback exploitation path even if the timing attack below proves too
// noisy to pull off cleanly in their environment.
define('SECRET_KEY', 'key123');

// VULNERABLE: naive byte-by-byte comparison that mimics what a plain `==`
// string comparison effectively does under the hood (PHP stops as soon as it
// finds a mismatching byte). A raw `==`/memcmp early-exit gap is normally far
// too small (nanoseconds) to reliably measure over HTTP/loopback, so we add a
// deliberate usleep(50) per correctly-matched leading byte here ONLY to
// exaggerate that real (but usually imperceptible) timing gap enough to be
// measurable for teaching purposes. hash_equals() below, by contrast, always
// takes the same amount of time no matter how many leading bytes match -
// that's the whole point of using it for secret comparisons.
function insecure_timing_compare($expected, $actual) {
    $len = min(strlen($expected), strlen($actual));
    for ($i = 0; $i < $len; $i++) {
        if ($expected[$i] !== $actual[$i]) {
            return false;
        }
        usleep(50); // artificial exaggeration of the natural `==` timing gap
    }
    return strlen($expected) === strlen($actual);
}

// Fast raw text endpoint for scripted byte-by-byte timing attacks:
//   GET lab3_timing_unsafe_hmac.php?data=...&sig_guess=<candidate signature prefix>
// Kept separate from the main page so a curl/script loop gets a minimal,
// fast response instead of a full HTML page on every guess.
if (isset($_GET['data']) && isset($_GET['sig_guess'])) {
    $data = (string)$_GET['data'];
    $guess = (string)$_GET['sig_guess'];
    $expected_sig = hash_hmac('sha256', $data, SECRET_KEY);
    $match = insecure_timing_compare($expected_sig, $guess);
    header('Content-Type: text/plain');
    echo $match ? 'MATCH' : 'NO_MATCH';
    exit;
}

$title = 'Lab 3: Verifikasi Signature Pakai == (Bukan hash_equals())';

// Seed a default voucher cookie on first visit: data|signature
if (!isset($_COOKIE['voucher'])) {
    $seed_data = 'user=guest;discount=10';
    $seed_sig = hash_hmac('sha256', $seed_data, SECRET_KEY);
    setcookie('voucher', "$seed_data|$seed_sig", time() + 3600, '/');
    $_COOKIE['voucher'] = "$seed_data|$seed_sig";
}

$raw_cookie = $_COOKIE['voucher'];
$cookie_parts = array_pad(explode('|', $raw_cookie, 2), 2, '');
$cookie_data = $cookie_parts[0];
$cookie_sig = $cookie_parts[1];

$verify_data = $cookie_data;
$verify_sig = $cookie_sig;
$did_verify = false;

// GET quick-check form: ?data=X&sig=Y - returns a distinguishable page state.
if (isset($_GET['data']) && isset($_GET['sig'])) {
    $verify_data = (string)$_GET['data'];
    $verify_sig = (string)$_GET['sig'];
    $did_verify = true;
}

// POST "Verify Voucher" form.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_data'], $_POST['verify_sig'])) {
    $verify_data = $_POST['verify_data'];
    $verify_sig = $_POST['verify_sig'];
    $did_verify = true;
}

$verify_result = '';
if ($did_verify) {
    $expected_sig = hash_hmac('sha256', $verify_data, SECRET_KEY);
    // VULNERABLE: `==` on two strings is NOT constant-time - PHP compares
    // byte by byte and can return as soon as it finds a difference. A string
    // that matches more leading bytes of the real signature can therefore
    // take (very slightly) longer to get rejected than one that mismatches
    // right away. hash_equals() exists specifically to close this gap by
    // always comparing every byte regardless of where the first mismatch is.
    if ($verify_sig == $expected_sig) {
        $verify_result = 'Voucher valid, diskon diterapkan!';
    } else {
        $verify_result = 'Voucher tidak valid.';
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: dapatkan voucher dengan signature yang valid TANPA pernah tahu
<code>SECRET_KEY</code> di server. Ada dua jalur:</p>
<p class="hint"><strong>Jalur 1 (timing attack, konsep utama lab ini):</strong> endpoint verifikasi
membandingkan signature memakai <code>==</code>, bukan <code>hash_equals()</code>. Ini tidak
konstan waktu &mdash; tebakan yang cocok di lebih banyak byte awal butuh waktu (sedikit) lebih
lama untuk ditolak. Coba tebak signature satu karakter hex demi satu karakter lewat endpoint
<code>?data=...&amp;sig_guess=...</code> sambil mengukur waktu respons (lihat README untuk contoh
script). Endpoint ini balas <code>MATCH</code>/<code>NO_MATCH</code> dalam teks polos supaya cepat
diukur.</p>
<p class="hint"><strong>Jalur 2 (fallback lebih sederhana):</strong> <code>SECRET_KEY</code>
sengaja dibuat pendek/lemah. Kalau kamu tahu satu pasang <code>data</code> dan
<code>signature</code> yang valid (misalnya dari cookie voucher kamu sendiri di bawah), kamu bisa
brute force <code>SECRET_KEY</code>-nya secara offline: coba banyak kandidat key, hitung
<code>hash_hmac('sha256', $data, $candidate_key)</code>, dan bandingkan ke signature yang kamu
punya. Begitu key ditemukan, kamu bisa memalsukan voucher APA PUN (misalnya
<code>discount=100</code>) dengan signature yang sah.</p>
</details>

<h3>Cookie <code>voucher</code> saat ini</h3>
<div class="result-box"><?php echo htmlspecialchars($raw_cookie); ?></div>
<table class="data-table">
<tr><th>Bagian</th><th>Nilai</th></tr>
<tr><td>data</td><td><?php echo htmlspecialchars($cookie_data); ?></td></tr>
<tr><td>signature (HMAC-SHA256)</td><td style="font-family:monospace;font-size:12px;"><?php echo htmlspecialchars($cookie_sig); ?></td></tr>
</table>

<h3>Verify Voucher</h3>
<form method="post">
  <label>data</label><br>
  <input type="text" name="verify_data" value="<?php echo htmlspecialchars($verify_data); ?>"><br>
  <label>signature</label><br>
  <input type="text" name="verify_sig" value="<?php echo htmlspecialchars($verify_sig); ?>"><br>
  <button type="submit">Verify Voucher</button>
</form>
<p class="hint">Tips: endpoint ini juga bisa dipanggil lewat GET untuk keperluan script:
<code>?data=...&amp;sig=...</code></p>

<?php if ($verify_result): ?>
<div class="<?php echo strpos($verify_result, 'valid,') !== false ? 'result-box' : 'error-box'; ?>"><?php echo htmlspecialchars($verify_result); ?></div>
<?php endif; ?>

<p>Ingat: <code>SECRET_KEY</code> di server ini sengaja pendek (hanya beberapa karakter) untuk
mensimulasikan kesalahan umum &mdash; kunci HMAC yang lemah membuat forge signature via brute
force jadi praktis, bahkan tanpa perlu mengeksploitasi celah timing sama sekali.</p>

<?php include 'footer.php'; ?>
