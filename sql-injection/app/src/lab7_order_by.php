<?php
require 'db.php';
$title = 'Lab 7: SQLi in ORDER BY';
$sort = $_GET['sort'] ?? 'id';

// VULNERABLE: ORDER BY cannot take a bound placeholder for column/expression names,
// so this parameter is concatenated directly - a common real-world trap.
$query = "SELECT id, name, price FROM products ORDER BY $sort";
$res = @$mysqli->query($query);

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: prepared statements cannot parameterise the <code>ORDER BY</code>
expression itself, so developers often concatenate it "because it's just a column name".
You cannot use UNION here. Instead, use conditional expressions to build a boolean oracle,
e.g. <code>sort=(CASE WHEN (1=1) THEN name ELSE price END)</code> vs
<code>(CASE WHEN (1=2) THEN name ELSE price END)</code> and observe how the row order changes,
or trigger a visible error with an invalid expression to confirm injection.</p>
</details>

<form method="get">
  <label>Sort expression</label><br>
  <input type="text" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
  <button type="submit">Sort</button>
</form>

<?php if ($res === false): ?>
  <div class="error-box">SQL Error: <?php echo htmlspecialchars($mysqli->error); ?></div>
<?php else: ?>
  <table><tr><th>ID</th><th>Name</th><th>Price</th></tr>
  <?php while ($row = $res->fetch_assoc()): ?>
    <tr><td><?php echo htmlspecialchars($row['id']); ?></td><td><?php echo htmlspecialchars($row['name']); ?></td><td><?php echo htmlspecialchars($row['price']); ?></td></tr>
  <?php endwhile; ?>
  </table>
<?php endif; ?>

<?php include 'footer.php'; ?>
