<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'CSRF Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - CSRF Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_no_token.php">Lab 1: No Token</a>
  <a href="lab2_token_not_tied.php">Lab 2: Token Not Tied to Session</a>
  <a href="lab3_token_removal.php">Lab 3: Token Removal Bypass</a>
  <a href="lab4_get_based.php">Lab 4: GET-based Action</a>
  <span class="session-info">
    <?php if (is_logged_in()): ?>
      Login sebagai <strong>victim</strong> (email: <?php echo htmlspecialchars($db['user']['email']); ?>) &middot; <a href="login.php?logout=1" style="color:#f87171;">Logout</a>
    <?php else: ?>
      <a href="login.php" style="color:#93c5fd;">Login</a>
    <?php endif; ?>
  </span>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
