<?php
require_once __DIR__ . '/lib.php';
$title = 'Lab 8: Checksum dari Sumber yang Sama dengan Artifact';
$db = load_db();

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = (string)($_POST['package_content'] ?? '');
    $submitted_checksum = trim((string)($_POST['checksum'] ?? ''));
    $actual_checksum = hash('sha256', $content);

    // Pengecekan ini SENDIRI benar secara matematis - hash_equals() dipakai
    // dengan tepat, tidak ada bug perbandingan seperti Lab 3/5. VULNERABLE-nya
    // ada di DESAIN alurnya: checksum yang dibandingkan datang dari form yang
    // SAMA, diisi orang yang SAMA, dalam request yang SAMA dengan
    // package_content-nya sendiri - bukan dari kanal terpisah yang independen
    // dan tepercaya (mis. manifest resmi vendor yang ditandatangani, di-fetch
    // lewat HTTPS terpisah, atau hash yang sudah di-pin sebelumnya ke sistem).
    $valid = hash_equals($actual_checksum, $submitted_checksum);

    if ($valid) {
        $db['checksum_same_source_log'][] = ['content_preview' => substr($content, 0, 60), 'checksum' => $actual_checksum, 'time' => date('H:i:s')];
        save_db($db);
        $db = load_db();
    }

    $result = compact('content', 'submitted_checksum', 'actual_checksum', 'valid');
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: "terapkan" package yang isinya jelas-jelas berbahaya, dengan checksum yang
tetap dinyatakan VALID oleh server. Isi textarea dengan konten apa pun (bebas, simulasikan
payload jahat), lalu hitung checksum SHA-256-nya sendiri dan tempel ke field checksum.</p>
<pre class="result-box">echo -n "isi package kamu" | sha256sum</pre>
</details>

<h3>Terapkan Update dari Package Registry Internal</h3>
<form method="post">
  <label>Isi package</label><br>
  <textarea name="package_content" rows="4" style="width:100%;max-width:600px;" placeholder="mis. payload berbahaya apa saja"></textarea><br>
  <label>Checksum resmi (SHA-256, dari registry)</label><br>
  <input type="text" name="checksum" style="width:100%;max-width:600px;" placeholder="tempel hasil sha256sum di sini">
  <button type="submit">Verifikasi &amp; Terapkan</button>
</form>

<?php if ($result): ?>
<div class="<?php echo $result['valid'] ? 'error-box' : 'ok-box'; ?>">
  Checksum dikirim: <code><?php echo htmlspecialchars($result['submitted_checksum']); ?></code><br>
  Checksum aktual isi package: <code><?php echo htmlspecialchars($result['actual_checksum']); ?></code><br>
  Hasil verifikasi: <strong><?php echo $result['valid'] ? 'VALID — package DITERAPKAN sebagai update resmi.' : 'TIDAK COCOK — ditolak.'; ?></strong>
</div>
<?php endif; ?>

<?php if (!empty($db['checksum_same_source_log'])): ?>
<h3>Riwayat Package yang "Berhasil" Diterapkan</h3>
<table class="data-table">
<tr><th>Waktu</th><th>Cuplikan isi</th><th>Checksum</th></tr>
<?php foreach (array_reverse($db['checksum_same_source_log']) as $e): ?>
<tr><td><?php echo htmlspecialchars($e['time']); ?></td><td><code><?php echo htmlspecialchars($e['content_preview']); ?></code></td><td style="font-size:11px;"><?php echo htmlspecialchars($e['checksum']); ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<p class="hint">Kenapa berhasil: perbandingan checksum-nya sendiri BENAR secara teknis (SHA-256
asli, dibandingkan dengan <code>hash_equals()</code>) — bug-nya bukan di situ. Masalahnya:
checksum "resmi" yang dipakai sebagai pembanding datang dari FORM/PARTY YANG SAMA dengan konten
yang sedang diverifikasi, dalam request yang sama pula. Ini setara dengan minta seseorang
"buktikan identitasmu" lalu menerima kartu identitas apa pun yang mereka buat sendiri saat itu
juga — checksum cuma berguna sebagai bukti integritas kalau ia datang dari kanal yang
BENAR-BENAR terpisah dan independen dari artifact yang diverifikasi: manifest resmi vendor yang
ditandatangani secara kriptografis, di-fetch lewat kanal terautentikasi terpisah, atau hash yang
sudah di-<em>pin</em> ke sistem SEBELUM artifact yang mencurigakan itu pernah muncul.</p>

<?php include 'footer.php'; ?>
