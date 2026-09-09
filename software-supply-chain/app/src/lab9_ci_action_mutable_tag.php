<?php
$title = 'Lab 9: CI Action Dipin ke Tag Mutable';
require_once __DIR__ . '/lib.php';
$db = load_db();

$SAFE_COMMIT = 'a1b2c3d (asli - jalankan build & upload artifact, tidak ada lainnya)';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'retarget') {
    $db['ci_action_v1_commit'] = 'f9e8d7c (BARU - disisipkan attacker: mencuri semua secret CI/CD & mengirimnya ke attacker.example)';
    save_db($db);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    $db['ci_action_v1_commit'] = $SAFE_COMMIT;
    save_db($db);
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: pahami kenapa workflow yang mem-pin third-party Action ke tag mutable
(<code>@v1</code>) rentan dibanding yang mem-pin ke commit SHA. Coba "ambil alih" tag <code>v1</code>
lewat form di bawah (mensimulasikan akun maintainer action tersebut yang disusupi), lalu
jalankan kedua workflow dan bandingkan hasilnya.</p>
</details>

<p>Workflow CI/CD sering memanggil Action pihak ketiga dari marketplace (GitHub Actions,
GitLab CI templates, dsb). Ada dua cara mem-referensikannya:</p>

<pre class="result-box"># Workflow A (rentan): pin ke tag - tag adalah pointer yang BISA dipindah maintainer kapan saja
- uses: corp-actions/build-helper@v1

# Workflow B (aman): pin ke commit SHA - SHA adalah identitas konten yang tidak bisa diubah
- uses: corp-actions/build-helper@a1b2c3d4e5f6...</pre>

<h3>1. Simulasikan repo Action-nya disusupi</h3>
<p>Anggap kamu berhasil membajak akun maintainer <code>corp-actions/build-helper</code> (lewat
phishing, token bocor, dsb — skenario nyata seperti insiden <code>tj-actions/changed-files</code>
2024). Kamu memindahkan tag <code>v1</code> ke commit baru yang berisi kode jahat:</p>
<form method="post" style="display:inline-block; margin-right:10px;">
  <input type="hidden" name="action" value="retarget">
  <button type="submit">Pindahkan tag v1 ke commit jahat (git tag -f v1 &lt;commit-jahat&gt; && git push -f)</button>
</form>
<form method="post" style="display:inline-block;">
  <input type="hidden" name="action" value="reset">
  <button type="submit">Reset tag v1 ke commit asli</button>
</form>

<div class="creds-box">Saat ini tag <code>v1</code> di repo <code>corp-actions/build-helper</code>
menunjuk ke commit: <code><?php echo htmlspecialchars($db['ci_action_v1_commit']); ?></code></div>

<h3>2. Jalankan kedua workflow</h3>
<table class="data-table">
<tr><th>Workflow</th><th>Referensi</th><th>Commit yang benar-benar dijalankan</th></tr>
<tr>
  <td>A (pin ke tag)</td>
  <td><code>@v1</code></td>
  <td><?php echo htmlspecialchars($db['ci_action_v1_commit']); ?></td>
</tr>
<tr>
  <td>B (pin ke SHA)</td>
  <td><code>@a1b2c3d...</code> (SHA eksplisit)</td>
  <td><?php echo htmlspecialchars($SAFE_COMMIT); ?> — <strong>selalu sama</strong>, apa pun yang terjadi pada tag <code>v1</code></td>
</tr>
</table>

<p class="hint">Kenapa berhasil: tag Git (<code>v1</code>, <code>latest</code>, <code>main</code>,
dst) hanyalah <em>pointer</em> yang bisa dipindahkan kapan saja oleh siapa pun yang punya akses
tulis ke repo tersebut — termasuk attacker yang berhasil membajaknya. Workflow yang mem-pin ke
<code>@v1</code> secara otomatis menjalankan APAPUN yang sedang ditunjuk tag itu <em>saat build
berjalan</em>, bukan kode yang direview tim saat pertama kali menambahkan Action ini ke workflow.
Commit SHA sebaliknya adalah hash konten — secara matematis tidak mungkin dua konten berbeda
menghasilkan SHA yang sama, jadi mem-pin ke SHA menjamin kode yang berjalan persis kode yang
pernah direview, selamanya, terlepas dari apa pun yang terjadi kemudian di repo Action tersebut.</p>

<?php include 'footer.php'; ?>
