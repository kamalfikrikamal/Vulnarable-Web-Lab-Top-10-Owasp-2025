<?php
$title = 'Lab 7: Reflected XSS via User-Agent header';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman "analytics" ini menampilkan kembali header <code>User-Agent</code>
dari request tanpa di-escape. Header HTTP mudah dimanipulasi (browser dev tools, curl, atau
proxy seperti Burp Suite), jadi ini adalah bentuk lain dari reflected XSS yang tidak melibatkan
parameter URL sama sekali. Coba dengan curl:</p>

<div class="result-box">curl -A "&lt;script&gt;alert(document.domain)&lt;/script&gt;" http://localhost:8079/xss/lab7_useragent.php</div>

<p>Atau ganti User-Agent lewat DevTools &rarr; Network conditions, lalu reload halaman ini.</p>
</details>

<h3>Detected client info</h3>
<div class="result-box">
User-Agent: <?php echo $ua; /* VULNERABLE: header reflected without htmlspecialchars() */ ?>
</div>

<?php include 'footer.php'; ?>
