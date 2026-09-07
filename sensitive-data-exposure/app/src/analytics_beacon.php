<?php
require_once __DIR__ . '/lib.php';
$db = load_db();

// Stands in for a genuinely third-party host (analytics/ad network) that
// the application embeds. It only ever sees whatever the browser decides
// to send it - and by default, that includes the full referring URL,
// query string and all.
$referer = $_SERVER['HTTP_REFERER'] ?? '(no referer)';
$db['referer_log'][] = date('H:i:s') . '  Referer: ' . $referer;
$db['referer_log'] = array_slice($db['referer_log'], -20);
save_db($db);

header('Content-Type: image/gif');
// 1x1 transparent GIF
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBTAA7');
