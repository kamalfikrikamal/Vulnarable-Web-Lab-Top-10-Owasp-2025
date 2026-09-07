<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'IDOR Lab';
$db = load_db();
$me = current_user($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - IDOR Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_basic_idor.php">Lab 1: Basic IDOR</a>
  <a href="lab2_idor_write.php">Lab 2: IDOR Write</a>
  <a href="lab3_idor_unpredictable.php">Lab 3: Unpredictable ID</a>
  <a href="lab4_idor_api.php">Lab 4: IDOR via API</a>
  <a href="lab5_mass_assignment.php">Lab 5: Mass Assignment</a>
  <span class="session-info">
    <?php if ($me): ?>
      Login sebagai <strong><?php echo htmlspecialchars($me['username']); ?></strong> (id=<?php echo (int)$me['id']; ?>) &middot; <a href="login.php?logout=1" style="color:#f87171;">Logout</a>
    <?php else: ?>
      <a href="login.php" style="color:#93c5fd;">Login</a>
    <?php endif; ?>
  </span>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
