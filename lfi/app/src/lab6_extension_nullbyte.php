<?php
$title = 'Lab 6: Validasi Ekstensi dengan Null Byte Bypass';
$page = $_GET['page'] ?? 'pages/welcome.png';

// Validasi ekstensi dijalankan pada STRING MENTAH (sebelum null byte "dipotong").
$valid_ext = (substr($page, -4) === '.png');

$target = null;
if ($valid_ext) {
    // SIMULASI perilaku historis PHP < 5.3.4: fungsi filesystem level-C (fopen/include) di versi
    // lama PHP memotong string di byte NUL karena string C diakhiri null, padahal string PHP
    // sendiri binary-safe (tidak terpotong). Efeknya, validasi ekstensi di atas melihat string
    // PENUH (termasuk ".png" palsu setelah null byte) dan lolos, tapi file yang benar-benar
    // dibuka terpotong sebelum null byte tersebut.
    // CATATAN: PHP modern (5.3.4+) SUDAH TIDAK rentan terhadap null byte injection secara
    // native - baris di bawah ini SENGAJA ditambahkan supaya teknik historis ini tetap bisa
    // dipelajari dan dipraktikkan di lab ini.
    $target = $page;
    $null_pos = strpos($target, "\0");
    if ($null_pos !== false) {
        $target = substr($target, 0, $null_pos);
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: aplikasi cuma mengizinkan file yang <strong>berakhiran</strong>
<code>.png</code> (dianggap "aman", untuk memuat file gambar). Sisipkan null byte
(<code>%00</code>) di antara path target dan ekstensi <code>.png</code> palsu &mdash; validasi
akan melihat string penuh yang berakhir <code>.png</code> dan meloloskannya, tapi file yang
benar-benar dibuka terpotong sebelum null byte:</p>
<ul class="hint">
  <li><code>?page=../../../../etc/passwd%00.png</code></li>
</ul>
<p class="hint">Catatan: ini adalah teknik <strong>historis</strong> (bug asli di PHP &lt;
5.3.4). PHP modern sudah tidak rentan secara native &mdash; lab ini mensimulasikan perilaku
lama tersebut secara sengaja di kode aplikasinya supaya tekniknya tetap bisa dipraktikkan.</p>
</details>

<?php if (!$valid_ext): ?>
<div class="error-box">Blocked: hanya file berakhiran ".png" yang diizinkan.</div>
<?php endif; ?>

<form method="get">
  <label>Page</label><br>
  <input type="text" name="page" value="<?php echo htmlspecialchars($page); ?>">
  <button type="submit">Load</button>
</form>

<div class="result-box">
<?php if ($target !== null) { include $target; } ?>
</div>

<?php include 'footer.php'; ?>
