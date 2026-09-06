<?php $title = 'Home'; include 'header.php'; ?>

<p>VulnShop is an intentionally vulnerable demo shop used to practice the main SQL Injection
categories described by PortSwigger's Web Security Academy. Each lab below has one distinct
injectable parameter. See the project <code>README.md</code> for full instructions and payload hints.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Authentication Bypass (classic in-band)</h3>
  <p>A login form builds its query by concatenating the username/password directly into SQL.</p>
  <a class="btn" href="lab1_login_bypass.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; UNION-based SQL Injection</h3>
  <p>A numeric product-id parameter is injectable, allowing UNION SELECT to pull data from other tables.</p>
  <a class="btn" href="lab2_union.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Error-based SQL Injection</h3>
  <p>A string category filter leaks data through verbose MySQL error messages (extractvalue/updatexml).</p>
  <a class="btn" href="lab3_error_based.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Blind SQL Injection (Boolean-based)</h3>
  <p>No data or errors are shown, only a "found / not found" style response you can use as an oracle.</p>
  <a class="btn" href="lab4_blind_boolean.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 5 &mdash; Blind SQL Injection (Time-based)</h3>
  <p>The response is identical regardless of the query result &mdash; use SLEEP() to infer data via timing.</p>
  <a class="btn" href="lab5_blind_time.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 6 &mdash; Second-Order SQL Injection</h3>
  <p>A payload is safely stored during registration, then unsafely reused later in a different query.</p>
  <a class="btn" href="lab6_second_order.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 7 &mdash; SQL Injection in an ORDER BY clause</h3>
  <p>User-controlled sort parameter cannot be parameterised the normal way and is injected verbatim.</p>
  <a class="btn" href="lab7_order_by.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
