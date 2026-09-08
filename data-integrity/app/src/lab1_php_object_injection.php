<?php
$title = 'Lab 1: PHP Object Injection';
require_once __DIR__ . '/lib.php';

class RememberMeToken {
    public $username = 'guest';
    public $role = 'user';
    public function __wakeup() {
        // simulates "trusting" the deserialized object's role for access decisions
    }
}

// Seed a default "remember me" cookie on first visit (role=user).
if (!isset($_COOKIE['remember_token'])) {
    $default_serialized = serialize(new RememberMeToken());
    setcookie('remember_token', $default_serialized, time() + 3600, '/');
    $_COOKIE['remember_token'] = $default_serialized;
}

$raw_cookie = $_COOKIE['remember_token'];

// VULNERABLE: unserialize() is called directly on attacker-controlled cookie
// data, with no allowed_classes restriction (`unserialize($raw_cookie, ['allowed_classes' => false])`
// would have blocked this). PHP will happily instantiate ANY class known to this
// request and populate its properties with whatever the attacker put in the
// payload string, and will invoke magic methods like __wakeup() along the way.
$obj = @unserialize($raw_cookie);

$is_admin = is_object($obj) && isset($obj->role) && $obj->role === 'admin';
$username = (is_object($obj) && isset($obj->username)) ? $obj->username : 'guest';

// Server-side helper so trainees can see exactly what a forged serialized
// object looks like, as if they had crafted it by hand.
$generated_payload = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_payload'])) {
    $forged = new RememberMeToken();
    $forged->username = 'guest';
    $forged->role = 'admin';
    $generated_payload = serialize($forged);
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: buka <strong>Admin Panel</strong> di bawah tanpa login sebagai admin.
Cookie <code>remember_token</code> menyimpan objek PHP yang sudah di-<code>serialize()</code>,
dan server mendeserialisasinya kembali dengan <code>unserialize()</code> lalu langsung
mempercayai properti <code>role</code>-nya. Kamu tidak perlu tahu password siapa pun &mdash;
cukup ganti isi cookie dengan versi yang propertinya sudah kamu ubah sendiri.</p>
<p class="hint">Klik tombol <strong>"Generate Payload Admin"</strong> di bawah untuk melihat
string <code>serialize()</code> yang sudah berisi <code>role=admin</code>. Salin string itu,
lalu di DevTools &rarr; Application &rarr; Cookies (atau lewat Burp), ganti nilai cookie
<code>remember_token</code> dengan string tersebut persis, dan reload halaman ini.</p>
</details>

<div class="hint">
<strong>Bantuan: cara membuat payload</strong><br>
Format <code>serialize()</code> PHP untuk objek adalah:<br>
<code>O:&lt;panjang nama class&gt;:"&lt;nama class&gt;":&lt;jumlah properti&gt;:{s:&lt;panjang nama properti&gt;:"&lt;nama properti&gt;";s:&lt;panjang nilai&gt;:"&lt;nilai&gt;";...}</code><br>
Contoh: untuk class <code>RememberMeToken</code> (15 karakter) dengan <code>username=guest</code>
dan <code>role=admin</code>, hasilnya persis seperti ini (perhatikan setiap angka panjang harus
sama persis dengan panjang string-nya, PHP akan menolak payload yang panjangnya salah hitung):
<pre>O:15:"RememberMeToken":2:{s:8:"username";s:5:"guest";s:4:"role";s:5:"admin";}</pre>
Kamu tidak perlu menghitung manual kalau malas &mdash; tombol di bawah melakukannya untukmu
(mensimulasikan attacker yang sudah tahu struktur class dari kode sumber yang bocor/open-source).
</div>

<form method="post">
  <button type="submit" name="generate_payload" value="1">Generate Payload Admin</button>
</form>
<?php if ($generated_payload): ?>
<div class="result-box"><?php echo htmlspecialchars($generated_payload); ?></div>
<p class="hint">Salin string di atas ke cookie <code>remember_token</code>, lalu reload halaman ini.</p>
<?php endif; ?>

<h3>Cookie <code>remember_token</code> saat ini (raw)</h3>
<div class="result-box"><?php echo htmlspecialchars($raw_cookie); ?></div>

<h3>Status login</h3>
<p>Username terbaca dari objek: <strong><?php echo htmlspecialchars($username); ?></strong>,
role: <span class="badge <?php echo $is_admin ? 'admin' : 'user'; ?>"><?php echo $is_admin ? 'admin' : 'user'; ?></span></p>

<?php if ($is_admin): ?>
<div class="result-box">
ADMIN PANEL UNLOCKED
----------------------
Daftar user: alice, admin, bob
API master key: SK-LIVE-9f8a7b6c5d4e3f2a1b0c
Server internal IP: 10.0.14.7
</div>
<?php else: ?>
<div class="error-box">Kamu login sebagai user biasa. Admin Panel tersembunyi.</div>
<?php endif; ?>

<h3>Kenapa ini berbahaya (di luar demo sederhana ini)</h3>
<p><code>unserialize()</code> pada data yang bisa dikendalikan attacker berbahaya bukan cuma
karena attacker bisa mengubah nilai properti seperti di lab ini. PHP juga otomatis memanggil
magic method seperti <code>__wakeup()</code> dan <code>__destruct()</code> begitu objeknya
terbentuk, dengan properti-properti yang NILAINYA sudah dikendalikan attacker. Kalau di
codebase nyata ada class lain (dependency, library, framework) yang magic method-nya melakukan
sesuatu seperti menulis file, menjalankan query, atau memanggil fungsi berdasarkan nilai
properti, attacker bisa merangkai beberapa class sekaligus (disebut <strong>POP chain</strong>
&mdash; Property-Oriented Programming chain) untuk berujung pada Remote Code Execution (RCE)
penuh, tanpa perlu ada bug lain di aplikasi selain satu <code>unserialize()</code> yang
mempercayai input eksternal ini.</p>

<?php include 'footer.php'; ?>
