<?php
$title = 'Lab 2: Admin dengan URL "tersembunyi"';
include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman ini sengaja <strong>tidak</strong> ditaruh di menu navigasi mana
pun, dengan asumsi developer "kalau linknya tidak ada, tidak akan ditemukan" (security through
obscurity). Tapi URL-nya tetap tercatat di <code>/robots.txt</code> &mdash; file yang memang
dipublikasikan untuk crawler mesin pencari, dan jadi salah satu tempat pertama yang dicek
attacker.</p>
</details>

<p>Coba lihat <a href="robots.txt" target="_blank">robots.txt</a> aplikasi ini &mdash; ada satu
baris <code>Disallow</code> yang menunjuk ke sebuah file yang tidak pernah kamu lihat linknya di
menu manapun. Buka file itu langsung.</p>

<?php include 'footer.php'; ?>
