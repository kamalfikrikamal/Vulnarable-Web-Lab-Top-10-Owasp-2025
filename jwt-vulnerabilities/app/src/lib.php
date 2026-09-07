<?php
session_start();

function b64url_encode($data) { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
function b64url_decode($data) { return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4)); }

function jwt_parts($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    $header = json_decode(b64url_decode($parts[0]), true);
    $payload = json_decode(b64url_decode($parts[1]), true);
    return ['header' => $header, 'payload' => $payload, 'raw' => $parts, 'signing_input' => $parts[0] . '.' . $parts[1]];
}

function jwt_issue_hs256($payload, $secret, $extra_header = []) {
    $header = array_merge(['typ' => 'JWT', 'alg' => 'HS256'], $extra_header);
    $h = b64url_encode(json_encode($header));
    $p = b64url_encode(json_encode($payload));
    $sig = b64url_encode(hash_hmac('sha256', "$h.$p", $secret, true));
    return "$h.$p.$sig";
}

function current_secret_hint() {
    // "Weak secret" for lab 2 - short and present in common secret
    // wordlists (jwt_secrets.txt style lists used by jwt_tool/hashcat).
    return 'letmein123';
}
