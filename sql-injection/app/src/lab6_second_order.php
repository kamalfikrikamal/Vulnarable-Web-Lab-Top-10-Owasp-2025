<?php
require 'db.php';
$title = 'Lab 6: Second-Order SQLi';
$action = $_GET['action'] ?? 'register';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
    $username = $_POST['username'] ?? '';
    $bio = $_POST['bio'] ?? '';

    // Step 1 (SAFE): the registration itself uses a prepared statement, so this
    // insert alone does not look "injectable" during a quick code review.
    $stmt = $mysqli->prepare("INSERT INTO profiles (username, bio) VALUES (?, ?)");
    $stmt->bind_param('ss', $username, $bio);
    $stmt->execute();
    setcookie('profile_username', $username, time() + 3600, '/');
    $message = "Profile stored for '" . htmlspecialchars($username) . "'. Now click 'View My Bio' to trigger the second query.";
}

$view_html = '';
if ($action === 'view') {
    $stored_username = $_COOKIE['profile_username'] ?? '';

    // Step 2 (VULNERABLE): later, a different feature re-reads the *already stored*
    // username and concatenates it into a new query without re-sanitising it.
    $query = "SELECT bio FROM profiles WHERE username = '$stored_username' ORDER BY id DESC LIMIT 1";
    $res = $mysqli->query($query);

    if ($res === false) {
        $view_html = "<div class='error-box'>SQL Error: " . htmlspecialchars($mysqli->error) . "\n\nQuery: " . htmlspecialchars($query) . "</div>";
    } elseif ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $view_html = "<div class='result-box'>Bio: " . htmlspecialchars($row['bio']) . "\n\nQuery executed:\n" . htmlspecialchars($query) . "</div>";
    } else {
        $view_html = "<div class='error-box'>No profile found.\n\nQuery executed:\n" . htmlspecialchars($query) . "</div>";
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: registration uses a <em>parameterised</em> query, so the username is
stored safely. But the "View My Bio" feature later rebuilds a <strong>new</strong> query using
that stored username, unsafely. Register with a username that breaks out of the quotes and
UNION-selects data from the <code>users</code> table, then view your bio to trigger it.
Example username: <code>x' UNION SELECT password FROM users WHERE username='admin'-- -</code></p>
</details>

<h3>Step 1: Register</h3>
<form method="post" action="?action=register">
  <label>Username</label><br>
  <input type="text" name="username" value=""><br>
  <label>Bio</label><br>
  <input type="text" name="bio" value="hello world"><br>
  <button type="submit">Register</button>
</form>
<?php if ($message): ?><div class="result-box"><?php echo $message; ?></div><?php endif; ?>

<h3>Step 2: Trigger</h3>
<a class="btn" href="?action=view">View My Bio</a>
<?php echo $view_html; ?>

<?php include 'footer.php'; ?>
