<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Password Reset Flaws Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Password Reset Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_token_leak_email.php">Lab 1: Token Leak via Email Pixel</a>
  <a href="lab2_token_reuse.php">Lab 2: Token Reuse</a>
  <a href="lab3_brute_forceable_code.php">Lab 3: Brute-forceable Code</a>
  <a href="lab4_host_header_poisoning.php">Lab 4: Host Header Poisoning</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
