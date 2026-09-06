<?php
$title = 'Lab 2: Reflected XSS (HTML attribute)';
$color = $_GET['color'] ?? 'blue';
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: parameter <code>color</code> ditaruh di dalam atribut
<code>value="..."</code> sebuah input, dan dikutip dengan tanda kutip ganda. Kamu harus keluar
dari atribut dulu sebelum bisa menyisipkan tag/handler baru. Coba:
<code>?color="&gt;&lt;script&gt;alert(1)&lt;/script&gt;</code> atau lebih rapi:
<code>?color=" onmouseover="alert(1)</code> lalu arahkan mouse ke kotak input tersebut.</p>
</details>

<form method="get">
  <label>Favorite color</label><br>
  <!-- VULNERABLE: value attribute built by string concatenation, no htmlspecialchars() -->
  <input type="text" name="color" value="<?php echo $color; ?>">
  <button type="submit">Save</button>
</form>

<?php include 'footer.php'; ?>
