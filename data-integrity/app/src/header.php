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
  <a href="lab5_magic_hash_bypass.php">Lab 5: Magic Hash Bypass</a>
  <a href="lab6_leaked_signing_secret.php">Lab 6: Leaked Signing Secret</a>
  <a href="lab7_partial_signature_gap.php">Lab 7: Partial Signature Gap</a>
  <a href="lab8_checksum_same_source.php">Lab 8: Checksum Same Source</a>
  <a href="lab9_extract_variable_injection.php">Lab 9: extract() Injection</a>
  <a href="lab10_untrusted_dynamic_dispatch.php">Lab 10: Untrusted Dynamic Dispatch</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
