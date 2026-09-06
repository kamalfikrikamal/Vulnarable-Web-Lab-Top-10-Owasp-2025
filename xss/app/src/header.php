<?php $title = $title ?? 'XSS Playground'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - XSS Playground</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_reflected.php">Lab 1: Reflected HTML</a>
  <a href="lab2_reflected_attribute.php">Lab 2: Attribute</a>
  <a href="lab3_reflected_js.php">Lab 3: JS Context</a>
  <a href="lab4_stored_comments.php">Lab 4: Stored</a>
  <a href="lab5_dom_xss.php">Lab 5: DOM-based</a>
  <a href="lab6_filter_bypass.php">Lab 6: Filter Bypass</a>
  <a href="lab7_useragent.php">Lab 7: User-Agent</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
