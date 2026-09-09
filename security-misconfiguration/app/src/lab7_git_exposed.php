<?php
$title = 'Lab 7: Folder .git Ter-expose';
require_once __DIR__ . '/lib.php';
$db = load_db();
include 'header.php';
?>

<p>Tim marketing minta dibuatkan landing page kecil terpisah dari aplikasi utama. Developer
membuatnya di laptop dengan <code>git init</code> seperti biasa, lalu men-deploy dengan cara
paling cepat: menyalin (<code>rsync</code>/<code>scp</code>) seluruh folder kerja apa adanya ke
server — termasuk folder <code>.git</code> yang seharusnya tidak pernah ikut ke production.</p>

<p>Subsite-nya ada di <a href="vcs-demo/" target="_blank" rel="noopener"><code>vcs-demo/</code></a>.
Buka dulu halamannya, lalu coba akses folder <code>.git</code> di dalamnya secara langsung.</p>

<h3>1. Konfirmasi .git bisa diakses</h3>
<pre class="result-box">curl -s http://localhost:8079/secmisconfig/vcs-demo/.git/HEAD
curl -s http://localhost:8079/secmisconfig/vcs-demo/.git/config</pre>
<p class="hint">Kalau responsnya berisi <code>ref: refs/heads/main</code> dan config git yang valid,
berarti seluruh riwayat commit repo ini — termasuk apa pun yang <em>pernah</em> di-commit, bahkan
yang sudah dihapus di commit berikutnya — bisa direkonstruksi dari sini.</p>

<h3>2. Unduh seluruh isi .git (directory listing aktif)</h3>
<pre class="result-box">mkdir -p recon && cd recon
wget -q -r -np -nH --cut-dirs=2 -R "index.html*" \
  http://localhost:8079/secmisconfig/vcs-demo/.git/</pre>
<p class="hint">Karena directory listing aktif di folder ini, <code>wget -r</code> bisa
mengunduh seluruh isi <code>.git/</code> (refs, objects, dst) tanpa perlu tool khusus seperti
<code>git-dumper</code>. <code>--cut-dirs=2</code> membuang dua komponen path
(<code>secmisconfig/</code> dan <code>vcs-demo/</code>) supaya hasil unduhan langsung jadi
folder <code>.git/</code> di direktori kerja saat ini.</p>

<h3>3. Baca riwayat commit dari .git yang sudah diunduh</h3>
<pre class="result-box">cd recon
git log --all -p</pre>
<p class="hint"><code>git log -p</code> jalan langsung selama ada folder <code>.git</code> di
direktori saat ini — tidak butuh working tree yang di-checkout. Perhatikan commit
"WIP: nyimpen config db buat testing" dan commit sesudahnya yang menghapusnya: isi file yang
"dihapus" itu tetap utuh terlihat di diff-nya.</p>

<details class="hint-box">
<summary>Kenapa ini bisa terjadi</summary>
<p class="hint">Git menyimpan seluruh riwayat sebagai <em>object</em> yang di-<em>content-address</em>
(hash) — menghapus file di commit baru cuma membuat snapshot baru tanpa file itu, bukan
menghapus object lamanya dari <code>.git/objects/</code>. Selama folder <code>.git</code> bisa
diakses siapa saja, seluruh riwayat itu ikut bisa dibaca siapa saja juga — apa pun yang pernah
di-commit, walau cuma sesaat, harus dianggap bocor permanen (rotate credential-nya, jangan cuma
hapus filenya).</p>
</details>

<?php include 'footer.php'; ?>
