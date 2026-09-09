<?php
$title = 'Lab 10: Container Base Image Dipin ke Tag Mutable';
require_once __DIR__ . '/lib.php';
$db = load_db();

$SAFE_DIGEST_CONTENT = 'Base image resmi Corp - php:8.2-apache + tools internal standar (tidak ada backdoor)';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'push_backdoor') {
    $db['base_image_latest_content'] = 'BACKDOOR: cron job tersembunyi yang mem-beacon ke attacker.example setiap 5 menit, ditambahkan ke layer image';
    save_db($db);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    $db['base_image_latest_content'] = $SAFE_DIGEST_CONTENT;
    save_db($db);
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: sama seperti Lab 9, tapi konteksnya container image, bukan CI Action —
prinsipnya identik: referensi <em>mutable</em> (tag) vs referensi <em>immutable</em> (digest).
"Push" image baru ke tag <code>:latest</code> lewat form di bawah, lalu bandingkan hasil build
dua Dockerfile.</p>
</details>

<p>Dua cara mem-referensikan base image di Dockerfile:</p>
<pre class="result-box"># Dockerfile A (rentan): pin ke tag - ":latest" cuma label, isinya bisa berubah kapan saja
FROM registry.corp.internal/base-image:latest

# Dockerfile B (aman): pin ke digest - content-addressed, isinya dijamin tidak pernah berubah
FROM registry.corp.internal/base-image@sha256:8f3a9c...</pre>

<h3>1. Simulasikan registry image disusupi</h3>
<p>Anggap kredensial push ke <code>registry.corp.internal</code> bocor (mis. lewat CI/CD secret
yang ter-expose — lihat Lab 3), dan attacker mem-push image baru ke tag <code>:latest</code>
yang sama:</p>
<form method="post" style="display:inline-block; margin-right:10px;">
  <input type="hidden" name="action" value="push_backdoor">
  <button type="submit">Push image backdoor ke tag :latest (docker push base-image:latest)</button>
</form>
<form method="post" style="display:inline-block;">
  <input type="hidden" name="action" value="reset">
  <button type="submit">Reset :latest ke image asli</button>
</form>

<div class="creds-box">Tag <code>base-image:latest</code> saat ini berisi:
<code><?php echo htmlspecialchars($db['base_image_latest_content']); ?></code></div>

<h3>2. Bandingkan hasil build</h3>
<table class="data-table">
<tr><th>Dockerfile</th><th>Referensi</th><th>Isi yang benar-benar ter-build</th></tr>
<tr>
  <td>A (pin ke tag)</td>
  <td><code>base-image:latest</code></td>
  <td><?php echo htmlspecialchars($db['base_image_latest_content']); ?></td>
</tr>
<tr>
  <td>B (pin ke digest)</td>
  <td><code>base-image@sha256:8f3a9c...</code></td>
  <td><?php echo htmlspecialchars($SAFE_DIGEST_CONTENT); ?> — <strong>selalu sama</strong>, apa pun yang di-push ke tag <code>:latest</code></td>
</tr>
</table>

<p class="hint">Kenapa berhasil: tag image container (<code>:latest</code>, <code>:stable</code>,
<code>:v2</code>, dst) hanyalah label yang menunjuk ke digest tertentu — dan label itu bisa
dipindahkan ke digest lain kapan saja oleh siapa pun yang punya akses push ke registry. Setiap
kali <code>docker build</code>/<code>docker pull</code> dijalankan tanpa digest eksplisit, Docker
mengambil APAPUN yang SEDANG ditunjuk tag tersebut saat itu — bukan image yang direview tim saat
pertama kali menulis Dockerfile-nya. Digest (<code>sha256:...</code>) adalah hash konten image
itu sendiri, jadi mem-pin ke digest menjamin build selalu memakai byte yang persis sama, selamanya
— inilah kenapa pipeline CI/CD production dan rekomendasi keamanan supply chain (mis. SLSA) selalu
menganjurkan pin ke digest untuk base image, bukan cuma tag.</p>

<?php include 'footer.php'; ?>
