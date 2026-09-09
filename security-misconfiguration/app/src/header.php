<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Security Misconfiguration Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Security Misconfiguration Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_debug_stacktrace.php">Lab 1: Debug Stacktrace</a>
  <a href="lab2_default_credentials.php">Lab 2: Default Credentials</a>
  <a href="lab3_directory_listing.php">Lab 3: Directory Listing</a>
  <a href="lab5_cors_misconfig.php">Lab 5: CORS Misconfig</a>
  <a href="lab6_missing_headers_clickjacking.php">Lab 6: Clickjacking</a>
  <a href="lab7_git_exposed.php">Lab 7: .git Exposed</a>
  <a href="lab8_env_exposed.php">Lab 8: .env Exposed</a>
  <a href="lab9_backup_file_guess.php">Lab 9: Backup File</a>
  <a href="lab10_missing_httponly.php">Lab 10: No HttpOnly</a>
  <a href="lab11_missing_secure.php">Lab 11: No Secure</a>
  <a href="lab12_missing_samesite.php">Lab 12: No SameSite</a>
  <a href="lab13_trace_method.php">Lab 13: TRACE Method</a>
  <a href="lab14_put_method.php">Lab 14: PUT Method</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
