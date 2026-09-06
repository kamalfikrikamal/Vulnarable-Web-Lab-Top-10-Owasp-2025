<?php
require 'db.php';
$title = 'Lab 4: Blind Boolean-Based SQLi';
$id = $_GET['id'] ?? '1';

// VULNERABLE: numeric parameter concatenated directly. No data or errors are ever
// shown back to the user - only whether a matching, in-stock product exists.
$query = "SELECT id FROM products WHERE id = $id AND price > 0";
$res = @$mysqli->query($query);

$found = ($res !== false && $res->num_rows > 0);

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: this "stock checker" only ever renders one of two messages
(<em>In stock</em> / <em>Not found</em>) &mdash; there are no visible errors and no data is
returned directly. Use this true/false oracle with boolean conditions
(<code>AND 1=1</code>, <code>AND SUBSTRING((SELECT password FROM users LIMIT 1),1,1)='a'</code>,
etc.) to extract data one character at a time.</p>
</details>

<form method="get">
  <label>Product ID</label><br>
  <input type="text" name="id" value="<?php echo htmlspecialchars($id); ?>">
  <button type="submit">Check Stock</button>
</form>

<?php if ($found): ?>
  <div class="result-box">Product is IN STOCK.</div>
<?php else: ?>
  <div class="error-box">Product NOT FOUND or out of stock.</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
