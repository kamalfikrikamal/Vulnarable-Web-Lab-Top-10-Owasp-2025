<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Insecure Randomness Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Insecure Randomness Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_predictable_reset_token.php">Lab 1: Reset Token</a>
  <a href="lab2_sequential_api_key.php">Lab 2: Sequential API Key</a>
  <a href="lab3_predictable_otp.php">Lab 3: Predictable OTP</a>
  <a href="lab4_predictable_coupon.php">Lab 4: Predictable Coupon</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
