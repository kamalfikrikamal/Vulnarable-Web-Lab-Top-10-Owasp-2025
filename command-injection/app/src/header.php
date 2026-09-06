<?php $title = $title ?? 'Command Injection Lab'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Command Injection Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_visible.php">Lab 1: Visible Output</a>
  <a href="lab2_blind.php">Lab 2: Blind (Time-based)</a>
  <a href="lab3_filter_bypass.php">Lab 3: Filter Bypass</a>
  <a href="lab4_argument_injection.php">Lab 4: Argument Injection</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
