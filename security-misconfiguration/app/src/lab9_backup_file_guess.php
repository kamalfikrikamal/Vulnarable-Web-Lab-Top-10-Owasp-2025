<?php
$title = 'Lab 9: Backup File Editor yang Bisa Ditebak';
require_once __DIR__ . '/lib.php';
$db = load_db();
include 'header.php';
?>

<p>Developer mengedit <code>config.php</code> langsung di server production lewat editor
teks (mis. gedit/nano) untuk perbaikan cepat. Beberapa text editor otomatis membuat file
"recovery"/backup di sebelah file aslinya dengan akhiran seperti <code>.save</code>,
<code>.bak</code>, <code>.orig</code>, atau <code>~</code> — dan file itu tidak pernah dihapus
setelah selesai edit.</p>

<p>Berbeda dari Lab 3 (directory listing): folder ini <strong>tidak</strong> bisa dijelajahi
(tidak ada <code>Options +Indexes</code>). File backup-nya harus ditebak namanya — persis seperti
teknik <em>content discovery</em> nyata memakai wordlist ekstensi umum
(<code>ffuf</code>/<code>gobuster</code> dengan wordlist <code>.bak</code>, <code>.save</code>,
<code>.old</code>, <code>~</code>, dst, dijalankan terhadap setiap nama file PHP yang sudah
diketahui ada).</p>

<h3>Coba tebak</h3>
<pre class="result-box">curl -s http://localhost:8079/secmisconfig/config.php.save</pre>
<p class="hint">Karena ekstensinya <code>.save</code> (bukan <code>.php</code>), Apache tidak
menjalankannya sebagai PHP — isinya dikirim mentah-mentah sebagai teks biasa, termasuk source
code lengkap beserta kredensial database yang di-hardcode di dalamnya. Bandingkan dengan
<code>config.php</code> aslinya (kalau ada) yang akan tetap dieksekusi sebagai PHP dan tidak
pernah membocorkan source code-nya sendiri lewat cara ini.</p>

<details class="hint-box">
<summary>Kenapa ini berbahaya walau "cuma nebak nama file"</summary>
<p class="hint">Pola nama backup editor sangat terbatas dan dikenal luas — tidak butuh brute
force acak, cukup coba beberapa akhiran umum untuk setiap file penting yang sudah diketahui
namanya (mis. <code>config.php</code>, <code>settings.php</code>, <code>wp-config.php</code>).
Scanner otomatis (Nikto, ffuf dengan wordlist) melakukan ini dalam hitungan detik.</p>
</details>

<?php include 'footer.php'; ?>
