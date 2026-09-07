<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'JWT Vulnerabilities Lab';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - JWT Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_alg_none.php">Lab 1: alg=none</a>
  <a href="lab2_weak_secret.php">Lab 2: Weak Secret</a>
  <a href="lab3_no_signature_check.php">Lab 3: No Signature Check</a>
  <a href="lab4_kid_path_traversal.php">Lab 4: kid Path Traversal</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
