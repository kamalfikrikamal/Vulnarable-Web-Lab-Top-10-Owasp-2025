<?php
require 'db.php';
$title = 'Lab 1: Login Bypass';
$result_html = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    // VULNERABLE: raw string concatenation into SQL, no prepared statement.
    $query = "SELECT * FROM users WHERE username = '$user' AND password = '$pass'";

    $res = $mysqli->query($query);
    if ($res === false) {
        $result_html = "<div class='error-box'>SQL Error: " . htmlspecialchars($mysqli->error) . "\n\nQuery: " . htmlspecialchars($query) . "</div>";
    } elseif ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $result_html = "<div class='result-box'>Login successful!\nWelcome, " . htmlspecialchars($row['username']) . " (role: " . htmlspecialchars($row['role']) . ")\n\nQuery executed:\n" . htmlspecialchars($query) . "</div>";
    } else {
        $result_html = "<div class='error-box'>Invalid credentials.\n\nQuery executed:\n" . htmlspecialchars($query) . "</div>";
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: log in as <strong>admin</strong> without knowing the password. The query is
shown after each attempt so you can see exactly what was executed.</p>
</details>

<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"><br>
  <label>Password</label><br>
  <input type="password" name="password" value=""><br>
  <button type="submit">Login</button>
</form>

<?php echo $result_html; ?>

<?php include 'footer.php'; ?>
