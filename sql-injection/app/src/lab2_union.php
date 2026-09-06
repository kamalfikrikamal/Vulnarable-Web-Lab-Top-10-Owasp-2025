<?php
require 'db.php';
$title = 'Lab 2: UNION-based SQLi';
$result_html = '';
$id = $_GET['id'] ?? '1';

// VULNERABLE: numeric parameter concatenated directly, no quotes, no cast to int.
$query = "SELECT id, name, description, price FROM products WHERE id = $id";
$res = $mysqli->query($query);

if ($res === false) {
    $result_html = "<div class='error-box'>SQL Error: " . htmlspecialchars($mysqli->error) . "</div>";
} else {
    $result_html = "<table><tr><th>ID</th><th>Name</th><th>Description</th><th>Price</th></tr>";
    while ($row = $res->fetch_assoc()) {
        $result_html .= "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['name']) . "</td><td>" . htmlspecialchars($row['description']) . "</td><td>" . htmlspecialchars($row['price']) . "</td></tr>";
    }
    $result_html .= "</table>";
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: this page lists a single product by numeric <code>id</code>. The products
table has 4 columns. Try to find out how many columns the underlying query has, then use a
UNION SELECT to pull data out of the <code>users</code> table instead.</p>
</details>

<form method="get">
  <label>Product ID</label><br>
  <input type="text" name="id" value="<?php echo htmlspecialchars($id); ?>">
  <button type="submit">View Product</button>
</form>

<p>Query executed:</p>
<div class="result-box"><?php echo htmlspecialchars($query); ?></div>

<?php echo $result_html; ?>

<?php include 'footer.php'; ?>
