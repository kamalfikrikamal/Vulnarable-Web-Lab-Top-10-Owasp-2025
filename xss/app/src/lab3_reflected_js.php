<?php
$title = 'Lab 3: Reflected XSS (JavaScript context)';
$name = $_GET['name'] ?? 'Guest';
// naive escaping of double quotes only - deliberately incomplete
$name_js = str_replace('"', '\\"', $name);
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: parameter <code>name</code> disisipkan ke dalam string JavaScript inline.
Aplikasi hanya meng-escape tanda kutip ganda (<code>"</code>), sehingga kamu bisa menutup string
dengan cara lain lalu menyisipkan kode. Coba:
<code>?name=&lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt;</code> (menutup tag script
secara langsung di level parser HTML, sebelum sempat jadi JS) atau gunakan backslash untuk
menetralkan escaping kutip: <code>?name=\";alert(1);//</code> (backslash yang mendahului kutip
membuat hasil escape jadi <code>\\"</code>, yaitu string berisi satu backslash, sehingga kutip
berikutnya tetap menutup string lebih awal dari yang diharapkan).</p>
</details>

<form method="get">
  <label>Your name</label><br>
  <input type="text" name="name" value="">
  <button type="submit">Greet me</button>
</form>

<div id="greeting" class="result-box"></div>

<script>
  // VULNERABLE: user input concatenated directly into a JS string literal
  var userName = "<?php echo $name_js; ?>";
  document.getElementById('greeting').textContent = "Hello, " + userName + "!";
</script>

<?php include 'footer.php'; ?>
