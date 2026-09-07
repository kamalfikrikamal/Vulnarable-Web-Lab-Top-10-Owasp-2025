<?php
require_once __DIR__ . '/lib.php';
$db = load_db();
$me = current_user($db);

// This "API" endpoint is called by the profile page's JavaScript via fetch()
// to render the logged-in user's own profile widget.
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $id = $_GET['id'] ?? ($me['id'] ?? 0);
    // VULNERABLE: returns the FULL user record (including password hash and
    // credit card) for whatever id is requested, with no check that the
    // caller is allowed to see that user's record.
    $user = find_user_by_id($db, $id);
    echo $user ? json_encode($user) : json_encode(['error' => 'not found']);
    exit;
}

$title = 'Lab 4: IDOR via API endpoint';
include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: widget profil di halaman ini memanggil endpoint API
<code>lab4_idor_api.php?api=1&amp;id=&lt;id&gt;</code> lewat JavaScript <code>fetch()</code> untuk
mengambil data profilmu sendiri. Buka tab Network di DevTools, lihat request itu, lalu coba
panggil ulang endpoint yang sama dengan <code>id</code> milik user lain
(langsung lewat address bar juga bisa, endpoint ini bisa diakses langsung). Perhatikan field apa
saja yang dikembalikan &mdash; termasuk <code>password</code> dan <code>credit_card</code> milik
orang lain.</p>
<ul class="hint">
<li><code>lab4_idor_api.php?api=1&amp;id=1</code></li>
<li><code>lab4_idor_api.php?api=1&amp;id=4</code> (akun admin)</li>
</ul>
</details>

<div id="profile-widget">Memuat profil...</div>

<script>
// (escaping here is just so this demo page doesn't turn into an unrelated
// stored-XSS lab if you've already renamed your profile in Lab 5 - the
// point of THIS lab is the over-exposed API response, not DOM rendering.)
function esc(s) { const d = document.createElement('div'); d.innerText = String(s); return d.innerHTML; }
fetch('lab4_idor_api.php?api=1&id=<?php echo (int)$me['id']; ?>')
  .then(r => r.json())
  .then(data => {
    document.getElementById('profile-widget').innerHTML =
      '<table class="data-table">' +
      Object.keys(data).map(k => '<tr><th>' + esc(k) + '</th><td>' + esc(data[k]) + '</td></tr>').join('') +
      '</table>';
  });
</script>

<p>Coba panggil endpoint API secara manual dengan ID berbeda:</p>
<form method="get" action="lab4_idor_api.php">
  <input type="hidden" name="api" value="1">
  <label>User ID</label><br>
  <input type="text" name="id" value="<?php echo (int)$me['id']; ?>">
  <button type="submit">Panggil API (buka tab baru / lihat JSON mentah)</button>
</form>

<?php include 'footer.php'; ?>
