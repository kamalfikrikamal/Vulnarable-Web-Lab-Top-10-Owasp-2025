<?php
$title = 'Lab 2: Hash MD5 Tanpa Salt';
include 'header.php';

$default_wordlist = "123456\npassword\nadmin123\nP@ssw0rd\nSummer2024!\nletmein\nqwerty\nchangeme\n";
$wordlist = $_POST['wordlist'] ?? $default_wordlist;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidates = array_filter(array_map('trim', explode("\n", $wordlist)));
    foreach ($candidates as $cand) {
        $h = md5($cand);
        foreach ($db['users_md5'] as $u) {
            if ($h === $u['hash']) {
                $results[] = "{$u['username']} : {$cand}  (md5={$h})";
            }
        }
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: hash di bawah ini dibuat dengan <code>md5($password)</code> &mdash; tanpa
salt, tanpa iterasi (bukan bcrypt/Argon2). MD5 didesain untuk <em>cepat</em> dihitung (awalnya
untuk checksum, bukan password), dan tanpa salt, hash yang sama akan selalu dihasilkan dari
password yang sama di akun mana pun &mdash; inilah kenapa <em>rainbow table</em>/lookup table
online (mis. CrackStation) bisa membalikkan MD5 dalam hitungan detik untuk password umum.
Tool "crack" sederhana di bawah mensimulasikan dictionary attack: masukkan daftar kata sandi
yang mungkin (satu per baris), server menghitung MD5 tiap kandidat lalu membandingkannya dengan
hash yang bocor.</p>
<p class="hint">Di dunia nyata, gunakan <code>hashcat -m 0 hashes.txt rockyou.txt</code> (mode 0
= raw MD5) untuk mencoba jutaan kandidat per detik.</p>
</details>

<h3>Dump tabel <code>users</code> (kolom password sudah "diamankan" dengan MD5)</h3>
<table class="data-table">
<tr><th>Username</th><th>MD5 Hash</th></tr>
<?php foreach ($db['users_md5'] as $u): ?>
<tr><td><?php echo htmlspecialchars($u['username']); ?></td><td><code><?php echo htmlspecialchars($u['hash']); ?></code></td></tr>
<?php endforeach; ?>
</table>

<h3>Dictionary attack tool</h3>
<form method="post">
  <label>Wordlist (satu kandidat password per baris)</label><br>
  <textarea name="wordlist" rows="8" style="width:100%;"><?php echo htmlspecialchars($wordlist); ?></textarea><br>
  <button type="submit">Crack Hashes</button>
</form>

<?php if ($results): ?>
<div class="result-box"><?php echo htmlspecialchars(implode("\n", $results)); ?></div>
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
<div class="error-box">Tidak ada match di wordlist ini &mdash; coba tambahkan kandidat lain.</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
