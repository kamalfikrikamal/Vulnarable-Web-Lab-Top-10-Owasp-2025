<?php
$title = 'Lab 5: CORS Misconfiguration';
require_once __DIR__ . '/lib.php';

// Simulasi user yang "sudah login" - begitu lab ini pertama kali dibuka,
// session langsung dianggap terautentikasi (bandingkan dengan lab CSRF di
// kategori lain yang punya login.php terpisah; di sini fokusnya murni ke
// header CORS, jadi login-nya disederhanakan).
ensure_logged_in();

if (isset($_GET['action']) && $_GET['action'] === 'api') {
    // VULNERABLE: origin request di-reflect apa adanya ke
    // Access-Control-Allow-Origin, dikombinasikan dengan
    // Access-Control-Allow-Credentials: true. Ini artinya browser di
    // origin MANA PUN boleh membaca response ini selama request dikirim
    // dengan credentials (cookie session ikut terkirim otomatis).
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Content-Type: application/json');

    if (empty($_SESSION['logged_in'])) {
        http_response_code(401);
        echo json_encode(['error' => 'not logged in']);
        exit;
    }

    echo json_encode([
        'email' => $_SESSION['user_email'],
        'api_key' => $_SESSION['api_key'],
        'note' => 'Data pribadi user yang sedang login - seharusnya hanya boleh dibaca oleh origin aplikasi resmi.',
    ]);
    exit;
}

$db = load_db();
include 'header.php';
?>

<p>Kamu sekarang "login" (session aktif) sebagai <code><?php echo htmlspecialchars($_SESSION['user_email']); ?></code>.
Endpoint API di lab ini (<code>?action=api</code>) mengembalikan data pribadi milik user yang
sedang login, dan dilindungi header CORS — tapi header itu dikonfigurasi secara salah:
alih-alih membatasi origin yang boleh mengakses (mis. hanya domain aplikasi resmi), server
me-reflect <strong>apa pun</strong> nilai header <code>Origin</code> yang dikirim request kembali
ke <code>Access-Control-Allow-Origin</code>, sambil tetap mengizinkan
<code>Access-Control-Allow-Credentials: true</code>.</p>

<h3>1. Buktikan lewat curl (lihat header response)</h3>
<pre class="result-box">curl -i -H "Origin: https://evil.example" http://localhost:8079/secmisconfig/lab5_cors_misconfig.php?action=api</pre>
<p class="hint">Perhatikan response header-nya: server membalas
<code>Access-Control-Allow-Origin: https://evil.example</code> dan
<code>Access-Control-Allow-Credentials: true</code> — origin sembarangan (bahkan yang jelas-jelas
bukan domain aplikasi ini) diizinkan penuh. Ini adalah bukti utama misconfiguration-nya, dan
tidak butuh browser sama sekali untuk membuktikannya.</p>

<h3>2. Coba dari console browser (origin lain)</h3>
<p>Buka tab baru ke domain/origin yang berbeda (mis. <code>http://127.0.0.1:8079</code> alih-alih
<code>http://localhost:8079</code>, atau domain lain mana pun), buka DevTools Console, lalu
jalankan:</p>
<pre class="result-box">fetch('http://localhost:8079/secmisconfig/lab5_cors_misconfig.php?action=api', {credentials: 'include'})
  .then(r => r.json())
  .then(console.log)</pre>
<p class="hint">Karena browser tab tersebut tetap membawa cookie session <code>localhost:8079</code>
(kalau kamu sudah pernah membuka lab ini di origin yang sama sebelumnya) dan server meng-reflect
origin apa pun dengan credentials diizinkan, response JSON berisi <code>email</code> dan
<code>api_key</code> milik user yang sedang login akan berhasil terbaca oleh JavaScript yang
berjalan di origin lain — persis skenario situs attacker yang mencuri data user lewat request
lintas-origin diam-diam.</p>

<details class="hint-box">
<summary>Lihat kode vulnerable</summary>
<p class="hint"><code>$origin = $_SERVER['HTTP_ORIGIN']; header('Access-Control-Allow-Origin: ' . $origin); header('Access-Control-Allow-Credentials: true');</code>
— server tidak pernah mengecek origin terhadap allowlist, hanya mengembalikan apa pun yang
dikirim client.</p>
</details>

<?php include 'footer.php'; ?>
