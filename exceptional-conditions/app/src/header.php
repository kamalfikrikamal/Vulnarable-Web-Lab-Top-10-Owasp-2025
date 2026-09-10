<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Exceptional Conditions Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Exceptional Conditions Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_fail_open_payment_timeout.php">Lab 1: Payment Timeout</a>
  <a href="lab2_error_message_info_leak.php">Lab 2: Error Message Leak</a>
  <a href="lab3_race_condition_giftcard.php">Lab 3: Race Condition</a>
  <a href="lab4_failopen_catch_block.php">Lab 4: Fail-Open Catch</a>
  <a href="lab5_duplicate_charge_retry.php">Lab 5: Duplicate Charge Retry</a>
  <a href="lab6_no_rollback_partial_failure.php">Lab 6: No Rollback</a>
  <a href="lab7_type_confusion_filter_bypass.php">Lab 7: Type Confusion Filter</a>
  <a href="lab8_malformed_response_fail_open.php">Lab 8: Malformed Response</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
