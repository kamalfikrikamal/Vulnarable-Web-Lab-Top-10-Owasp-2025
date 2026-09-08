<?php
$title = 'Lab 1: Prototype Pollution';
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman ini memuat utilitas "merge/extend" bergaya library populer
(mensimulasikan lodash <code>merge()</code> versi lama, sebelum 4.17.5 &mdash;
<strong>CVE-2018-3721</strong>) yang deep-merge dua object secara rekursif TANPA memblokir key
istimewa seperti <code>__proto__</code>. Kalau kamu bisa membuat aplikasi men-<em>deep-merge</em>
JSON yang kamu kontrol ke dalam object apa pun, kamu bisa menambahkan properti ke
<code>Object.prototype</code> &mdash; dan properti itu otomatis "muncul" di SEMUA object polos
<code>{}</code> di seluruh halaman (bahkan yang belum pernah dibuat saat payload dikirim).</p>
<p class="hint">Coba payload ini di kotak merge di bawah, lalu klik "Terapkan Merge":</p>
<div class="result-box">{"__proto__":{"isAdmin":true}}</div>
<p class="hint">Perhatikan panel "Admin" di bawah &mdash; sebelumnya terkunci, dan dicek dengan
cara yang sepertinya tidak berhubungan sama sekali dengan form merge di atas.</p>
</details>

<h3>Utilitas merge (versi vulnerable, ter-bundle di halaman ini)</h3>
<p>Kode JS di bawah ini persis mendemonstrasikan bug-nya: deep-merge rekursif biasa, tidak ada
pengecekan <code>key === '__proto__' || key === 'constructor' || key === 'prototype'</code> sama
sekali sebelum menulis ke target.</p>
<div class="result-box">function vulnerableMerge(target, source) {
  for (var key in source) {
    if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
      if (typeof target[key] !== 'object' || target[key] === null) target[key] = {};
      vulnerableMerge(target[key], source[key]); // rekursif, tanpa filter key
    } else {
      target[key] = source[key]; // langsung ditulis, tanpa validasi
    }
  }
  return target;
}</div>

<h3>Coba sendiri</h3>
<label>Payload JSON yang akan di-merge (dipanggil sebagai <code>vulnerableMerge({}, JSON.parse(payload))</code>)</label><br>
<textarea id="payload" rows="4" style="width:100%; max-width:600px;">{"__proto__":{"isAdmin":true}}</textarea><br>
<button type="button" onclick="applyMerge()">Terapkan Merge</button>
<button type="button" onclick="location.reload()">Reset Halaman</button>

<h3>Bukti (proof) hasil merge</h3>
<p>Object baru <code>{}</code> di bawah ini <strong>tidak pernah disentuh langsung</strong> oleh
merge di atas &mdash; dibuat belakangan, hanya untuk pembuktian:</p>
<pre id="proof" class="result-box">(belum ada merge yang dijalankan)</pre>

<h3>Panel Admin (bagian halaman yang "tidak berhubungan")</h3>
<p>Panel ini melakukan pengecekan akses dengan membaca <code>({}).isAdmin</code> dari object
polos yang baru dibuat saat halaman dicek &mdash; simulasi kode aplikasi lain yang memercayai
properti default dari sebuah object biasa.</p>
<div id="adminPanel" class="error-box">&#128274; Terkunci &mdash; <code>({}).isAdmin</code> saat ini <code>undefined</code>/<code>false</code>.</div>

<script>
function vulnerableMerge(target, source) {
  for (var key in source) {
    if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
      if (typeof target[key] !== 'object' || target[key] === null) target[key] = {};
      vulnerableMerge(target[key], source[key]);
    } else {
      target[key] = source[key];
    }
  }
  return target;
}

function checkAdminAccess() {
  // Kode ini tidak tahu apa-apa soal form merge di atas - cuma bikin object polos dan
  // mengecek satu properti, seperti kode "asli" di bagian lain aplikasi yang tidak sadar
  // library merge-nya rentan.
  var probe = {};
  var panel = document.getElementById('adminPanel');
  if (probe.isAdmin) {
    panel.className = 'ok-box';
    panel.innerHTML = '&#128275; TERBUKA - <code>({}).isAdmin</code> = <b>true</b>. ' +
      'Object polos yang baru dibuat ini TIDAK PERNAH di-merge secara langsung - nilainya ' +
      'datang dari Object.prototype yang sudah tercemar.';
  } else {
    panel.className = 'error-box';
    panel.innerHTML = '&#128274; Terkunci - <code>({}).isAdmin</code> saat ini <code>' + probe.isAdmin + '</code>.';
  }
}

function applyMerge() {
  var raw = document.getElementById('payload').value;
  var proof = document.getElementById('proof');
  try {
    var parsed = JSON.parse(raw);
    var result = vulnerableMerge({}, parsed);

    var probe = {};
    var lines = [];
    lines.push('vulnerableMerge({}, payload) selesai dijalankan.');
    lines.push('');
    lines.push('JSON.stringify(hasil merge)  = ' + JSON.stringify(result) + '  (own properties saja)');
    lines.push('');
    lines.push('--- Proof: object BARU & TIDAK TERKAIT, dibuat setelah merge ---');
    lines.push('var probe = {};');
    lines.push('JSON.stringify(probe)        = ' + JSON.stringify(probe) + '   (masih "{}", own prop memang kosong)');
    lines.push('probe.isAdmin                = ' + probe.isAdmin);
    lines.push('"isAdmin" in probe           = ' + ('isAdmin' in probe));
    lines.push('probe.isAdmin === true       = ' + (probe.isAdmin === true));
    if (probe.isAdmin === true) {
      lines.push('');
      lines.push('>>> Object.prototype berhasil TERCEMAR. Semua object {} di halaman ini sekarang mewarisi isAdmin=true. <<<');
    }
    proof.textContent = lines.join('\n');
    console.log('vulnerableMerge result:', result);
    console.log('probe.isAdmin:', probe.isAdmin);
  } catch (e) {
    proof.textContent = 'Gagal parse JSON: ' + e.message;
  }
  // Bagian halaman lain (panel admin) melakukan pengecekannya sendiri SETELAH merge selesai,
  // persis seperti kode aplikasi nyata yang berjalan di request/komponen berbeda.
  checkAdminAccess();
}

// Cek kondisi awal saat halaman pertama kali dimuat (sebelum merge apa pun dijalankan).
checkAdminAccess();
</script>

<p class="hint">Kenapa berhasil: <code>JSON.parse('{"__proto__":{"isAdmin":true}}')</code>
menghasilkan object dengan OWN property literal bernama <code>"__proto__"</code> (bukan
accessor). Saat <code>vulnerableMerge</code> mem-for-in key tersebut lalu merekursi ke dalamnya,
ekspresi <code>target["__proto__"]</code> di sisi TARGET justru memicu accessor bawaan
<code>Object.prototype.__proto__</code> yang mengarah ke <code>Object.prototype</code> itu
sendiri &mdash; sehingga assignment <code>target[key] = value</code> di level rekursi berikutnya
menulis langsung ke <code>Object.prototype.isAdmin</code>, bukan ke object lokal manapun. Inilah
mekanisme persis di balik CVE-2018-3721 (lodash <code>merge()</code>/<code>defaultsDeep()</code>
sebelum versi 4.17.5) dan sederet CVE prototype-pollution serupa di library JS lain.</p>

<?php include 'footer.php'; ?>
