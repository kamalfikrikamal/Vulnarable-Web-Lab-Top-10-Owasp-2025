<?php $title = $title ?? 'File Upload Lab'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - File Upload Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_unrestricted.php">Lab 1: Unrestricted</a>
  <a href="lab2_content_type.php">Lab 2: Content-Type</a>
  <a href="lab3_path_traversal.php">Lab 3: Path Traversal</a>
  <a href="lab4_blacklist_bypass.php">Lab 4: Blacklist</a>
  <a href="lab5_obfuscated_extension.php">Lab 5: .htaccess Override</a>
  <a href="lab6_polyglot.php">Lab 6: Polyglot</a>
  <a href="lab7_race_condition.php">Lab 7: Race Condition</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
