<?php
$title = 'Lab 3: IDOR dengan ID tidak berurutan';
include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }

function doc_token($username) { return substr(sha1($username . '_doc_salt_2025'), 0, 12); }

$docs = [];
foreach ($db['users'] as $u) {
    $docs[] = ['token' => doc_token($u['username']), 'owner' => $u['username'], 'title' => ucfirst($u['username']) . "'s Q3 salary review.pdf", 'content' => "Confidential salary review for {$u['username']}: base salary adjustment +8%, bonus eligibility: yes."];
}

$my_token = doc_token($me['username']);
$token = $_GET['token'] ?? $my_token;
$found = null;
foreach ($docs as $d) if ($d['token'] === $token) { $found = $d; break; }
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: dokumen tidak lagi dipakai ID numerik berurutan (<code>1,2,3</code>) &mdash;
sekarang pakai token 12-karakter yang terlihat acak, dengan asumsi developer "tidak bisa ditebak
= aman". Tapi keamanan berbasis "ID sulit ditebak" (security through obscurity) runtuh begitu
token itu bocor lewat kanal lain. Lihat bagian "Recent team activity" di bawah &mdash; token
milik user lain bocor di sana. Salin salah satu token, tempel ke parameter <code>?token=</code>.</p>
</details>

<p>Token dokumenmu: <code><?php echo htmlspecialchars($my_token); ?></code> (URL:
<code>?token=<?php echo htmlspecialchars($my_token); ?></code>)</p>

<form method="get">
  <label>Document token</label><br>
  <input type="text" name="token" value="<?php echo htmlspecialchars($token); ?>">
  <button type="submit">Buka Dokumen</button>
</form>

<?php if ($found): ?>
<table class="data-table">
  <tr><th>Judul</th><td><?php echo htmlspecialchars($found['title']); ?></td></tr>
  <tr><th>Pemilik</th><td><?php echo htmlspecialchars($found['owner']); ?></td></tr>
  <tr><th>Isi</th><td><?php echo htmlspecialchars($found['content']); ?></td></tr>
</table>
<?php if ($found['owner'] !== $me['username']): ?>
<div class="error-box">Ini dokumen milik <?php echo htmlspecialchars($found['owner']); ?>, bukan milikmu &mdash; token acak tidak menggantikan pengecekan otorisasi.</div>
<?php endif; ?>
<?php endif; ?>

<h3>Recent team activity (visible ke semua user login)</h3>
<div class="creds-box">
<?php foreach ($docs as $d): if ($d['owner'] === $me['username']) continue; ?>
  &bull; <?php echo htmlspecialchars($d['owner']); ?> membagikan file ke channel #finance:
  <code>?token=<?php echo htmlspecialchars($d['token']); ?></code> ("<?php echo htmlspecialchars($d['title']); ?>")<br>
<?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
