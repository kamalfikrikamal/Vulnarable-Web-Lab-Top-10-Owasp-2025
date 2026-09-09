<?php
$title = 'Lab 8: Lockfile Diabaikan Saat Build';
require_once __DIR__ . '/lib.php';
$db = load_db();
include 'header.php';

// Lockfile "menjanjikan" versi + hash yang aman & sudah direview.
$lockfile = [
    'package' => 'acme-payment-sdk',
    'version' => '3.4.0',
    'hash' => 'sha256-2f8a1c...pinned-and-reviewed',
];

// Registry publik saat ini punya versi lebih baru yang ternyata sudah disusupi
// (mensimulasikan insiden nyata seperti event-stream 2018 / ua-parser-js 2021).
$registry_latest = [
    'version' => '3.5.1',
    'hash' => 'sha256-9d0b77...NOT reviewed, published 2 hari lalu',
    'compromised' => true,
    'payload' => "postinstall: mengirim environment variable & kredensial cloud provider (AWS/GCP) yang ditemukan di CI runner ke http://attacker.example/exfil",
];

$build_log = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode'])) {
    $lines = [];
    if ($_POST['mode'] === 'frozen') {
        $lines[] = 'npm ci  (setara "npm install --frozen-lockfile" / "composer install --no-dev --prefer-lock")';
        $lines[] = "Membaca package-lock.json...";
        $lines[] = "Dependency \"acme-payment-sdk\" dikunci ke versi {$lockfile['version']} (hash {$lockfile['hash']})";
        $lines[] = "Mengambil versi {$lockfile['version']} dari registry, verifikasi hash... COCOK.";
        $lines[] = "Build selesai. Versi yang terpasang: {$lockfile['version']} (persis sesuai lockfile, sudah direview tim).";
    } else {
        $lines[] = 'npm install  (TANPA --frozen-lockfile - lockfile diabaikan/di-update otomatis)';
        $lines[] = "Mengecek registry untuk versi terbaru \"acme-payment-sdk\"...";
        $lines[] = "Ditemukan versi {$registry_latest['version']} (lebih baru dari yang ada di lockfile).";
        $lines[] = "Menginstall {$registry_latest['version']} dan MENIMPA lockfile lama tanpa konfirmasi apa pun.";
        $lines[] = "";
        $lines[] = "[!] Versi {$registry_latest['version']} ternyata belum pernah direview tim - baru dipublikasikan 2 hari lalu.";
        $lines[] = "[!] " . $registry_latest['payload'];
    }
    $build_log = implode("\n", $lines);
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: bandingkan hasil dua cara menjalankan "build" yang sama-sama umum dipakai
tim development. Perhatikan versi paket & apa yang benar-benar terjadi di masing-masing log.</p>
</details>

<p><code>package-lock.json</code>/<code>composer.lock</code> mengunci dependency ke versi &amp;
hash yang spesifik &mdash; itulah gunanya lockfile: memastikan versi yang sudah direview tim
adalah versi yang benar-benar terpasang, di mesin siapa pun, kapan pun build dijalankan. Tapi
lockfile ini cuma efektif kalau proses build benar-benar MENEGAKKANNYA.</p>

<div class="creds-box">
<strong>Isi lockfile saat ini:</strong> <code>acme-payment-sdk</code> versi
<code><?php echo htmlspecialchars($lockfile['version']); ?></code>, hash
<code><?php echo htmlspecialchars($lockfile['hash']); ?></code> — versi ini sudah direview &amp;
disetujui tim security.
</div>

<form method="post" style="display:inline-block; margin-right:10px;">
  <input type="hidden" name="mode" value="frozen">
  <button type="submit">Build dengan lockfile ditegakkan (npm ci / --frozen-lockfile)</button>
</form>
<form method="post" style="display:inline-block;">
  <input type="hidden" name="mode" value="loose">
  <button type="submit">Build biasa (npm install, lockfile diabaikan)</button>
</form>

<?php if ($build_log !== null): ?>
<div class="result-box"><?php echo htmlspecialchars($build_log); ?></div>
<?php endif; ?>

<p class="hint">Kenapa berhasil: <code>npm install</code> (tanpa flag <code>--frozen-lockfile</code>,
atau setara <code>composer update</code> alih-alih <code>composer install</code>) diizinkan
mengambil versi TERBARU yang tersedia di registry dan menimpa lockfile begitu saja — abai
terhadap fakta bahwa versi yang sudah direview tim adalah versi yang tertulis di lockfile, bukan
"versi terbaru apa pun yang kebetulan ada saat build dijalankan". Perintah seperti
<code>npm ci</code>/<code>--frozen-lockfile</code> sengaja dirancang untuk MENOLAK build sama
sekali kalau lockfile tidak cocok dengan dependency yang diminta, alih-alih diam-diam
memperbaruinya. CI/CD pipeline yang tidak secara eksplisit memakai mode "frozen" ini kehilangan
seluruh manfaat lockfile-nya, persis seperti tidak pernah memasang lockfile sama sekali.</p>

<?php include 'footer.php'; ?>
