<?php
$title = 'Lab 7: Type Confusion Filter Bypass';
include 'header.php';

// Daftar nama yang diblokir - dipakai untuk mencegah user mengganti display name jadi nama yang
// terkesan sebagai admin/superuser (impersonation).
$BLOCKED_VALUES = ['admin', 'root', 'superuser'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['display_name'])) {
    $input = $_POST['display_name'];

    // BUG (type confusion): kode ini berasumsi $_POST['display_name'] SELALU berupa string,
    // tidak pernah divalidasi dengan is_string() dulu. PHP menerima field form apa pun sebagai
    // ARRAY kalau nama field-nya diakhiri "[]" (atau di-craft manual di request mentah) -
    // in_array($array, $BLOCKED_VALUES) TIDAK PERNAH true untuk needle berupa array dibandingkan
    // terhadap haystack berisi string-string, walaupun isi array itu PERSIS SAMA dengan salah
    // satu nilai yang diblokir. Filter ini jadi bisa dilewati begitu saja hanya dengan mengubah
    // BENTUK/TIPE input, bukan isinya.
    if (in_array($input, $BLOCKED_VALUES)) {
        $shown = is_array($input) ? implode(', ', array_map('strval', $input)) : (string)$input;
        $error = "Ditolak: nama '" . htmlspecialchars($shown) . "' termasuk daftar terlarang.";
    } else {
        $db = load_db();
        $db['profile']['display_name'] = $input; // disimpan apa adanya, bisa berupa array
        save_db($db);
        $shown = is_array($input) ? implode(', ', array_map('strval', $input)) : (string)$input;
        $message = "Display name berhasil diubah jadi '" . htmlspecialchars($shown) . "'"
            . (is_array($input) ? ' (dikirim sebagai ARRAY, bukan string biasa!)' : '') . '.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    reset_db();
    $message = 'State lab direset ke kondisi awal.';
}

$db = load_db();
$current = $db['profile']['display_name'];
$current_shown = is_array($current) ? implode(', ', array_map('strval', $current)) : (string)$current;
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Coba dulu Form A: isi <code>admin</code> lalu submit - filter menolaknya dengan
benar (bukti filter normal memang bekerja untuk input string biasa). Sekarang coba Form B: form
ini mengirim field yang SAMA (<code>display_name</code>) tapi dengan nama
<code>display_name[]</code> berisi nilai <code>admin</code> yang PERSIS SAMA - jadi PHP
menerimanya sebagai array <code>['admin']</code>, bukan string <code>'admin'</code>. Perhatikan
apakah filter <code>in_array()</code> tetap menolaknya, atau malah meloloskannya begitu saja.
Kamu juga bisa coba manual lewat curl:</p>
<pre class="result-box">curl -s -X POST http://localhost:8079/exceptcond/lab7_type_confusion_filter_bypass.php --data-urlencode "display_name[]=admin"</pre>
</details>

<div class="lab-card">
  <h3>Display Name Saat Ini</h3>
  <p><strong><?php echo htmlspecialchars($current_shown); ?></strong>
  <?php if (is_array($current)): ?><span class="badge admin">TERSIMPAN SEBAGAI ARRAY</span><?php endif; ?></p>
</div>

<div class="lab-card">
  <h3>Form A &mdash; Ganti Display Name (input string biasa)</h3>
  <form method="post">
    <label>Display Name</label><br>
    <input type="text" name="display_name" placeholder="coba: admin"><br>
    <button type="submit">Simpan</button>
  </form>
</div>

<div class="lab-card">
  <h3>Form B &mdash; Demo Bypass (field dikirim sebagai array, isi tetap "admin")</h3>
  <p class="hint">Form ini mengirim <code>display_name[]=admin</code> - nilai yang PERSIS SAMA
  dengan yang ditolak di Form A, cuma dibungkus array.</p>
  <form method="post">
    <input type="hidden" name="display_name[]" value="admin">
    <button type="submit">Kirim display_name[]=admin</button>
  </form>
</div>

<form method="post" style="margin-bottom:12px;">
  <input type="hidden" name="action" value="reset">
  <button type="submit" style="background:#7f1d1d;">Reset State Lab</button>
</form>

<?php if ($message): ?><div class="ok-box"><?php echo $message; ?></div><?php endif; ?>
<?php if ($error): ?><div class="error-box"><?php echo $error; ?></div><?php endif; ?>

<p class="hint">Daftar terlarang saat ini: <code><?php echo htmlspecialchars(implode(', ', $BLOCKED_VALUES)); ?></code></p>

<p class="hint">Kenapa ini bug: <code>in_array($needle, $haystack)</code> dengan perbandingan
default (non-strict) TIDAK PERNAH menganggap sebuah array sama dengan salah satu string di
haystack-nya, berapa pun isinya - jadi begitu <code>$_POST['display_name']</code> berbentuk array
(bukan string), filter blocklist ini otomatis selalu meloloskannya, TIDAK PEDULI apa isi array
itu. Ini bukan soal NILAI yang lolos filter (nilainya persis sama: "admin"), tapi soal TIPE input
yang tidak pernah divalidasi sebelum dicek terhadap blocklist. Kode validasi/filter seperti ini
diam-diam mengasumsikan input selalu scalar string - padahal PHP mengizinkan field request apa
pun datang sebagai array hanya dengan mengubah nama field jadi <code>nama[]</code> di form/request
mentah. Selalu panggil <code>is_string($input)</code> (dan tolak kalau bukan) SEBELUM menjalankan
input lewat logika blocklist/filter apa pun - mishandling terhadap TIPE input yang tak terduga
ini sama persis dengan mishandling terhadap kondisi tak terduga lainnya di kategori ini.</p>

<?php include 'footer.php'; ?>
