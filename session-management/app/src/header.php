<?php
require_once __DIR__ . '/lib.php';
$title = $title ?? 'Broken Session Management Lab';
$db = load_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> - Session Management Lab</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a href="/">&larr; Portal</a>
  <a href="index.php">Home</a>
  <a href="lab1_token_survives_logout.php">Lab 1: Token Survives Logout</a>
  <a href="lab2_predictable_session_id.php">Lab 2: Predictable Session ID</a>
  <a href="lab3_session_fixation.php">Lab 3: Session Fixation</a>
  <a href="lab4_token_in_url.php">Lab 4: Token in URL</a>
</nav>
<div class="container">
<h1><?php echo $title; ?></h1>
