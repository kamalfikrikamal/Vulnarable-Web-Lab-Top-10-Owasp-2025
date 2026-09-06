<?php
$title = 'Lab 5: DOM-based XSS';
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: JavaScript di halaman ini membaca <code>location.hash</code> (bagian
setelah <code>#</code> di URL, yang <strong>tidak pernah dikirim ke server</strong>) dan
menulisnya langsung ke <code>innerHTML</code>. Ini murni client-side, server tidak melihat
payload sama sekali. Coba buka:
<code>lab5_dom_xss.php#&lt;img src=x onerror=alert(document.domain)&gt;</code></p>
</details>

<form onsubmit="return false;">
  <label>Search (client-side only, uses #hash)</label><br>
  <input type="text" id="searchBox" placeholder="type then press Go">
  <button onclick="goSearch()">Go</button>
</form>

<div id="output" class="result-box"></div>

<script>
  function goSearch() {
    var val = document.getElementById('searchBox').value;
    location.hash = val;
  }

  function renderFromHash() {
    var hash = decodeURIComponent(location.hash.substring(1));
    if (hash) {
      // VULNERABLE: untrusted client-side data written straight into innerHTML
      document.getElementById('output').innerHTML = 'You searched for: ' + hash;
    }
  }

  window.addEventListener('hashchange', renderFromHash);
  renderFromHash();
</script>

<?php include 'footer.php'; ?>
