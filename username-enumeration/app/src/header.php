<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Username Enumeration Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Username Enumeration Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_different_message.php">Lab 1: Different Error Message</a>
  <a href="lab2_subtle_difference.php">Lab 2: Subtly Different Response</a>
  <a href="lab3_response_timing.php">Lab 3: Response Timing</a>
  <a href="lab4_account_lockout.php">Lab 4: Account Lockout</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
