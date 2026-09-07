<?php
require_once __DIR__ . '/lib.php';
if (isset($_GET['switch_user'])) { $_SESSION['username'] = $_GET['switch_user'] === 'bob' ? 'bob' : 'alice'; }
$title = $title ?? 'Sensitive Data Exposure Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Sensitive Data Exposure Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_url_leak.php">Lab 1: Data Sensitif di URL</a>
  <a href="lab2_missing_cache_control.php">Lab 2: Missing Cache-Control</a>
  <a href="lab3_unmasked_response.php">Lab 3: Data Tidak Di-mask</a>
  <span class="session-info">
    Login sebagai <strong><?php echo htmlspecialchars(current_username()); ?></strong> &middot;
    <a href="?switch_user=<?php echo current_username() === 'alice' ? 'bob' : 'alice'; ?>" style="color:#93c5fd;">Ganti ke <?php echo current_username() === 'alice' ? 'bob' : 'alice'; ?></a>
  </span>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
