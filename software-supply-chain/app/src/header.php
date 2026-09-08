<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Software Supply Chain Failures Lab';
$db = load_db();
ensure_update_files();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Software Supply Chain Failures Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_prototype_pollution.php">Lab 1: Prototype Pollution</a>
  <a href="lab2_dependency_confusion.php">Lab 2: Dependency Confusion</a>
  <a href="lab3_cicd_secret_exposure.php">Lab 3: CI/CD Secret Exposure</a>
  <a href="lab4_unsigned_autoupdate.php">Lab 4: Unsigned Auto-Update</a>
  <a href="lab5_malicious_postinstall.php">Lab 5: Malicious Postinstall</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
