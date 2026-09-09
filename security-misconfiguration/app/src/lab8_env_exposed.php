<?php
$title = 'Lab 8: File .env Ter-expose';
require_once __DIR__ . '/lib.php';
$db = load_db();
include 'header.php';
?>

<p>Banyak framework modern (Laravel, Symfony, Node.js/dotenv, dst) menyimpan seluruh konfigurasi
sensitif — kredensial database, API key, application secret — di satu file bernama
<code>.env</code> di root aplikasi. File ini <strong>wajib</strong> berada di luar webroot atau
diblokir eksplisit dari akses HTTP, tapi server ini tidak melakukan keduanya: <code>.env</code>
ikut ter-deploy persis di root aplikasi, dan tidak ada aturan apa pun yang memblokir akses ke
file berawalan titik (dotfile).</p>

<h3>Buktikan</h3>
<pre class="result-box">curl -s http://localhost:8079/secmisconfig/.env</pre>
<p>Atau buka langsung: <a href=".env" target="_blank" rel="noopener">.env</a></p>

<p class="hint">Perhatikan isinya: kredensial database production, application key, dan password
SMTP semuanya terbaca mentah-mentah dalam satu request tanpa autentikasi apa pun.</p>

<details class="hint-box">
<summary>Kenapa ini terjadi</summary>
<p class="hint">Apache (dan banyak web server lain) secara default <strong>tidak</strong>
memblokir akses ke file/folder berawalan titik kecuali dikonfigurasi eksplisit — mis.
<code>&lt;FilesMatch "^\."&gt; Require all denied &lt;/FilesMatch&gt;</code>. Developer yang
terbiasa dengan framework yang otomatis menaruh <code>.env</code> di luar
<code>public/</code>/webroot (mis. Laravel) bisa lupa bahwa aturan itu tidak berlaku otomatis
kalau deployment-nya manual/berbeda struktur.</p>
</details>

<?php include 'footer.php'; ?>
