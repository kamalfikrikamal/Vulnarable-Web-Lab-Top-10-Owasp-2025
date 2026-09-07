<?php
$title = 'Lab 3: Data Sensitif Tidak Di-mask';
require_once __DIR__ . '/lib.php';
$db = load_db();
$user = find_user($db, current_username());
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: kodebase aplikasi ini SUDAH punya fungsi <code>mask_card()</code> yang
benar (lihat "Halaman Profil" di bawah, kartu tampil sebagai <code>************1234</code>).
Tapi endpoint "Riwayat Transaksi" di bawahnya lupa memanggil fungsi itu, dan menampilkan nomor
kartu lengkap apa adanya di response &mdash; kesalahan konsistensi yang sangat umum terjadi
ketika masking diterapkan per-endpoint secara manual alih-alih dipaksakan di satu lapisan
serialisasi terpusat.</p>
</details>

<h3>Halaman Profil (benar - sudah di-mask)</h3>
<table class="data-table">
<tr><th>Username</th><td><?php echo htmlspecialchars($user['username']); ?></td></tr>
<tr><th>Kartu tersimpan</th><td><?php echo htmlspecialchars(mask_card($user['card'])); ?></td></tr>
</table>

<h3>Riwayat Transaksi (bug - tidak di-mask)</h3>
<table class="data-table">
<tr><th>Tanggal</th><th>Deskripsi</th><th>Kartu yang dipakai</th></tr>
<tr><td>2026-09-01</td><td>Pembelian online</td><td><?php echo htmlspecialchars($user['card']); ?></td></tr>
<tr><td>2026-08-15</td><td>Langganan bulanan</td><td><?php echo htmlspecialchars($user['card']); ?></td></tr>
</table>

<div class="error-box">Nomor kartu lengkap (<?php echo htmlspecialchars($user['card']); ?>) tampil penuh di riwayat transaksi, padahal halaman profil di atas sudah benar melakukan masking untuk data yang sama.</div>

<?php include 'footer.php'; ?>
