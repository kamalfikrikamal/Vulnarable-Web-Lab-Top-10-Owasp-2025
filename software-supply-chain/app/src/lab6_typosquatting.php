<?php
$title = 'Lab 6: Typosquatting';
require_once __DIR__ . '/lib.php';
$db = load_db();
include 'header.php';

// Katalog "registry publik" untuk lab ini - satu paket resmi & dua lookalike
// yang didaftarkan attacker dengan nama nyaris identik (deskripsi & versi
// sengaja disalin mirip supaya susah dibedakan sekilas).
$catalog = [
    'corp-http-client' => [
        'official' => true,
        'downloads' => '842K',
        'desc' => 'HTTP client resmi tim Platform Corp untuk memanggil API internal.',
        'run_log' => 'Menjalankan corp-http-client@2.1.0... OK, tidak ada aksi mencurigakan.',
    ],
    'corp-http-cIient' => [ // huruf 'l' diganti 'I' kapital
        'official' => false,
        'downloads' => '1.1M',
        'desc' => 'HTTP client resmi tim Platform Corp untuk memanggil API internal.',
        'run_log' => "Menjalankan corp-http-cIient@2.1.0...\n[!] postinstall: mengirim seluruh environment variable proses ke http://attacker.example/collect\n[!] postinstall: mencari & mengunggah file *.pem, *.env, id_rsa yang ditemukan di direktori project",
    ],
    'corp_http_client' => [ // hyphen diganti underscore
        'official' => false,
        'downloads' => '390K',
        'desc' => 'HTTP client resmi tim Platform Corp untuk memanggil API internal (fork terbaru, lebih cepat).',
        'run_log' => "Menjalankan corp_http_client@2.1.1...\n[!] postinstall: menyisipkan reverse shell ke attacker.example:4444 di setiap request yang dikirim lewat client ini",
    ],
];

$installed = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pkg_name'])) {
    $name = trim((string)$_POST['pkg_name']);
    if (isset($catalog[$name])) {
        $installed = ['name' => $name] + $catalog[$name];
        $db['typosquat_install_log'][] = ['name' => $name, 'official' => $catalog[$name]['official'], 'time' => date('H:i:s')];
        save_db($db);
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: cari & "install" package HTTP client resmi tim Platform Corp lewat form
pencarian di bawah. Perhatikan baik-baik SETIAP karakter di nama paket sebelum menekan Install -
ada dua paket lookalike di katalog ini yang sengaja dibuat nyaris tidak bisa dibedakan sekilas
dari yang asli.</p>
</details>

<h3>1. "Quick install" dari tutorial internal (copy-paste)</h3>
<p>Potongan command ini disalin dari sebuah halaman wiki internal/tutorial blog - seperti
kebanyakan insiden typosquatting nyata, korban jarang mengetik nama paket manual, mereka
copy-paste dari suatu tempat tanpa mengecek ulang setiap karakternya:</p>
<pre class="result-box">corp-install corp-http-cIient</pre>
<form method="post">
  <input type="hidden" name="pkg_name" value="corp-http-cIient">
  <button type="submit">Jalankan command di atas apa adanya</button>
</form>

<h3>2. Atau cari manual &amp; pilih sendiri</h3>
<table class="data-table">
<tr><th>Nama paket</th><th>Downloads</th><th>Deskripsi</th><th></th></tr>
<?php foreach ($catalog as $name => $pkg): ?>
<tr>
  <td><code><?php echo htmlspecialchars($name); ?></code><?php if ($pkg['official']): ?> <span class="badge user">verified publisher</span><?php endif; ?></td>
  <td><?php echo htmlspecialchars($pkg['downloads']); ?></td>
  <td><?php echo htmlspecialchars($pkg['desc']); ?></td>
  <td>
    <form method="post" style="margin:0;">
      <input type="hidden" name="pkg_name" value="<?php echo htmlspecialchars($name); ?>">
      <button type="submit">Install</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</table>

<?php if ($installed): ?>
<h4>Hasil instalasi <code><?php echo htmlspecialchars($installed['name']); ?></code></h4>
<div class="<?php echo $installed['official'] ? 'ok-box' : 'error-box'; ?>"><?php echo nl2br(htmlspecialchars($installed['run_log'])); ?></div>
<?php endif; ?>

<?php if (!empty($db['typosquat_install_log'])): ?>
<h4>Riwayat install di lab ini</h4>
<ul class="file-list">
  <?php foreach (array_reverse($db['typosquat_install_log']) as $entry): ?>
    <li><?php echo htmlspecialchars($entry['time']); ?> — <code><?php echo htmlspecialchars($entry['name']); ?></code> — <?php echo $entry['official'] ? 'paket resmi' : '<strong>PAKET TYPOSQUAT</strong>'; ?></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

<p class="hint">Kenapa berhasil: attacker mendaftarkan paket dengan nama yang secara visual nyaris
identik dengan paket populer/resmi — mengganti huruf yang mirip (<code>l</code> jadi
<code>I</code>), tanda pemisah (<code>-</code> jadi <code>_</code>), atau menambah/menghapus satu
huruf. Registry publik pada umumnya tidak memverifikasi bahwa nama yang didaftarkan "milik"
organisasi tertentu, dan jumlah download bisa dipalsukan/di-inflate untuk terlihat lebih
terpercaya. Korban paling sering terkena bukan karena sengaja salah ketik, tapi karena
copy-paste command dari tutorial, dokumentasi pihak ketiga, atau bahkan hasil AI code assistant
yang berhalusinasi nama paket yang mirip tapi tidak pernah benar-benar ada.</p>

<?php include 'footer.php'; ?>
