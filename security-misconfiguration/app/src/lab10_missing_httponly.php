<?php
$title = 'Lab 10: Cookie Tanpa Flag HttpOnly';
require_once __DIR__ . '/lib.php';

$token = ensure_demo_cookie('demo_session_httponly_off', [
    'expires' => time() + 3600,
    'path' => '/secmisconfig/',
    'httponly' => false, // VULNERABLE: seharusnya true
    'secure' => false,
    'samesite' => 'Lax',
]);

$db = load_db();
include 'header.php';
?>

<p>Cookie session <code>demo_session_httponly_off</code> baru saja diset ke browser kamu tanpa
flag <code>HttpOnly</code>. Flag ini seharusnya mencegah JavaScript membaca cookie lewat
<code>document.cookie</code> — satu-satunya lapisan pertahanan kalau suatu saat halaman ini
(atau halaman lain di origin yang sama) punya bug XSS.</p>

<h3>1. Konfirmasi lewat curl</h3>
<pre class="result-box">curl -i http://localhost:8079/secmisconfig/lab10_missing_httponly.php | grep -i set-cookie</pre>
<p class="hint">Perhatikan header <code>Set-Cookie</code>: tidak ada kata <code>HttpOnly</code>
di dalamnya sama sekali.</p>

<h3>2. Buktikan dampaknya lewat XSS</h3>
<p>Halaman ini juga mencetak parameter <code>?msg=</code> tanpa encoding (bug XSS reflected biasa
— sengaja disediakan di sini untuk membuktikan dampak, bukan fokus lab ini). Coba:</p>
<pre class="result-box">http://localhost:8079/secmisconfig/lab10_missing_httponly.php?msg=&lt;script&gt;document.title=document.cookie&lt;/script&gt;</pre>
<p class="hint">Perhatikan judul tab browser berubah jadi isi <code>document.cookie</code>, lengkap
dengan nilai <code>demo_session_httponly_off</code>. Kalau flag <code>HttpOnly</code> terpasang,
<code>document.cookie</code> tidak akan pernah menampilkan cookie ini — bug XSS di atas jadi jauh
lebih terbatas dampaknya (cuma bisa deface halaman, tidak bisa mencuri token sesi).</p>

<?php if (isset($_GET['msg'])): ?>
<div class="result-box"><strong>Pesan:</strong> <?php echo $_GET['msg']; ?></div>
<?php endif; ?>

<details class="hint-box">
<summary>Kenapa ini penting sebagai lapisan pertahanan terpisah</summary>
<p class="hint"><code>HttpOnly</code> tidak mencegah XSS terjadi — ini murni mitigasi
<em>dampak</em>. Aplikasi tetap harus memperbaiki XSS-nya (lihat kategori XSS terpisah), tapi
selama itu belum sempurna 100% di semua endpoint, <code>HttpOnly</code> adalah jaring pengaman
murah yang seharusnya selalu dipasang di setiap cookie sesi/autentikasi tanpa terkecuali.</p>
</details>

<?php include 'footer.php'; ?>
