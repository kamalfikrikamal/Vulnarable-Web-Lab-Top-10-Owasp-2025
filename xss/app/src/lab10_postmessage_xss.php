<?php
$title = 'Lab 10: DOM-based XSS via postMessage';
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman ini mendengarkan event <code>message</code> — mekanisme
<code>postMessage</code> yang legal untuk komunikasi antar window/iframe, termasuk lintas
origin — dan menulis <code>event.data</code> langsung ke <code>innerHTML</code> elemen di
bawah, TANPA mengecek <code>event.origin</code> sama sekali. Artinya halaman, iframe, atau
domain <strong>mana pun</strong> bisa mengirim pesan ke halaman ini dan pesan tersebut akan
langsung dirender sebagai HTML aktif. Buka
<a href="lab10_attacker_iframe.php" target="_blank">halaman attacker (PoC)</a> — halaman itu
meng-embed halaman ini di dalam <code>&lt;iframe&gt;</code> lalu mengirim payload XSS lewat
<code>postMessage()</code> begitu iframe-nya selesai dimuat.</p>
</details>

<h3>Area Notifikasi</h3>
<p>Halaman ini mensimulasikan widget yang menerima notifikasi real-time dari window/iframe
lain lewat <code>postMessage</code> dan menampilkannya di kotak di bawah.</p>

<div id="notify-area" class="result-box">(menunggu pesan...)</div>

<script>
  // VULNERABLE: tidak ada pengecekan event.origin (harusnya divalidasi terhadap
  // allowlist domain terpercaya), dan event.data ditulis langsung ke innerHTML
  // tanpa sanitasi apa pun walaupun originnya divalidasi sekalipun.
  window.addEventListener('message', function(event) {
    document.getElementById('notify-area').innerHTML = event.data;
  });
</script>

<?php include 'footer.php'; ?>
