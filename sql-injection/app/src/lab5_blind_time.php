<?php
require 'db.php';
$title = 'Lab 5: Blind Time-Based SQLi';
$id = $_GET['id'] ?? '1';

// VULNERABLE: numeric parameter concatenated directly. The response is IDENTICAL
// no matter what the query returns - the only observable signal is timing.
$query = "SELECT id FROM products WHERE id = $id";
$start = microtime(true);
@$mysqli->query($query);
$elapsed = round(microtime(true) - $start, 2);

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: this "activity logger" endpoint always shows the exact same generic
message, regardless of the query outcome, so a boolean oracle is not available. Use
<code>SLEEP()</code> inside the injected condition (e.g.
<code>1 AND IF(SUBSTRING((SELECT password FROM users WHERE username='admin'),1,1)='S',SLEEP(3),0)</code>)
and measure the response time to infer data one bit/character at a time.</p>
</details>

<form method="get">
  <label>Product ID</label><br>
  <input type="text" name="id" value="<?php echo htmlspecialchars($id); ?>">
  <button type="submit">Log Activity</button>
</form>

<div class="result-box">Request processed. (server time: <?php echo $elapsed; ?>s)</div>

<?php include 'footer.php'; ?>
