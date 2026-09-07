<?php
$title = $title ?? 'LFI Lab';
// Simulasi "request log" milik aplikasi sendiri (bukan access log Apache) supaya lab log
// poisoning (Lab 7) deterministik di semua environment, tidak tergantung lokasi/format log
// web server yang berbeda-beda per distro/konfigurasi.
$log_file = __DIR__ . '/../app_data/access.log';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '-';
$entry = '[' . date('Y-m-d H:i:s') . '] ' . ($_SERVER['REMOTE_ADDR'] ?? '-') . ' "' . $ua . '"' . PHP_EOL;
@file_put_contents($log_file, $entry, FILE_APPEND);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - LFI Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_basic.php">Lab 1: Basic LFI</a>
  <a href="lab2_absolute_bypass.php">Lab 2: Absolute Path</a>
  <a href="lab3_nonrecursive_strip.php">Lab 3: Non-recursive Strip</a>
  <a href="lab4_double_decode.php">Lab 4: Double Decode</a>
  <a href="lab5_start_validation.php">Lab 5: Start Validation</a>
  <a href="lab6_extension_nullbyte.php">Lab 6: Null Byte</a>
  <a href="lab7_wrappers_rce.php">Lab 7: Wrappers &amp; RCE</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
