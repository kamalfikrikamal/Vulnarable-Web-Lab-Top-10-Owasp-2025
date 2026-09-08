<?php
$title = 'Lab 3: Directory Listing Terbuka';
require_once __DIR__ . '/lib.php';
$db = load_db();
include 'header.php';
?>

<p>Server web ini punya folder <code>backup/</code> di dalam webroot — biasanya ditaruh
sementara oleh admin/developer untuk menyimpan hasil dump database atau file konfigurasi lama,
lalu lupa dihapus. Yang membuatnya berbahaya: folder ini dikonfigurasi dengan
<code>Options +Indexes</code> aktif di Apache, sehingga siapa pun yang membuka URL folder
tersebut akan melihat daftar seluruh isinya secara langsung, tanpa perlu tahu nama file
spesifik apa pun.</p>

<h3>Coba buka folder backup</h3>
<p><a class="btn" href="backup/">Buka backup/</a></p>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Klik link di atas (atau akses langsung <code>/secmisconfig/backup/</code> dari
browser). Apache akan menampilkan listing folder karena <code>Options +Indexes</code> aktif
untuk direktori ini. Buka setiap file yang muncul dan perhatikan isinya — file
<code>.sql.bak</code> dan <code>.php.bak</code> sering berisi data sensitif yang seharusnya
tidak pernah bisa diakses lewat browser.</p>
</details>

<?php include 'footer.php'; ?>
