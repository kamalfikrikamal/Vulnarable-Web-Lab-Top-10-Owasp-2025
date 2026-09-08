<?php
require 'db.php';
$title = 'Lab 11: SQLi via Cookie';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tracking_id'])) {
    setcookie('TrackingId', $_POST['tracking_id'], time() + 3600, '/');
    header('Location: lab11_cookie_based.php');
    exit;
}

if (!isset($_COOKIE['TrackingId'])) {
    setcookie('TrackingId', '1', time() + 3600, '/');
    $_COOKIE['TrackingId'] = '1'; // langsung tersedia juga untuk request saat ini
}
$tracking_id = $_COOKIE['TrackingId'];

// VULNERABLE: nilai cookie disambung langsung ke SQL, sama seperti Lab 2 - bedanya titik
// injeksinya bukan parameter URL/form yang kelihatan, tapi cookie yang di-set otomatis oleh
// server dan sepenuhnya bisa diubah attacker lewat DevTools/Burp.
$query = "SELECT id, name, description, price FROM products WHERE id = '$tracking_id' LIMIT 1";
$res = $mysqli->query($query);

$result_html = '';
if ($res === false) {
    $result_html = "<div class='error-box'>SQL Error: " . htmlspecialchars($mysqli->error) . "</div>";
} elseif ($res->num_rows > 0) {
    $result_html = "<table><tr><th>ID</th><th>Name</th><th>Description</th><th>Price</th></tr>";
    while ($row = $res->fetch_assoc()) {
        $result_html .= "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['name']) . "</td><td>" . htmlspecialchars($row['description']) . "</td><td>" . htmlspecialchars($row['price']) . "</td></tr>";
    }
    $result_html .= "</table>";
} else {
    $result_html = "<div class='error-box'>Produk tidak ditemukan.</div>";
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman ini menampilkan "produk yang baru saja Anda lihat" berdasarkan
cookie <code>TrackingId</code> yang di-set otomatis saat pertama kali membuka halaman ini -
tidak ada form/parameter URL yang terlihat untuk field ini. Buka DevTools &rarr; Application/
Storage &rarr; Cookies (atau intercept dengan Burp), ubah nilai cookie <code>TrackingId</code>
secara manual, lalu reload halaman. Query-nya sama persis strukturnya dengan Lab 2 (UNION-based),
jadi payload UNION yang sama seharusnya berfungsi di sini juga. Sebagai kemudahan tanpa
DevTools, form di bawah ini juga bisa dipakai untuk mengubah nilai cookie tersebut.</p>
</details>

<p>Nilai cookie <code>TrackingId</code> saat ini: <code><?php echo htmlspecialchars($tracking_id); ?></code></p>

<form method="post">
  <label>Set nilai cookie TrackingId (alternatif tanpa DevTools)</label><br>
  <input type="text" name="tracking_id" value="" style="width:420px;">
  <button type="submit">Set Cookie &amp; Reload</button>
</form>

<h3>Produk yang baru saja Anda lihat:</h3>
<?php echo $result_html; ?>

<?php include 'footer.php'; ?>
