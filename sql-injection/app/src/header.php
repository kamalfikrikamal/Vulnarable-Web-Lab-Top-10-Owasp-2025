<?php $title = $title ?? 'VulnShop'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - VulnShop SQLi Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_login_bypass.php">Lab 1: Login Bypass</a>
  <a href="lab2_union.php">Lab 2: UNION</a>
  <a href="lab3_error_based.php">Lab 3: Error-Based</a>
  <a href="lab4_blind_boolean.php">Lab 4: Blind Boolean</a>
  <a href="lab5_blind_time.php">Lab 5: Blind Time</a>
  <a href="lab6_second_order.php">Lab 6: Second-Order</a>
  <a href="lab7_order_by.php">Lab 7: ORDER BY</a>
  <a href="lab8_stacked_queries.php">Lab 8: Stacked Queries</a>
  <a href="lab9_filter_bypass.php">Lab 9: Filter Bypass</a>
  <a href="lab10_insert_based.php">Lab 10: INSERT-based</a>
  <a href="lab11_cookie_based.php">Lab 11: Cookie-based</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
