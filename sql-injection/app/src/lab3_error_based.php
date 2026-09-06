<?php
require 'db.php';
$title = 'Lab 3: Error-Based SQLi';
$result_html = '';
$category = $_GET['category'] ?? 'electronics';

// VULNERABLE: string parameter concatenated inside quotes, errors are shown to the user.
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

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: this page filters products by <code>category</code> and displays raw MySQL
errors when the query is malformed. Use a function such as <code>extractvalue()</code> or
<code>updatexml()</code> with an intentionally invalid XPath to leak data (e.g. the database
version or a subquery result) through the error message.</p>
</details>

<form method="get">
  <label>Category</label><br>
  <input type="text" name="category" value="<?php echo htmlspecialchars($category); ?>">
  <button type="submit">Filter</button>
</form>

<?php echo $result_html; ?>

<?php include 'footer.php'; ?>
