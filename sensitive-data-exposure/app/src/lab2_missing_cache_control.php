<?php
require_once __DIR__ . '/lib.php';
$db = load_db();
$mode = ($_GET['mode'] ?? 'insecure') === 'secure' ? 'secure' : 'insecure';

if (isset($_GET['clear_cache'])) {
    @unlink(shared_cache_path());
    header('Location: lab2_missing_cache_control.php?mode=' . $mode);
    exit;
}

$user = find_user($db, current_username());
$body = '<div class="result-box">Akun: ' . htmlspecialchars($user['username']) .
    '<br>Saldo: Rp' . htmlspecialchars($user['balance']) .
    '<br>Kartu tersimpan: ' . htmlspecialchars($user['card']) . '</div>';

$served_from_cache = false;

if ($mode === 'secure') {
    // FIXED: tell every cache (browser, proxy, CDN) along the way that
    // this response must never be stored or reused for another request.
    header('Cache-Control: no-store, private');
} else {
    // VULNERABLE: no Cache-Control header at all. A shared cache sitting
    // in front of the app (corporate proxy, CDN, even browser disk cache
    // on a shared computer) is free to store this response and replay it
    // to the NEXT visitor, regardless of who they are.
    $cache_file = shared_cache_path();
    if (file_exists($cache_file)) {
        $body = file_get_contents($cache_file);
        $served_from_cache = true;
    } else {
        file_put_contents($cache_file, $body);
    }
}

$title = 'Lab 2: Missing Cache-Control (mode: ' . $mode . ')';
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: mode <code>insecure</code> di halaman ini tidak pernah mengirim header
<code>Cache-Control</code>, sehingga disimulasikan seolah ada shared cache (proxy kantor, CDN,
atau disk cache browser di komputer bersama) yang menyimpan response pertama dan
menyajikannya ulang ke pengunjung berikutnya <strong>siapa pun mereka</strong>. Langkah:</p>
<ol class="hint">
<li>"Ganti ke bob" di navbar, buka halaman ini dengan <code>?mode=insecure</code> &mdash; data
bob ter-cache.</li>
<li>"Ganti ke alice", buka <code>?mode=insecure</code> lagi &mdash; alice melihat data BOB dari
cache, bukan datanya sendiri!</li>
<li>Klik "Clear cache", ulangi dengan <code>?mode=secure</code> &mdash; setiap user selalu
melihat datanya sendiri karena <code>Cache-Control: no-store</code> mencegah penyimpanan sama
sekali.</li>
</ol>
<p class="hint">Cek juga dengan curl: <code>curl -I "http://target/lab2_missing_cache_control.php?mode=insecure"</code>
vs <code>?mode=secure</code> &mdash; bandingkan ada/tidaknya header <code>Cache-Control</code>.</p>
</details>

<p>Mode saat ini: <strong><?php echo htmlspecialchars($mode); ?></strong> |
<a href="?mode=insecure">mode=insecure</a> | <a href="?mode=secure">mode=secure</a> |
<a href="?mode=<?php echo $mode; ?>&clear_cache=1">Clear cache</a></p>

<?php echo $body; ?>

<?php if ($served_from_cache): ?>
<div class="error-box">Konten di atas disajikan dari SHARED CACHE, bukan data akun <?php echo htmlspecialchars(current_username()); ?> yang sedang login sekarang &mdash; bukti kebocoran data lintas user akibat cache tanpa kontrol.</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
