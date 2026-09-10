<?php
$title = 'Lab 7: Data Sensitif Bocor Lewat Console Browser';
include 'header.php';

// Data "debug" fiktif yang seharusnya tidak pernah sampai ke browser pengguna.
$debug_session_token = 'sess_9f8c2a41e7b3441dbe9a7d6c3f0a1122';
$debug_api_key = 'sk_internal_live_4f9b2e7a1c8d3f56';
$debug_card_last4 = '4412';
$debug_user_id = 42;

$console_line = "console.log('DEBUG session:', {user_id: {$debug_user_id}, session_token: '{$debug_session_token}', "
    . "saved_card_last4: '{$debug_card_last4}', internal_api_key: '{$debug_api_key}'});";
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman "checkout" di bawah ini terlihat normal &mdash; tidak ada apa pun
yang sensitif muncul di teks halaman atau di response HTML/network. Kebocorannya murni terjadi di
<strong>console browser</strong>, lewat baris <code>console.log(...)</code> yang lupa dihapus
developer sebelum rilis ke produksi.</p>
<p class="hint">Buka DevTools browser kamu sendiri sekarang (klik kanan &rarr; Inspect, atau
F12/Cmd+Opt+I), pindah ke tab <strong>Console</strong>, lalu klik tombol
"Lanjutkan ke Pembayaran" di bawah. Kamu akan melihat baris <code>DEBUG session:</code> muncul di
console berisi <code>session_token</code> dan <code>internal_api_key</code> sungguhan &mdash;
persis seperti yang direproduksi di kotak abu-abu di bawah halaman ini (supaya lab tetap bisa
dibuktikan tanpa harus membuka browser).</p>
</details>

<h3>Checkout</h3>
<p>Pesananmu: <strong>1x Langganan Premium</strong> &mdash; Rp 149.000 / bulan.</p>
<p>Kartu tersimpan: •••• •••• •••• <?php echo htmlspecialchars($debug_card_last4); ?></p>

<button type="button" onclick="window.__lab7Checkout && window.__lab7Checkout();">Lanjutkan ke Pembayaran</button>
<div id="lab7-status" class="ok-box" style="display:none; margin-top:12px;">Pembayaran diproses (demo, tidak ada transaksi sungguhan).</div>

<script>
// VULNERABLE: baris debug ini seharusnya dihapus sebelum deploy ke produksi.
// Data sensitif (session token, API key internal) ikut ditulis ke console
// browser setiap kali pengguna klik "Lanjutkan ke Pembayaran" -- terlihat oleh
// siapa pun yang membuka DevTools di sesi browser yang sedang aktif ini.
window.__lab7Checkout = function () {
    console.log('DEBUG session:', {
        user_id: <?php echo (int)$debug_user_id; ?>,
        session_token: '<?php echo $debug_session_token; ?>',
        saved_card_last4: '<?php echo $debug_card_last4; ?>',
        internal_api_key: '<?php echo $debug_api_key; ?>'
    });
    document.getElementById('lab7-status').style.display = 'block';
};
// Juga langsung jalan sekali saat halaman dimuat, supaya kebocorannya tidak
// bergantung sama sekali pada trainee mengklik tombol.
window.__lab7Checkout();
</script>

<h3>Apa yang sebenarnya tertulis di console (direproduksi di sini biar bisa dibuktikan)</h3>
<p class="hint">Baris persis di bawah ini adalah apa yang akan muncul di tab Console DevTools
browser kamu &mdash; direproduksi di halaman supaya lab bisa dinilai/diverifikasi tanpa harus
benar-benar membuka DevTools, tapi kamu tetap didorong untuk membukanya sendiri dan melihatnya
muncul secara langsung.</p>
<div class="result-box"><?php echo htmlspecialchars($console_line); ?></div>

<div class="error-box" style="margin-top:20px;">
Kenapa ini berbahaya: halaman ini <strong>terlihat aman</strong> &mdash; tidak ada token atau kunci
API yang muncul di teks halaman, HTML mentah, maupun response jaringan mana pun. Tapi
<code>session_token</code> dan <code>internal_api_key</code> tetap bocor lewat console browser,
yang trivial dilihat oleh:
<ul>
<li>siapa pun dengan akses fisik/sementara ke sesi browser yang sedang terbuka (shoulder-surfing DevTools);</li>
<li>ekstensi browser jahat/yang sudah disusupi &mdash; ekstensi umumnya sudah punya akses ke
konten halaman, dan developer yang terbiasa logging berlebihan ke console juga cenderung
"terlalu percaya" bahwa channel ini tidak terlihat;</li>
<li>support/QA engineer yang cuma diminta "buka console, cek ada error apa nggak" &mdash; dan
tanpa sengaja melihat token sesi yang masih aktif.</li>
</ul>
Berbeda dari Lab 1&ndash;6 (semuanya kebocoran di log <em>server-side</em>), kebocoran di sini
terjadi langsung di browser setiap pengunjung, dan konsol developer sering di-screen-share,
di-screenshot, lalu ditempel ke bug tracker atau Slack tanpa disadari itu memuat rahasia.
<br><br>
<strong>Perbaikan:</strong> hapus seluruh <code>console.log</code> debug sebelum build produksi
(otomatis lewat build tooling, mis. opsi <code>drop_console</code> di Babel/Terser, atau lint rule
yang melarang <code>console.log</code> masuk ke commit), dan jangan pernah mencatat
token/secret/API key ke console bahkan saat development.
</div>

<?php include 'footer.php'; ?>
