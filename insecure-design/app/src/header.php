<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Insecure Design Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Insecure Design Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_price_tampering.php">Lab 1: Price Tampering</a>
  <a href="lab2_negative_quantity.php">Lab 2: Negative Quantity</a>
  <a href="lab3_coupon_stacking.php">Lab 3: Coupon Stacking</a>
  <a href="lab4_skip_checkout_step.php">Lab 4: Skip Checkout Step</a>
  <a href="lab5_unlimited_referral_abuse.php">Lab 5: Referral Abuse</a>
  <a href="lab6_2fa_forced_browsing.php">Lab 6: 2FA Forced Browsing</a>
  <a href="lab7_password_change_no_reauth.php">Lab 7: Password Change No Re-auth</a>
  <a href="lab8_trusted_device_bypass.php">Lab 8: Trusted Device Bypass</a>
  <a href="lab9_coupon_parameter_pollution.php">Lab 9: Coupon Parameter Pollution</a>
  <a href="lab10_region_price_spoofing.php">Lab 10: Region Price Spoofing</a>
  <a href="lab11_over_refund.php">Lab 11: Over-Refund</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
