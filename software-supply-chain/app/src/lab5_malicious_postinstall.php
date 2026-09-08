<?php
$title = 'Lab 5: Malicious Postinstall Script';
include 'header.php';

$submit_msg = null;
$build_outputs = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit') {
    $name = trim((string)($_POST['pkg_name'] ?? ''));
    $desc = trim((string)($_POST['pkg_desc'] ?? ''));
    $postinstall = trim((string)($_POST['pkg_postinstall'] ?? ''));
    if ($name !== '' && $postinstall !== '') {
        $db['submitted_packages'][] = [
            'name' => $name,
            'description' => $desc,
            'postinstall' => $postinstall,
        ];
        save_db($db);
        $submit_msg = "Paket \"$name\" berhasil disubmit sebagai dependency baru.";
    } else {
        $submit_msg = 'Nama paket dan perintah postinstall wajib diisi.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'build') {
    $build_outputs = [];
    foreach ($db['submitted_packages'] as $pkg) {
        // VULNERABLE: skrip lifecycle dari dependency pihak ketiga dieksekusi mentah-mentah,
        // dengan privilege penuh proses build - persis seperti npm/composer install script
        // yang jalan otomatis tanpa review manusia.
        $out = shell_exec($pkg['postinstall'] . ' 2>&1');
        $build_outputs[] = ['name' => $pkg['name'], 'cmd' => $pkg['postinstall'], 'output' => $out];
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: paket manager sungguhan (npm, composer, pip, dst.) mengizinkan dependency
mendefinisikan skrip <em>lifecycle</em> (mis. <code>postinstall</code>) yang otomatis dijalankan
begitu paket ter-install/ter-build &mdash; dengan privilege penuh milik siapa pun yang menjalankan
install/build tersebut. Kalau sebuah project menarik dependency pihak ketiga tanpa mereview isi
skrip lifecycle-nya, penulis dependency itu efektif mendapat eksekusi kode di setiap mesin yang
membangun project tersebut.</p>
<p class="hint">Submit paket baru dengan field <code>postinstall</code> diisi perintah shell,
misalnya <code>id</code> atau <code>whoami</code>, lalu klik "Jalankan Build (Admin)" untuk
melihat perintah itu benar-benar dieksekusi di server.</p>
</details>

<h3>1. Submit Paket (Dependency Baru)</h3>
<p>Simulasi mempublikasikan/menambahkan dependency pihak ketiga ke sebuah project.</p>
<form method="post">
  <input type="hidden" name="action" value="submit">
  <label>Nama paket</label><br>
  <input type="text" name="pkg_name" placeholder="mis. left-pad-plus"><br>
  <label>Deskripsi</label><br>
  <input type="text" name="pkg_desc" placeholder="mis. utility string padding"><br>
  <label>Perintah shell yang dijalankan otomatis setelah package di-install (postinstall)</label><br>
  <input type="text" name="pkg_postinstall" placeholder="mis. id"><br>
  <button type="submit">Submit Paket</button>
</form>
<?php if ($submit_msg): ?>
<div class="ok-box"><?php echo htmlspecialchars($submit_msg); ?></div>
<?php endif; ?>

<?php if (!empty($db['submitted_packages'])): ?>
<h4>Paket yang sudah disubmit</h4>
<table class="data-table">
<tr><th>Nama</th><th>Deskripsi</th><th>postinstall</th></tr>
<?php foreach ($db['submitted_packages'] as $p): ?>
<tr>
  <td><?php echo htmlspecialchars($p['name']); ?></td>
  <td><?php echo htmlspecialchars($p['description']); ?></td>
  <td><code><?php echo htmlspecialchars($p['postinstall']); ?></code></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<h3>2. Jalankan Build (Admin)</h3>
<p>Simulasi proses build/install project yang menarik semua dependency di atas, lalu otomatis
menjalankan skrip <code>postinstall</code> masing-masing.</p>
<form method="post">
  <input type="hidden" name="action" value="build">
  <button type="submit">Jalankan Build (Admin)</button>
</form>

<?php if ($build_outputs !== null): ?>
  <?php if (empty($build_outputs)): ?>
  <div class="hint">Belum ada paket yang disubmit.</div>
  <?php endif; ?>
  <?php foreach ($build_outputs as $b): ?>
  <p><strong>Output build [<?php echo htmlspecialchars($b['name']); ?>]</strong> (menjalankan <code><?php echo htmlspecialchars($b['cmd']); ?></code>):</p>
  <div class="result-box"><?php echo htmlspecialchars($b['output']); ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<p class="hint">Kenapa berhasil: perintah di field <code>postinstall</code> dieksekusi langsung
via <code>shell_exec()</code> tanpa validasi/whitelist apa pun, meniru bagaimana package manager
sungguhan menjalankan lifecycle script dependency secara otomatis begitu <code>install</code>
atau <code>build</code> dipanggil. Kode ini berjalan dengan privilege penuh milik proses build
itu sendiri, bukan privilege terbatas si "penulis paket" &mdash; itulah kenapa dependency yang
tidak direview bisa jadi jalur RCE penuh, walau tidak ada satu baris pun kode aplikasi utama yang
diubah.</p>

<?php include 'footer.php'; ?>
