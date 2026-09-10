<?php
$title = 'Lab 6: User Bisa Menghapus Log Audit Sendiri';
include 'header.php';

if (!isset($db['audit_log'])) $db['audit_log'] = [];

$deleted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_riwayat'])) {
    // VULNERABLE: "Hapus Riwayat Aktivitas Saya" dikemas sebagai fitur privasi
    // yang tidak berbahaya, tapi implementasinya benar-benar menghapus baris
    // audit_log yang SAMA yang dipakai tim SOC untuk investigasi keamanan.
    // Tidak ada tabel/penyimpanan terpisah untuk "tampilan user" vs "log audit".
    $db['audit_log'] = [];
    save_db($db);
    $deleted = true;
}

$view = $_GET['view'] ?? 'user';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman "Riwayat Aktivitas Saya" di bawah menampilkan riwayat aktivitas akun
demo <code>budi</code>, lengkap dengan tombol "Hapus Semua Riwayat" &mdash; terlihat seperti fitur
privasi/kerapihan yang wajar ("bersihkan histori saya"). Bandingkan dengan tab
"Admin: Log Investigasi (SOC)" &mdash; keduanya membaca sumber data yang <strong>persis sama</strong>.</p>
<p class="hint">Klik "Hapus Semua Riwayat" di tab user, lalu buka tab SOC &mdash; log investigasi
keamanan yang seharusnya jadi bukti forensik ikut lenyap, dihapus oleh pengguna biasa (atau
penyerang yang sudah mengambil alih akun user tersebut) tanpa hak akses admin sama sekali.</p>
</details>

<p>
  <a href="?view=user" class="<?php echo $view === 'user' ? '' : 'btn'; ?>"><?php echo $view === 'user' ? '<strong>Riwayat Aktivitas Saya</strong>' : 'Riwayat Aktivitas Saya'; ?></a>
  &nbsp;|&nbsp;
  <a href="?view=soc" class="<?php echo $view === 'soc' ? '' : 'btn'; ?>"><?php echo $view === 'soc' ? '<strong>Admin: Log Investigasi (SOC)</strong>' : 'Admin: Log Investigasi (SOC)'; ?></a>
</p>

<?php if ($view === 'soc'): ?>

<h3>Admin: Log Investigasi (SOC)</h3>
<p class="hint">Ini adalah tampilan tim SOC/investigator terhadap <code>audit_log</code> &mdash;
data keamanan yang dipakai untuk merekonstruksi kejadian saat ada insiden. <strong>Ini tabel data
yang sama persis</strong> dengan yang ditampilkan di "Riwayat Aktivitas Saya", cuma dilabeli
berbeda.</p>
<?php if (empty($db['audit_log'])): ?>
<div class="error-box">audit_log KOSONG. Tidak ada satu pun entri investigasi yang tersisa &mdash;
seluruh jejak aktivitas keamanan akun ini sudah hilang.</div>
<?php else: ?>
<table class="data-table">
  <tr><th>ID</th><th>Waktu</th><th>User</th><th>Event</th></tr>
  <?php foreach ($db['audit_log'] as $entry): ?>
  <tr>
    <td><?php echo (int)$entry['id']; ?></td>
    <td><?php echo htmlspecialchars($entry['ts']); ?></td>
    <td><?php echo htmlspecialchars($entry['user']); ?></td>
    <td><?php echo htmlspecialchars($entry['event']); ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php else: ?>

<h3>Riwayat Aktivitas Saya</h3>
<p class="hint">Fitur privasi: hapus histori aktivitas akunmu kalau kamu merasa tidak perlu
disimpan lagi.</p>

<?php if ($deleted): ?>
<div class="ok-box">Riwayat berhasil dihapus. (Sekarang buka tab "Admin: Log Investigasi (SOC)" di
atas dan lihat apa yang terjadi di sana.)</div>
<?php endif; ?>

<?php if (empty($db['audit_log'])): ?>
<p><em>Belum ada riwayat aktivitas (atau sudah dihapus).</em></p>
<?php else: ?>
<table class="data-table">
  <tr><th>Waktu</th><th>Aktivitas</th></tr>
  <?php foreach ($db['audit_log'] as $entry): ?>
  <tr>
    <td><?php echo htmlspecialchars($entry['ts']); ?></td>
    <td><?php echo htmlspecialchars($entry['event']); ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<form method="post" style="margin-top:14px;">
  <button type="submit" name="hapus_riwayat" value="1">Hapus Semua Riwayat</button>
</form>
<?php endif; ?>

<?php endif; ?>

<div class="error-box" style="margin-top:20px;">
Kenapa ini berbahaya: tombol "Hapus Semua Riwayat" terlihat seperti fitur privasi yang wajar,
tapi di balik layar ia menjalankan <code>unset</code>/pengosongan langsung terhadap tabel
<code>audit_log</code> &mdash; tabel yang sama yang dipakai tim keamanan untuk investigasi.
Artinya pengguna biasa (atau penyerang yang berhasil mengambil alih satu akun user) bisa
menghapus jejak aktivitasnya sendiri sebelum tim SOC sempat menyelidikinya &mdash; padahal log
audit/keamanan seharusnya tidak pernah bisa dihapus lewat izin level-user biasa.
</div>

<?php include 'footer.php'; ?>
