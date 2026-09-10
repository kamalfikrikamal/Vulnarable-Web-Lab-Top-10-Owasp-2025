<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Logging & Alerting Failures Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Logging & Alerting Failures Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_log_injection_forgery.php">Lab 1: Log Injection / Forgery</a>
  <a href="lab2_log_injection_stored_xss.php">Lab 2: Stored XSS via Log</a>
  <a href="lab3_no_alerting_bruteforce.php">Lab 3: No Alerting</a>
  <a href="lab4_sensitive_data_in_logs.php">Lab 4: Data Sensitif di Log</a>
  <a href="lab5_threshold_evasion.php">Lab 5: Threshold Bisa Dihindari</a>
  <a href="lab6_log_tampering.php">Lab 6: Log Bisa Dihapus User</a>
  <a href="lab7_client_side_console_logging.php">Lab 7: Bocor di Console Browser</a>
  <a href="lab8_insufficient_log_context.php">Lab 8: Log Tanpa Konteks</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
