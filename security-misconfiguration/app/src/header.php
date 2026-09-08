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
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
