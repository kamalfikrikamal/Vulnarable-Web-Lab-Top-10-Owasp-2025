<?php
$title = 'Lab 11: Cookie Tanpa Flag Secure';
require_once __DIR__ . '/lib.php';

$token = ensure_demo_cookie('demo_session_secure_off', [
    'expires' => time() + 3600,
    'path' => '/secmisconfig/',
    'httponly' => true,
    'secure' => false, // VULNERABLE: seharusnya true kalau aplikasi punya versi HTTPS
    'samesite' => 'Lax',
]);

$db = load_db();
include 'header.php';
?>

<p>Cookie session <code>demo_session_secure_off</code> diset tanpa flag <code>Secure</code>.
Flag ini seharusnya membuat browser <strong>hanya</strong> mengirim cookie lewat koneksi HTTPS
— tanpanya, cookie yang sama persis akan tetap dikirim kalau saja ada satu titik akses saja ke
aplikasi ini yang masih plain HTTP (subdomain lama yang lupa di-redirect, load balancer health
check, jaringan publik/WiFi yang di-MITM, dst).</p>

<h3>1. Konfirmasi lewat curl</h3>
<pre class="result-box">curl -i http://localhost:8079/secmisconfig/lab11_missing_secure.php | grep -i set-cookie</pre>
<p class="hint">Tidak ada kata <code>Secure</code> di header <code>Set-Cookie</code>.</p>

<h3>2. Simulasi "titik akses HTTP yang lupa di-redirect"</h3>
<p>Lab ini sendiri berjalan di HTTP biasa (tidak ada TLS di environment training ini) — anggap
saja endpoint di bawah ini adalah subdomain legacy/staging yang seharusnya sudah tidak dipakai
lagi, tapi masih hidup dan menerima cookie yang sama:</p>
<a class="btn" href="?legacy_http_endpoint=1">Akses endpoint legacy (simulasi plain HTTP)</a>

<?php if (isset($_GET['legacy_http_endpoint'])): ?>
<div class="result-box">
Cookie <code>demo_session_secure_off</code> yang diterima endpoint "legacy" ini:
<strong><?php echo htmlspecialchars($_COOKIE['demo_session_secure_off'] ?? '(tidak ada)'); ?></strong>
<br><br>
Nilai ini <em>identik</em> dengan yang dipakai di endpoint utama — kalau endpoint ini benar-benar
plain HTTP di dunia nyata (bukan simulasi), siapa pun yang menyadap lalu lintas jaringan
(mis. di WiFi publik) bisa membaca token sesi ini apa adanya dan memakainya untuk membajak sesi
korban, tanpa perlu bug XSS atau apa pun.
</div>
<?php endif; ?>

<details class="hint-box">
<summary>Kenapa flag ini tetap wajib walau "aplikasinya sudah HTTPS semua"</summary>
<p class="hint">Klaim "semua endpoint sudah HTTPS" susah dijamin benar 100% selamanya — subdomain
baru, service internal, atau konfigurasi load balancer yang berubah bisa diam-diam membuka jalur
HTTP baru. Flag <code>Secure</code> membuat browser sendiri yang menegakkan aturan "jangan pernah
kirim cookie ini lewat HTTP", terlepas dari kesalahan konfigurasi di sisi manapun.</p>
</details>

<?php include 'footer.php'; ?>
