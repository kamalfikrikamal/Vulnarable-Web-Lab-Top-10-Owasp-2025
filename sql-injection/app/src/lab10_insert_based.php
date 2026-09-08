<?php
require 'db.php';
$title = 'Lab 10: SQLi via INSERT (Registrasi)';
$result_html = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $bio = $_POST['bio'] ?? '';

    // VULNERABLE: kedua field disambung langsung ke statement INSERT, tanpa prepared
    // statement. Error mentah dari MySQL ditampilkan ke user, sama seperti Lab 3.
    $query = "INSERT INTO reg_demo_users (username, bio) VALUES ('$username', '$bio')";
    $res = $mysqli->query($query);

    if ($res === false) {
        $result_html = "<div class='error-box'>SQL Error: " . htmlspecialchars($mysqli->error) . "\n\nQuery: " . htmlspecialchars($query) . "</div>";
    } else {
        $result_html = "<div class='result-box'>Akun '" . htmlspecialchars($username) . "' berhasil didaftarkan.\n\nQuery dieksekusi:\n" . htmlspecialchars($query) . "</div>";
    }
}

$accounts_html = '';
$res = $mysqli->query("SELECT id, username, bio FROM reg_demo_users ORDER BY id DESC");
if ($res) {
    $accounts_html = "<table><tr><th>ID</th><th>Username</th><th>Bio</th></tr>";
    while ($row = $res->fetch_assoc()) {
        $accounts_html .= "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['username']) . "</td><td>" . htmlspecialchars($row['bio']) . "</td></tr>";
    }
    $accounts_html .= "</table>";
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: injeksi tidak hanya terjadi di klausa <code>SELECT</code>/<code>WHERE</code>
- di mana pun input user disambung ke SQL adalah titik injeksi potensial, termasuk statement
<code>INSERT</code>. Form registrasi ini menerima <code>username</code> dan <code>bio</code>,
lalu memasukkannya ke <code>INSERT INTO reg_demo_users (username, bio) VALUES (...)</code>.
Karena tabel ini cuma punya 2 kolom, Anda tidak bisa menambah kolom baru dengan koma - tapi
Anda bisa mengubah nilai kolom <code>bio</code> menjadi sebuah ekspresi (bukan cuma string
statis) memakai operator <code>OR</code>, lalu menyisipkan fungsi pemicu error seperti
<code>extractvalue()</code> di dalamnya untuk membocorkan data dari tabel <code>users</code>
lewat pesan error.</p>
</details>

<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? 'tester'); ?>"><br>
  <label>Bio</label><br>
  <input type="text" name="bio" value="" style="width:420px;"><br>
  <button type="submit">Daftar</button>
</form>

<?php echo $result_html; ?>

<h3>Akun yang sudah terdaftar</h3>
<?php echo $accounts_html; ?>

<?php include 'footer.php'; ?>
