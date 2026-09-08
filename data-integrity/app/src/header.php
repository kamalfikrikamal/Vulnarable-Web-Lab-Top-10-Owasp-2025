<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Data Integrity Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Data Integrity Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_php_object_injection.php">Lab 1: Object Injection</a>
  <a href="lab2_unsigned_state_cookie.php">Lab 2: Unsigned State Cookie</a>
  <a href="lab3_timing_unsafe_hmac.php">Lab 3: Timing-Unsafe HMAC</a>
  <a href="lab4_update_no_checksum.php">Lab 4: Update Tanpa Checksum</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
