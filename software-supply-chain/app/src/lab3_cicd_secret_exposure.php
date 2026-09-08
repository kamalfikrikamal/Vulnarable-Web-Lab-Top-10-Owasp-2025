<?php
$title = 'Lab 3: CI/CD Secret Exposure';
include 'header.php';

// Token deploy production - HARUS konsisten dengan token yang di-hardcode di deploy.yml
// (file workflow CI/CD yang "tidak sengaja" ikut ter-deploy ke webroot).
$VALID_DEPLOY_TOKEN = 'DEPLOY_TOKEN_9f8a7b6c5d4e3f2a1b0c';

$result_html = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim((string)($_POST['token'] ?? ''));
    // Terima format "Bearer <token>" maupun token mentah, sama seperti API deploy nyata.
    if (stripos($token, 'Bearer ') === 0) $token = trim(substr($token, 7));

    if ($token !== '' && hash_equals($VALID_DEPLOY_TOKEN, $token)) {
        $deployment_id = 'dep-' . substr(sha1($token . microtime()), 0, 10);
        $result_html = "ok";
        $ok_deployment_id = $deployment_id;
        $ok_timestamp = date('Y-m-d H:i:s') . ' UTC';
    } else {
        $result_html = 'fail';
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: form di bawah ini mensimulasikan API internal
<code>https://deploy.acme-corp.internal/api/deploy</code> &mdash; siapa pun yang punya token
Bearer yang valid bisa memicu deploy ke production. Masalahnya, file konfigurasi pipeline CI/CD
kadang tidak sengaja ikut ter-<em>deploy</em> ke webroot publik (bukan cuma tersimpan di
repository/CI server), lengkap dengan secret yang di-hardcode di dalamnya.</p>
<p class="hint">Coba akses langsung <code>/deploy.yml</code> pada aplikasi ini. File semacam ini
(workflow CI, <code>.gitlab-ci.yml</code>, <code>Jenkinsfile</code>, dsb) adalah target umum
untuk dicoba path-guessing di webroot mana pun.</p>
</details>

<h3>Internal Deploy API</h3>
<form method="post">
  <label>Authorization Token</label><br>
  <input type="text" name="token" placeholder="Bearer DEPLOY_TOKEN_..." style="width:100%; max-width:500px;">
  <button type="submit">Trigger Deploy</button>
</form>

<?php if ($result_html === 'ok'): ?>
<div class="ok-box">
  <strong>200 OK</strong> &mdash; Deploy berhasil dipicu ke production.<br>
  Deployment ID: <code><?php echo htmlspecialchars($ok_deployment_id); ?></code><br>
  Timestamp: <code><?php echo htmlspecialchars($ok_timestamp); ?></code><br>
  Environment: <code>production</code>, Branch: <code>main</code>
</div>
<?php elseif ($result_html === 'fail'): ?>
<div class="error-box">401 - token tidak valid.</div>
<?php endif; ?>

<p class="hint">Kenapa berhasil: token deploy production di-hardcode langsung sebagai plaintext
di dalam file workflow CI/CD (<code>deploy.yml</code>). File ini seharusnya hanya hidup di dalam
repository/CI runner, tapi salah konfigurasi deployment membuatnya ikut tersalin ke webroot
publik &mdash; siapa pun yang menemukannya lewat path-guessing langsung mendapat kredensial yang
setara dengan hak akses pipeline deploy itu sendiri, tanpa perlu membobol apa pun di aplikasi.</p>

<?php include 'footer.php'; ?>
