<?php
$title = 'Lab 2: State Cookie Tanpa Integrity Protection';
require_once __DIR__ . '/lib.php';

// Seed a realistic starting state on first visit.
if (!isset($_COOKIE['cart_state'])) {
    $initial = ['cart_total' => 150000, 'wallet_balance' => 0, 'role' => 'user'];
    $encoded = base64_encode(json_encode($initial));
    setcookie('cart_state', $encoded, time() + 3600, '/');
    $_COOKIE['cart_state'] = $encoded;
}

$raw_cookie = $_COOKIE['cart_state'];

// VULNERABLE: the server base64-decodes + json-decodes whatever comes back in
// the cookie and trusts it completely - there is no signature/HMAC, no
// server-side session record to cross-check against. Whatever JSON the
// client sends IS the state, full stop.
$state = json_decode(base64_decode($raw_cookie), true);
if (!is_array($state)) {
    $state = ['cart_total' => 150000, 'wallet_balance' => 0, 'role' => 'user'];
}

$checkout_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $total = $state['cart_total'] ?? 0;
    $balance = $state['wallet_balance'] ?? 0;
    if ($balance >= $total) {
        $checkout_result = "Checkout berhasil! Rp $total dipotong dari saldo (sisa saldo: Rp " . ($balance - $total) . ").";
    } else {
        $checkout_result = "Checkout gagal, saldo (Rp $balance) tidak cukup untuk total (Rp $total).";
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: checkout berhasil padahal saldo aslimu Rp 0, dan/atau ubah <code>role</code>
jadi <code>admin</code>. Seluruh state (saldo, total belanja, role) disimpan di cookie
<code>cart_state</code> sebagai <code>base64_encode(json_encode(...))</code> &mdash; server tidak
menyimpan salinan "asli" di server, dan tidak ada tanda tangan apa pun yang mencegah kamu
mengubah isinya sebelum dikirim balik.</p>
<p class="hint">Langkah: (1) buka DevTools &rarr; Application &rarr; Cookies, salin nilai cookie
<code>cart_state</code>. (2) Decode base64-nya (console: <code>atob("...")</code>) untuk melihat
JSON aslinya. (3) Edit field <code>wallet_balance</code> (atau <code>cart_total</code>,
<code>role</code>) jadi nilai apa pun yang kamu mau. (4) Encode ulang ke base64
(<code>btoa(JSON.stringify(obj))</code>). (5) Set kembali sebagai nilai cookie
<code>cart_state</code>, reload halaman ini.</p>
</details>

<h3>Isi cookie <code>cart_state</code> saat ini (raw, base64)</h3>
<div class="result-box"><?php echo htmlspecialchars($raw_cookie); ?></div>

<h3>Isi cookie <code>cart_state</code> saat ini (setelah decode)</h3>
<div class="result-box"><?php echo htmlspecialchars(json_encode($state, JSON_PRETTY_PRINT)); ?></div>

<table class="data-table">
<tr><th>Field</th><th>Nilai</th></tr>
<tr><td>Total belanja (cart_total)</td><td>Rp <?php echo htmlspecialchars((string)($state['cart_total'] ?? '?')); ?></td></tr>
<tr><td>Saldo Anda (wallet_balance)</td><td>Rp <?php echo htmlspecialchars((string)($state['wallet_balance'] ?? '?')); ?></td></tr>
<tr><td>Role</td><td><span class="badge <?php echo (($state['role'] ?? '') === 'admin') ? 'admin' : 'user'; ?>"><?php echo htmlspecialchars($state['role'] ?? '?'); ?></span></td></tr>
</table>

<form method="post" style="margin-top:14px;">
  <button type="submit" name="checkout" value="1">Checkout</button>
</form>
<?php if ($checkout_result): ?>
<div class="<?php echo strpos($checkout_result, 'berhasil') !== false ? 'result-box' : 'error-box'; ?>"><?php echo htmlspecialchars($checkout_result); ?></div>
<?php endif; ?>

<p>Perhatikan: ini bukan soal cookie-nya "tidak dienkripsi" (kamu memang boleh melihat isinya,
itu bukan intinya). Intinya adalah <strong>tidak ada apa pun yang mencegah kamu MENGUBAH</strong>
nilainya dan diterima begitu saja oleh server sebagai data yang sah &mdash; tidak ada checksum,
tidak ada HMAC, tidak ada validasi silang ke state di server. Server hanya mempercayai apa pun
yang tertulis di cookie.</p>

<?php include 'footer.php'; ?>
