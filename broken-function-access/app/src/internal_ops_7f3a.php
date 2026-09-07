<?php
require_once __DIR__ . '/lib.php';
$title = 'Internal Ops Console';
$db = load_db();
$me = current_user($db);
include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }

// VULNERABLE: this "hidden" admin console has no role check at all - its
// only protection is that the URL isn't linked from the UI (it leaked via
// robots.txt instead).
?>

<p>Kamu login sebagai: <strong><?php echo htmlspecialchars($me['username']); ?></strong>
(role: <span class="badge <?php echo $me['role']==='admin'?'admin':'user'; ?>"><?php echo htmlspecialchars($me['role']); ?></span>)</p>

<div class="result-box"><?php echo htmlspecialchars($db['secret']); ?></div>

<?php if ($me['role'] !== 'admin'): ?>
<div class="error-box">Halaman ini tidak pernah ditautkan dari menu manapun, tapi kamu tetap bisa membukanya langsung dan bukan admin &mdash; obscurity bukan access control.</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
