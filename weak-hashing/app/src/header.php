<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Weak Password Hashing Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Weak Hashing Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_plaintext.php">Lab 1: Plaintext Storage</a>
  <a href="lab2_unsalted_md5.php">Lab 2: Unsalted MD5</a>
  <a href="lab3_reversible_encoding.php">Lab 3: Reversible "Encryption"</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
