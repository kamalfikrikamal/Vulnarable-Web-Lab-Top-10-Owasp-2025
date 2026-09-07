<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Broken Function-Level Access Control Lab';
$db = load_db();
$me = current_user($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - BFLA Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_unprotected_admin.php">Lab 1: Unprotected Admin</a>
  <a href="lab2_hidden_url.php">Lab 2: Hidden URL</a>
  <a href="lab3_role_cookie.php">Lab 3: Role via Cookie</a>
  <a href="lab4_method_bypass.php">Lab 4: Method Bypass</a>
  <a href="lab5_referer_bypass.php">Lab 5: Referer Bypass</a>
  <span class="session-info">
    <?php if ($me): ?>
      Login sebagai <strong><?php echo htmlspecialchars($me['username']); ?></strong> (role: <?php echo htmlspecialchars($me['role']); ?>) &middot; <a href="login.php?logout=1" style="color:#f87171;">Logout</a>
    <?php else: ?>
      <a href="login.php" style="color:#93c5fd;">Login</a>
    <?php endif; ?>
  </span>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
