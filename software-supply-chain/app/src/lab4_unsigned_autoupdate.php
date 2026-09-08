<?php
$title = 'Lab 4: Unsigned Auto-Update';
include 'header.php';

$paket_url = $_GET['paket_url'] ?? '';
$content = null;
$error = null;

if ($paket_url !== '') {
    // VULNERABLE: fetch/baca paket dari lokasi yang diberikan lalu langsung "diterapkan" -
    // TIDAK ADA verifikasi signature/checksum sama sekali sebelum "menginstall"-nya.
    $full_path = __DIR__ . '/' . $paket_url;
    $data = @file_get_contents($full_path);
    if ($data === false) {
        $error = 'Paket tidak ditemukan / gagal diambil dari: ' . $paket_url;
    } else {
        $content = $data;
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: fitur auto-update plugin di bawah ini mengambil "paket update" dari URL
yang diberikan admin, lalu langsung menerapkannya &mdash; TANPA pernah memverifikasi
signature/checksum apa pun. Aplikasi tidak punya cara membedakan paket resmi dari vendor dengan
paket yang sudah dimodifikasi/disusupi.</p>
<p class="hint">Sudah disiapkan dua contoh "paket" untuk dicoba (offline, tanpa perlu internet):</p>
<ul class="hint">
  <li><code>?paket_url=data/official_update.txt</code> &mdash; paket resmi yang legit.</li>
  <li><code>?paket_url=data/tampered_update.txt</code> &mdash; paket yang sudah ditampering /
  disusupi penyerang, tapi strukturnya dibuat terlihat sama persis dengan yang resmi.</li>
</ul>
<p class="hint">Perhatikan: KEDUANYA diterima dan "diterapkan" begitu saja oleh aplikasi, dengan
respons sukses yang sama. Itulah bug-nya &mdash; tidak ada pengecekan apa pun yang membedakan
keduanya.</p>
</details>

<h3>Auto-Update Plugin (Admin)</h3>
<form method="get">
  <label>URL paket update</label><br>
  <input type="text" name="paket_url" value="<?php echo htmlspecialchars($paket_url); ?>" style="width:100%; max-width:500px;" placeholder="mis. data/official_update.txt">
  <button type="submit">Terapkan Update</button>
</form>

<?php if ($content !== null): ?>
<div class="ok-box">Update berhasil diterapkan, isi paket:</div>
<div class="result-box"><?php echo htmlspecialchars($content); ?></div>
<?php elseif ($error !== null): ?>
<div class="error-box"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<p class="hint">Kenapa berhasil: tidak ada pemeriksaan signature (mis. GPG/code-signing) ataupun
checksum (SHA-256, dsb.) terhadap kunci publik vendor sebelum paket "diterapkan". Aplikasi hanya
percaya begitu saja pada isi apa pun yang ada di lokasi yang diberikan. Bandingkan hasil
<code>data/official_update.txt</code> (legit) dengan <code>data/tampered_update.txt</code>
(sudah disusupi) &mdash; keduanya diterima dengan pesan sukses yang identik, membuktikan aplikasi
sama sekali tidak bisa membedakan paket resmi dari paket yang sudah dimanipulasi. Di dunia nyata,
ini setara dengan insiden supply-chain seperti SolarWinds Orion (2020) di mana mekanisme update
resmi dipakai untuk mendistribusikan backdoor ke ribuan pelanggan tanpa terdeteksi.</p>

<?php include 'footer.php'; ?>
