<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        // Lab 2: default vendor credentials never changed sebelum go-live
        'admin_username' => 'admin',
        'admin_password' => 'admin123',
        'users' => [
            ['id' => 1, 'username' => 'alice', 'email' => 'alice@corp.test'],
            ['id' => 2, 'username' => 'bob', 'email' => 'bob@corp.test'],
            ['id' => 3, 'username' => 'admin', 'email' => 'admin@corp.test'],
        ],
        // Lab 5: user yang disimulasikan "sudah login" via session, plus
        // data sensitif miliknya yang nanti dibocorkan lewat CORS misconfig
        'session_user' => [
            'email' => 'carol@corp.test',
            'api_key' => 'demo-api-key--9f2a1c7e4b3d8f001122--NOT-A-REAL-CREDENTIAL',
        ],
        // Lab 6: log transfer yang dibuat lewat form clickjacking
        'transfers' => [],
    ];
}

function load_db() {
    $path = db_path();
    if (!file_exists($path)) save_db(seed_db());
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : seed_db();
}

function save_db($db) { file_put_contents(db_path(), json_encode($db, JSON_PRETTY_PRINT)); }

// Lab 5: "login" otomatis via session begitu peserta pertama kali buka lab
// tersebut - tidak perlu form login sungguhan, cukup untuk mensimulasikan
// user yang sedang login membawa cookie session ke tab lain.
function ensure_logged_in() {
    $db = load_db();
    if (empty($_SESSION['logged_in'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $db['session_user']['email'];
        $_SESSION['api_key'] = $db['session_user']['api_key'];
    }
}

// Lab 10-12: cookie demo terpisah dari session PHP bawaan, supaya tiap lab
// bisa mendemonstrasikan kombinasi flag (HttpOnly/Secure/SameSite) yang
// berbeda-beda tanpa saling mempengaruhi. Set ulang setiap kali dipanggil
// dengan $opts yang diberikan (bukan cuma sekali di awal), supaya flag yang
// dikirim browser selalu sesuai dengan kode lab yang sedang dibuka.
function ensure_demo_cookie($name, array $opts) {
    if (!isset($_COOKIE[$name])) {
        $token = bin2hex(random_bytes(8));
        setcookie($name, $token, $opts);
        $_COOKIE[$name] = $token;
        return $token;
    }
    // Cookie sudah ada dari kunjungan sebelumnya - set ulang flag-nya (nilai
    // tetap sama) supaya konsisten kalau opts di kode lab pernah diubah.
    setcookie($name, $_COOKIE[$name], $opts);
    return $_COOKIE[$name];
}
