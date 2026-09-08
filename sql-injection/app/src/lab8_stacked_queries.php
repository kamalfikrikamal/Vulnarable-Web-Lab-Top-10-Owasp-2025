<?php
require 'db.php';
$title = 'Lab 8: Stacked Queries';

// Self-healing: kalau payload sebelumnya sempat men-DROP tabel ini, buat ulang di sini supaya
// lab tetap bisa dipakai berulang kali oleh peserta lain.
$mysqli->query("CREATE TABLE IF NOT EXISTS notes (id INT AUTO_INCREMENT PRIMARY KEY, text VARCHAR(255))");

$result_html = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = $_POST['note'] ?? '';

    // VULNERABLE: string concatenation DAN mysqli_multi_query() - beda dari semua lab lain di
    // aplikasi ini yang memakai query()/prepare(). multi_query() secara eksplisit mengizinkan
    // lebih dari satu statement SQL dipisah titik koma (;) dieksekusi dalam satu pemanggilan.
    $query = "INSERT INTO notes (text) VALUES ('$note')";

    if ($mysqli->multi_query($query)) {
        // Kuras semua result set yang dihasilkan (bisa lebih dari satu statement).
        do {
            if ($res = $mysqli->store_result()) {
                $res->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
        $result_html = "<div class='result-box'>Catatan diproses.\n\nQuery dieksekusi:\n" . htmlspecialchars($query) . "</div>";
    } else {
        $result_html = "<div class='error-box'>SQL Error: " . htmlspecialchars($mysqli->error) . "\n\nQuery: " . htmlspecialchars($query) . "</div>";
    }
}

$notes_html = '';
$res = $mysqli->query("SELECT id, text FROM notes ORDER BY id DESC");
if ($res) {
    $notes_html = "<table><tr><th>ID</th><th>Text</th></tr>";
    while ($row = $res->fetch_assoc()) {
        $notes_html .= "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['text']) . "</td></tr>";
    }
    $notes_html .= "</table>";
} else {
    $notes_html = "<div class='error-box'>Tabel notes tidak ditemukan (kemungkinan baru saja di-DROP oleh payload stacked query Anda). Reload halaman ini - tabelnya akan otomatis dibuat ulang (kosong).</div>";
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: fitur "catatan cepat" ini memakai <code>mysqli_multi_query()</code>, bukan
<code>query()</code>/prepared statement biasa seperti lab-lab lain. Ini berarti server benar-benar
akan menjalankan lebih dari satu statement SQL kalau Anda menutup statement <code>INSERT</code>
pertama dengan <code>')</code>, menambahkan titik koma dan statement SQL baru, lalu mengomentari
sisa query aslinya. Perhatikan tabel "notes" di bawah setelah submit - apakah muncul baris baru
yang tidak Anda ketik langsung di form?</p>
</details>

<form method="post">
  <label>Catatan</label><br>
  <input type="text" name="note" value="" style="width:420px;"><br>
  <button type="submit">Simpan Catatan</button>
</form>

<?php echo $result_html; ?>

<h3>Isi tabel notes saat ini</h3>
<?php echo $notes_html; ?>

<?php include 'footer.php'; ?>
