<?php
$title = 'Lab 4: Fail-Open di Catch Block';
include 'header.php';

/**
 * Pengecekan kepemilikan laporan yang "sebenarnya" - membandingkan owner laporan dengan
 * user yang sedang login. Parameter di-type-hint sebagai int: untuk report_id numerik biasa
 * (mis. "2" dari query string) PHP otomatis meng-coerce ke int dengan aman. TAPI kalau
 * report_id berupa string non-numerik ("abc") atau array (dari "report_id[]=1"), PHP TIDAK
 * BISA meng-coerce-nya ke int - ini melempar TypeError di titik pemanggilan, SEBELUM baris
 * pengecekan kepemilikan di bawah ini sempat jalan sama sekali.
 */
function user_can_access_report(int $report_id): bool {
    $db = load_db();
    $report = find_report_by_id($db, $report_id);
    if (!$report) return false;
    return $report['owner'] === CURRENT_USER;
}

$report_id_raw = $_GET['report_id'] ?? null;
$can_access = false;
$checked = false;
$report = null;
$type_error_msg = '';

if ($report_id_raw !== null) {
    $checked = true;
    try {
        $can_access = user_can_access_report($report_id_raw);
    } catch (\Throwable $e) {
        // BUG (fail-open): kalau pengecekan akses sendiri melempar error tak terduga,
        // seharusnya akses DITOLAK (fail closed). Alih-alih, di sini malah diloloskan.
        // TODO: tangani error dengan benar nanti
        $can_access = true;
        $type_error_msg = get_class($e) . ': ' . $e->getMessage();
    }

    if ($can_access) {
        $db = load_db();
        $report = is_numeric($report_id_raw) ? find_report_by_id($db, (int)$report_id_raw) : null;
        if (!$report) {
            // report_id tidak bisa di-resolve (karena memang malformed) tapi $can_access
            // sudah kadung true - kode ini tetap lanjut menampilkan laporan sensitif sebagai
            // fallback, membuktikan akses benar-benar diberikan tanpa verifikasi kepemilikan
            // yang valid sama sekali.
            $report = find_report_by_id($db, 2); // laporan gaji admin (RAHASIA)
        }
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Kamu login sebagai <code><?php echo htmlspecialchars(CURRENT_USER); ?></code>.
Coba: (1) <code>?report_id=1</code> (laporan milik kamu sendiri, alice - harus berhasil), (2)
<code>?report_id=2</code> (laporan milik admin - harus DITOLAK, membuktikan pengecekan normal
memang bekerja), lalu (3) <code>?report_id=abc</code> atau (4) <code>?report_id[]=1</code>
(bikin <code>report_id</code> jadi array) - dua yang terakhir ini bikin fungsi pengecekan
melempar error di tengah jalan. Lihat apa yang terjadi ke keputusan akses saat itu.</p>
</details>

<div class="lab-card">
  <h3>Download Laporan</h3>
  <p>
    <a href="?report_id=1">Laporan #1 (milik alice)</a> &middot;
    <a href="?report_id=2">Laporan #2 (milik admin)</a> &middot;
    <a href="?report_id=abc">Laporan dengan report_id=abc</a> &middot;
    <a href="?report_id[]=1">Laporan dengan report_id[]=1</a>
  </p>
</div>

<?php if ($checked): ?>
  <?php if ($type_error_msg): ?>
    <div class="error-box">Exception tertangkap saat pengecekan akses: <?php echo htmlspecialchars($type_error_msg); ?>
    &mdash; karena masuk <code>catch (\Throwable $e)</code> yang fail-open, akses tetap DILOLOSKAN.</div>
  <?php endif; ?>

  <?php if ($can_access && $report): ?>
    <div class="ok-box">
      <h3><?php echo htmlspecialchars($report['title']); ?></h3>
      <p><?php echo htmlspecialchars($report['content']); ?></p>
    </div>
  <?php elseif (!$can_access): ?>
    <div class="error-box">403 Forbidden &mdash; kamu (<?php echo htmlspecialchars(CURRENT_USER); ?>) tidak memiliki akses ke laporan ini.</div>
  <?php endif; ?>
<?php endif; ?>

<p class="hint">Kenapa ini bug: <code>catch (\Throwable $e)</code> menangkap SEMUA jenis error/
exception, termasuk error yang sama sekali tidak terduga (di sini: <code>TypeError</code> akibat
tipe data input yang aneh). Menangkap semua Throwable itu sendiri bukan masalah - masalahnya
adalah fallback di dalam catch block ini meloloskan akses (<code>$can_access = true</code>)
alih-alih menolaknya. Pengecekan keamanan HARUS gagal tertutup (fail closed/deny by default): apa
pun yang terjadi tak terduga selama proses verifikasi, jalur amannya adalah menolak akses, bukan
mengizinkannya.</p>

<?php include 'footer.php'; ?>
