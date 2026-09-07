<?php
$title = 'Lab 2: API Key Sekuensial';
include 'header.php';

// You are "alice", API key 1042 (issued right below admin's key at 1043,
// because keys are simply an auto-increment counter).
$my_key = 1042;
$lookup = $_GET['key'] ?? $my_key;
$found = null;
foreach ($db['api_keys'] as $k) if ((string)$k['id'] === (string)$lookup) { $found = $k; break; }
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: API key kamu adalah <code>1042</code> &mdash; ini cuma nilai
<code>AUTO_INCREMENT</code> biasa dari database, bukan token acak. Coba naikkan/turunkan angkanya
untuk menemukan API key milik user lain.</p>
</details>

<p>API key kamu (sebagai alice): <code><?php echo $my_key; ?></code></p>

<form method="get">
  <label>Lookup API key</label><br>
  <input type="text" name="key" value="<?php echo htmlspecialchars($lookup); ?>">
  <button type="submit">Lihat Detail Key</button>
</form>

<?php if ($found): ?>
<table class="data-table">
  <tr><th>Key ID</th><td><?php echo (int)$found['id']; ?></td></tr>
  <tr><th>Owner</th><td><?php echo htmlspecialchars($found['owner']); ?></td></tr>
  <tr><th>Note</th><td><?php echo htmlspecialchars($found['note']); ?></td></tr>
</table>
<?php if ($found['owner'] !== 'alice'): ?>
<div class="error-box">Ini API key milik <?php echo htmlspecialchars($found['owner']); ?>, ditemukan hanya dengan menaikkan angka satu per satu dari key milikmu sendiri.</div>
<?php endif; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>
