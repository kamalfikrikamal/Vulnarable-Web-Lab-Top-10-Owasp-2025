<?php
$title = 'Lab 4: Admin - Apply System Update';
require_once __DIR__ . '/lib.php';

function official_checksum_path() { return __DIR__ . '/data/official_update_checksum.txt'; }
function applied_update_path() { return __DIR__ . '/data/applied_update.txt'; }

// Seed the "official" checksum reference file on first run. This is what a
// real update pipeline WOULD compare an uploaded artifact against before
// applying it. The vulnerable code below never actually reads this file -
// that's the whole point of this lab.
if (!file_exists(official_checksum_path())) {
    $legit_content = "SYSTEM-UPDATE-v2.1.0\nRelease: Security patch bundle\nBuild: 2026-09-01\n";
    $legit_hash = hash('sha256', $legit_content);
    file_put_contents(official_checksum_path(), "$legit_hash  official-update-v2.1.0.txt\n");
}

$upload_result = '';
$applied_content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['update_file']) && $_FILES['update_file']['error'] === UPLOAD_ERR_OK) {
    $content = file_get_contents($_FILES['update_file']['tmp_name']);
    // VULNERABLE: the uploaded file's content is saved and "applied" as the
    // new official system update with ZERO verification against any
    // known-good checksum or signature. There isn't even a weak/broken check
    // here - there is simply no comparison of any kind. Whatever bytes the
    // client uploaded are now trusted as if they were the legitimate,
    // vendor-issued update.
    file_put_contents(applied_update_path(), $content);
    $upload_result = 'Update berhasil diterapkan!';
    $applied_content = $content;
} elseif (file_exists(applied_update_path())) {
    $applied_content = file_get_contents(applied_update_path());
}

$official_checksum_line = file_exists(official_checksum_path()) ? trim(file_get_contents(official_checksum_path())) : '';
$official_hash = $official_checksum_line !== '' ? trim(explode(' ', $official_checksum_line)[0]) : '';
$applied_hash = $applied_content !== '' ? hash('sha256', $applied_content) : '';

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: upload file "update" apa pun (bukan file resmi) dan lihat bahwa server
menerimanya begitu saja sebagai update sistem yang sah, tanpa mengeceknya terhadap checksum resmi
sama sekali. Buat file teks apa pun berisi teks bebas (misalnya berisi konfigurasi/perintah
palsu), upload lewat form di bawah, dan perhatikan bahwa halaman tidak pernah membandingkan hash
file kamu dengan <code>data/official_update_checksum.txt</code> &mdash; field itu memang ada di
server, tapi kodenya tidak pernah membacanya.</p>
</details>

<p>Referensi checksum resmi (disimpan di server, <strong>tidak pernah dipakai oleh kode di bawah
ini</strong>):</p>
<div class="result-box"><?php echo htmlspecialchars($official_checksum_line ?: '(belum ada)'); ?></div>

<h3>Apply System Update</h3>
<form method="post" enctype="multipart/form-data">
  <input type="file" name="update_file"><br><br>
  <button type="submit">Apply Update</button>
</form>

<?php if ($upload_result): ?>
<div class="result-box"><?php echo htmlspecialchars($upload_result); ?></div>
<?php endif; ?>

<?php if ($applied_content !== ''): ?>
<h3>Isi update yang sedang "diterapkan" saat ini</h3>
<div class="result-box"><?php echo htmlspecialchars($applied_content); ?></div>
<p>SHA-256 dari file yang baru saja diterapkan: <code><?php echo htmlspecialchars($applied_hash); ?></code></p>
<?php if ($official_hash && $applied_hash && $applied_hash !== $official_hash): ?>
<div class="error-box">Hash file yang diterapkan TIDAK cocok dengan checksum resmi
(<code><?php echo htmlspecialchars($official_hash); ?></code>) &mdash; tapi server tetap
menerimanya, karena tidak pernah membandingkan keduanya sama sekali.</div>
<?php elseif ($official_hash && $applied_hash && $applied_hash === $official_hash): ?>
<div class="ok-box">Kebetulan hash-nya cocok dengan versi resmi.</div>
<?php endif; ?>
<?php endif; ?>

<h3>Kenapa ini berbahaya</h3>
<p>Update yang diterapkan di sini tidak pernah dieksekusi sebagai kode &mdash; hanya disimpan
sebagai teks. Tapi bahkan file yang "cuma" berisi konfigurasi, teks lisensi, atau data lain yang
dipakai untuk keputusan yang berkaitan dengan keamanan, tetap berbahaya kalau diterima tanpa
verifikasi keasliannya. Perhatikan juga: checksum (hash biasa seperti SHA-256) saja sebenarnya
TIDAK CUKUP untuk membuktikan keaslian &mdash; checksum hanya melindungi dari korupsi data yang
tidak disengaja (misalnya file rusak saat transfer), karena attacker yang mengganti isi file bisa
dengan mudah menghitung ulang checksum barunya sendiri dan menyertakannya juga. Jaminan integritas
yang sesungguhnya butuh <strong>signature kriptografis</strong> (misalnya tanda tangan digital)
yang diverifikasi memakai public key milik penerbit resmi yang sudah dipercaya sebelumnya &mdash;
sesuatu yang tidak bisa dipalsukan attacker walau mereka bisa mengganti isi file dan menghitung
checksum barunya sendiri.</p>

<?php include 'footer.php'; ?>
