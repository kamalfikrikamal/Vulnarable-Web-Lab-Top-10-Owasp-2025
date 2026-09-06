<?php
// INTENTIONALLY simple connection helper - training lab only.
// PHP 8.1+ defaults mysqli to throwing exceptions on error; these labs rely on
// query() returning false and $mysqli->error being readable instead.
mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = new mysqli('sqli-db', 'root', 'root_pass_change_me', 'vulnshop');
if ($mysqli->connect_errno) {
    die("DB connection failed: " . $mysqli->connect_error);
}
