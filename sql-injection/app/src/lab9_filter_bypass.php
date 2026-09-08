<?php
require 'db.php';
$title = 'Lab 9: Filter/WAF Bypass';
$result_html = '';
$category = $_GET['category'] ?? 'electronics';
$blocked = false;

// NAIVE BLACKLIST: hanya menolak frasa literal "union" diikuti whitespace lalu "select"
// (case-insensitive). Filter ini tidak paham SQL sama sekali - ia cuma cocok-cocokkan teks.
if (preg_match('/union\s+select/i', $category)) {
    $blocked = true;
}

if ($blocked) {
    $result_html = "<div class='error-box'>Payload berbahaya terdeteksi!</div>";
} else {
    // VULNERABLE: persis seperti Lab 3 - string concatenation langsung ke query. Filter di atas
    // adalah satu-satunya "proteksi" tambahan, dan itu cuma blacklist berbasis pattern-match.
    $query = "SELECT id, name, price FROM products WHERE category = '$category'";
    $res = $mysqli->query($query);

    if ($res === false) {
        $result_html = "<div class='error-box'>MySQL Error: " . htmlspecialchars($mysqli->error) . "</div>";
    } else {
        $result_html = "<table><tr><th>ID</th><th>Name</th><th>Price</th></tr>";
        while ($row = $res->fetch_assoc()) {
            $result_html .= "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['name']) . "</td><td>" . htmlspecialchars($row['price']) . "</td></tr>";
        }
        $result_html .= "</table>";
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: sama seperti Lab 3 (UNION lewat parameter <code>category</code>), tapi
sekarang ada filter yang memblokir kalau input mengandung frasa <code>union select</code>
(spasi/tab/baris baru di antaranya tetap kena blokir). Filter ini hanya mengenali whitespace
sebagai pemisah kata - ia tidak tahu bahwa MySQL juga menerima komentar inline
(<code>/**/</code>) sebagai pemisah token yang sah antar keyword. Coba ganti spasi antara
<code>UNION</code> dan <code>SELECT</code> dengan <code>/**/</code>.</p>
</details>

<form method="get">
  <label>Category</label><br>
  <input type="text" name="category" value="<?php echo htmlspecialchars($category); ?>" style="width:420px;">
  <button type="submit">Filter</button>
</form>

<?php echo $result_html; ?>

<?php include 'footer.php'; ?>
