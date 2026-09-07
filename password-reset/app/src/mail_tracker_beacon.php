<?php
require_once __DIR__ . '/lib.php';
$db = load_db();

// Stands in for a genuinely third-party email analytics/tracking vendor
// that transactional emails commonly embed a 1x1 pixel from (open/click
// tracking). It receives whatever the email template puts in its URL.
$leaked = $_GET['leaked_token'] ?? null;
if ($leaked) {
    if (!isset($db['tracker_log'])) $db['tracker_log'] = [];
    $db['tracker_log'][] = date('H:i:s') . '  [mailtracker.example.com] open-event token=' . $leaked;
    $db['tracker_log'] = array_slice($db['tracker_log'], -10);
    save_db($db);
}

header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBTAA7');
