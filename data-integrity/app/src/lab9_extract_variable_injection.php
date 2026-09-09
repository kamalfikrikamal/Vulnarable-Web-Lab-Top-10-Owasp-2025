<?php
require_once __DIR__ . '/lib.php';
$title = 'Lab 9: Variable Injection Lewat extract()';

// Variabel-variabel ini SEHARUSNYA cuma bisa datang dari sumber tepercaya di
// server (sesi login, database) - bukan dari request pengguna sama sekali.
$is_admin = false;
$account_balance = 0;
$username = 'guest';

// VULNERABLE: extract() menulis SETIAP key di $_GET langsung jadi variabel PHP
// bernama sama di scope ini - termasuk MENIMPA $is_admin, $account_balance,
// dan $username yang barusan diinisialisasi dengan aman di atas. Developer
// bermaksud memakainya cuma untuk kenyamanan (mis. `?theme=dark` jadi
// `$theme`), tapi tidak ada allowlist nama variabel mana yang boleh
// ditimpa - SEMUA nama variabel di scope ini rentan.
extract($_GET);

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: buat halaman ini menampilkan status "ADMIN" dan saldo akun yang kamu
tentukan sendiri, padahal kedua variabel itu diinisialisasi aman
(<code>$is_admin = false; $account_balance = 0;</code>) sebelum kode di bawahnya berjalan. Coba
tambahkan parameter URL yang namanya PERSIS SAMA dengan nama variabel PHP yang ingin kamu timpa.</p>
<pre class="result-box">?is_admin=1&amp;account_balance=999999999&amp;username=SUPERUSER</pre>
</details>

<h3>Profil Akun</h3>
<table class="data-table">
<tr><th>Username</th><td><?php echo htmlspecialchars((string)$username); ?></td></tr>
<tr><th>Role</th><td><?php echo $is_admin ? '<span class="badge admin">ADMIN</span>' : '<span class="badge user">user biasa</span>'; ?></td></tr>
<tr><th>Saldo</th><td><?php echo rupiah((float)$account_balance); ?></td></tr>
</table>

<?php if ($is_admin): ?>
<div class="error-box">⚠ Status ADMIN &amp; saldo di atas datang murni dari parameter URL yang
kamu kirim sendiri, bukan dari sesi login atau database mana pun.</div>
<?php endif; ?>

<p class="hint">Kenapa berhasil: <code>extract($_GET)</code> mengubah SETIAP pasangan
key-value di query string menjadi variabel PHP di scope saat ini, tanpa allowlist nama variabel
mana yang "aman" untuk ditimpa. Ini menghancurkan integritas internal skrip itu sendiri —
variabel yang seharusnya cuma bisa diisi dari logika server (status admin, saldo akun) jadi bisa
ditentukan langsung oleh siapa pun yang mengirim request, hanya dengan menebak/mengetahui nama
variabelnya. Bug class ini historisnya terkenal lewat kasus <code>register_globals</code> di PHP
lama (fitur bawaan yang otomatis melakukan hal serupa untuk SEMUA input, dihapus total di PHP 5.4
karena masalah keamanan ini) — <code>extract()</code> tanpa flag pembatas
(<code>EXTR_SKIP</code> dengan variabel yang sudah didefinisikan, atau lebih baik: jangan pernah
extract data eksternal sama sekali) menghidupkan kembali masalah yang persis sama secara manual.</p>

<?php include 'footer.php'; ?>
