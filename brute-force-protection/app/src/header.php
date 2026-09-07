<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Broken Brute-Force Protection Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Brute-Force Protection Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_no_rate_limit.php">Lab 1: No Rate Limiting</a>
  <a href="lab2_xff_bypass.php">Lab 2: IP Lockout Bypass (X-Forwarded-For)</a>
  <a href="lab3_case_variation_bypass.php">Lab 3: Lockout Bypass (Case Variation)</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
