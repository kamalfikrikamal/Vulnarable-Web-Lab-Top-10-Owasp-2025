<?php
/**
 * Konfigurasi seluruh konten portal: kategori OWASP Top 10:2025 -> jenis
 * kerentanan -> lab spesifik. Untuk menambah kerentanan baru di masa depan,
 * cukup tambah/lengkapi entri di sini - tidak perlu mengubah index.php,
 * category.php, vuln.php, atau lab.php.
 */

function lab_url($app, $path) {
    $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return "$scheme://$host:8079/$app/" . ltrim($path, '/');
}

function owasp_categories() {
    return [
        'a01-access-control' => [
            'code' => 'A01:2025', 'title' => 'Broken Access Control', 'status' => 'active',
            'summary' => 'Pembatasan hak akses tidak diterapkan dengan benar.',
            'description' => '<p>Terjadi ketika aplikasi gagal memastikan bahwa pengguna hanya bisa mengakses data atau fungsi yang menjadi haknya. Akibatnya, pengguna biasa bisa mengakses data pengguna lain, atau bahkan fungsi khusus admin.</p><p><strong>Contoh sederhana:</strong> URL <code>/invoice?id=1001</code> menampilkan invoice milik kita. Jika kita ubah manual menjadi <code>/invoice?id=1002</code> dan ternyata bisa melihat invoice milik orang lain tanpa otorisasi, itu disebut <em>Insecure Direct Object Reference (IDOR)</em>, salah satu bentuk Broken Access Control.</p><p>Lab hari ini mencakup tiga variasi Broken Access Control yang paling sering ditemukan di dunia nyata: <strong>Insecure Direct Object Reference (IDOR)</strong> (server mempercayai ID/identifier dari client tanpa mengecek kepemilikan), <strong>Broken Function-Level Access Control</strong> (user biasa bisa menjalankan fungsi yang seharusnya khusus admin - vertical privilege escalation), dan <strong>Cross-Site Request Forgery (CSRF)</strong> (aksi yang mengubah state dipicu diam-diam oleh halaman pihak ketiga, memanfaatkan sesi korban yang sedang login).</p>',
            'vulns' => [
                'idor' => [
                    'title' => 'Insecure Direct Object Reference (IDOR)',
                    'summary' => 'Server mempercayai ID yang dikirim client tanpa mengecek kepemilikan.',
                    'description' => '<p>IDOR terjadi ketika aplikasi mengambil/mengubah data berdasarkan identifier (ID, token, nama file) yang dikirim langsung oleh client, tanpa memverifikasi bahwa data tersebut memang milik user yang sedang login.</p><p><strong>Contoh sederhana:</strong> <code>GET /invoice?id=5001</code> menampilkan invoice kita. Mengubah jadi <code>id=5002</code> dan tetap bisa melihat invoice user lain adalah bukti IDOR.</p>',
                    'labs' => [
                        'basic-read' => [
                            'title' => 'Basic IDOR (baca data)',
                            'summary' => 'Mengubah parameter ID untuk membaca data milik user lain.',
                            'description' => '<p>Halaman "invoice saya" mengambil data lewat parameter <code>id</code> tanpa mengecek kepemilikan sama sekali.</p><p><strong>Contoh payload:</strong> <code>?id=5002</code> saat login sebagai user pemilik invoice <code>5001</code>.</p>',
                            'app' => 'idor', 'path' => 'lab1_basic_idor.php',
                        ],
                        'write' => [
                            'title' => 'IDOR pada aksi tulis',
                            'summary' => 'Parameter ID yang sama dipakai untuk mengubah/menghapus data user lain.',
                            'description' => '<p>Form update menyimpan ID target di hidden field yang bisa diubah bebas lewat DevTools/Burp sebelum submit, mengizinkan penulisan ke data milik user lain.</p><p><strong>Contoh:</strong> ubah <code>user_id=4</code> di request update profil untuk mengubah data akun admin.</p>',
                            'app' => 'idor', 'path' => 'lab2_idor_write.php',
                        ],
                        'unpredictable-id' => [
                            'title' => 'IDOR dengan ID tidak berurutan',
                            'summary' => 'ID diganti token acak, tapi tetap bocor lewat kanal lain.',
                            'description' => '<p>ID "sulit ditebak" (UUID/token acak) sering dianggap developer sebagai pengganti access control - padahal cuma <em>obscurity</em>. Begitu token bocor lewat kanal lain (log, feed aktivitas, dsb), data tetap bisa diakses siapa pun yang memilikinya.</p>',
                            'app' => 'idor', 'path' => 'lab3_idor_unpredictable.php',
                        ],
                        'api' => [
                            'title' => 'IDOR di endpoint API/JSON',
                            'summary' => 'Endpoint API mengembalikan seluruh field, termasuk yang sensitif.',
                            'description' => '<p>Endpoint yang dipanggil <code>fetch()</code> dari JavaScript bisa diakses langsung dan sering luput dari audit access control yang sama ketatnya dengan halaman HTML.</p><p><strong>Contoh:</strong> <code>?api=1&amp;id=4</code> mengembalikan password &amp; kartu kredit user lain.</p>',
                            'app' => 'idor', 'path' => 'lab4_idor_api.php',
                        ],
                        'mass-assignment' => [
                            'title' => 'Mass Assignment',
                            'summary' => 'Field ekstra di request diterima mentah-mentah oleh server.',
                            'description' => '<p>Server melakukan <code>array_merge($user, $_POST)</code> tanpa allowlist field yang boleh diubah, sehingga field seperti <code>role</code> bisa diselundupkan lewat request meski tidak ada di form aslinya.</p>',
                            'app' => 'idor', 'path' => 'lab5_mass_assignment.php',
                        ],
                    ],
                ],
                'broken-function-access' => [
                    'title' => 'Broken Function-Level Access Control',
                    'summary' => 'User biasa bisa menjalankan fungsi yang seharusnya khusus admin.',
                    'description' => '<p>Terjadi ketika aplikasi lupa memverifikasi role/permission sebelum menjalankan fungsi sensitif - dikenal juga sebagai <em>vertical privilege escalation</em>.</p><p><strong>Contoh sederhana:</strong> halaman <code>/admin</code> hanya mengecek "apakah sudah login", bukan "apakah role-nya admin".</p>',
                    'labs' => [
                        'unprotected-admin' => [
                            'title' => 'Unprotected admin functionality',
                            'summary' => 'Halaman admin diakses langsung tanpa cek role.',
                            'description' => '<p>Server hanya mengecek status login, tidak pernah mengecek <code>role === admin</code>.</p>',
                            'app' => 'bfla', 'path' => 'lab1_unprotected_admin.php',
                        ],
                        'hidden-url' => [
                            'title' => 'Unprotected admin dengan URL "tersembunyi"',
                            'summary' => 'URL admin tidak ditautkan di UI, tapi bocor lewat robots.txt.',
                            'description' => '<p>Security through obscurity: URL tidak ada di menu manapun, tapi tercatat di <code>robots.txt</code> yang memang dipublikasikan untuk crawler.</p>',
                            'app' => 'bfla', 'path' => 'lab2_hidden_url.php',
                        ],
                        'role-cookie' => [
                            'title' => 'Role ditentukan cookie client-writable',
                            'summary' => 'Keputusan akses memakai cookie yang bisa diubah bebas oleh client.',
                            'description' => '<p>Halaman membaca <code>$_COOKIE[\'role\']</code> alih-alih data user tervalidasi di server. Cookie non-<code>HttpOnly</code> bisa diubah lewat <code>document.cookie</code>.</p>',
                            'app' => 'bfla', 'path' => 'lab3_role_cookie.php',
                        ],
                        'method-bypass' => [
                            'title' => 'Method-based access control bypass',
                            'summary' => 'Cek role cuma jalan untuk GET, endpoint POST tidak dicek.',
                            'description' => '<p>Tombol aksi disembunyikan di render GET, tapi handler yang benar-benar menjalankan aksi (POST) tidak pernah mengecek role.</p>',
                            'app' => 'bfla', 'path' => 'lab4_method_bypass.php',
                        ],
                        'referer-bypass' => [
                            'title' => 'Referer-based access control bypass',
                            'summary' => 'Akses "dianggap sah" berdasarkan header Referer yang dipalsukan.',
                            'description' => '<p>Header <code>Referer</code> sepenuhnya dikendalikan client dan bisa dipalsukan lewat curl/Burp - bukan bukti otorisasi apa pun.</p>',
                            'app' => 'bfla', 'path' => 'lab5_referer_bypass.php',
                        ],
                    ],
                ],
                'csrf' => [
                    'title' => 'Cross-Site Request Forgery (CSRF)',
                    'summary' => 'Aksi sensitif dipicu diam-diam oleh halaman pihak ketiga.',
                    'description' => '<p>CSRF memanfaatkan fakta bahwa browser otomatis menyertakan cookie session ke setiap request ke suatu domain, terlepas dari halaman mana yang memicu request itu. Tanpa proteksi tambahan, halaman attacker bisa memaksa browser korban mengirim request "atas nama" korban.</p>',
                    'labs' => [
                        'no-token' => [
                            'title' => 'Tidak ada token CSRF',
                            'summary' => 'Form aksi sensitif tanpa proteksi CSRF apa pun.',
                            'description' => '<p>Server hanya mengandalkan cookie session yang valid, tanpa token CSRF sama sekali.</p>',
                            'app' => 'csrf', 'path' => 'lab1_no_token.php',
                        ],
                        'token-not-tied' => [
                            'title' => 'Token CSRF tidak diikat ke session',
                            'summary' => 'Validasi hanya cek "token pernah diterbitkan", bukan pemiliknya.',
                            'description' => '<p>Attacker bisa mendapat token valid milik sendiri, lalu memakainya untuk memalsukan request atas nama korban.</p>',
                            'app' => 'csrf', 'path' => 'lab2_token_not_tied.php',
                        ],
                        'token-removal' => [
                            'title' => 'Bypass dengan menghapus parameter token',
                            'summary' => 'Validasi cuma jalan kalau parameter token dikirim.',
                            'description' => '<p><code>if (isset($_POST[\'csrf_token\']))</code> - kalau parameter itu tidak ada sama sekali, validasi ter-skip begitu saja.</p>',
                            'app' => 'csrf', 'path' => 'lab3_token_removal.php',
                        ],
                        'get-based' => [
                            'title' => 'Aksi sensitif lewat GET request',
                            'summary' => 'State-changing action dipicu hanya dengan satu tag &lt;img&gt;.',
                            'description' => '<p>GET seharusnya <em>safe method</em> tanpa efek samping. Mengizinkan aksi sensitif lewat GET membuatnya bisa dipicu tanpa JavaScript maupun form sama sekali.</p>',
                            'app' => 'csrf', 'path' => 'lab4_get_based.php',
                        ],
                    ],
                ],
            ],
        ],
        'a02-security-misconfiguration' => [
            'code' => 'A02:2025', 'title' => 'Security Misconfiguration', 'status' => 'active',
            'summary' => 'Konfigurasi keamanan server/aplikasi yang salah atau longgar.',
            'description' => '<p>Terjadi saat server, framework, database, atau layanan cloud dikonfigurasi secara tidak aman - biasanya karena memakai pengaturan bawaan (default) tanpa dikeraskan (hardening).</p><p><strong>Contoh sederhana:</strong> Mode debug Laravel/Django masih aktif di production sehingga error menampilkan detail source code dan environment variable (termasuk password database) ke publik.</p><p>Lab hari ini mencakup empat variasi Security Misconfiguration: <strong>Security Misconfiguration</strong> umum (debug mode, kredensial default, directory listing, debug endpoint tertinggal, CORS, header keamanan hilang), <strong>Exposed VCS/Config Files</strong> (folder <code>.git</code>, file <code>.env</code>, backup editor yang ikut ter-deploy ke webroot), <strong>Cookie Security Misconfiguration</strong> (atribut <code>HttpOnly</code>/<code>Secure</code>/<code>SameSite</code> yang dilewatkan), dan <strong>Insecure HTTP Methods</strong> (method <code>TRACE</code>/<code>PUT</code> yang seharusnya dimatikan tapi tetap aktif).</p>',
            'vulns' => [
                'misconfig' => [
                    'title' => 'Security Misconfiguration',
                    'summary' => 'Pengaturan bawaan/longgar di server atau aplikasi yang seharusnya dikeraskan sebelum production.',
                    'description' => '<p>Beda dari bug di kode aplikasi, Security Misconfiguration adalah kesalahan pada <em>pengaturan</em> - server, framework, atau layanan yang dikonfigurasi longgar (atau dibiarkan memakai default) sehingga membuka celah yang sebetulnya mudah dicegah lewat hardening dasar.</p>',
                    'labs' => [
                        'debug-stacktrace' => [
                            'title' => 'Debug mode aktif membocorkan stack trace',
                            'summary' => 'Pesan error mentah menampilkan kredensial database.',
                            'description' => '<p>Mode debug yang seharusnya cuma untuk development masih aktif di "production" - error yang tak tertangani menampilkan stack trace lengkap beserta connection string database.</p><p><strong>Contoh:</strong> masukkan ID produk yang bukan angka untuk memicu error.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab1_debug_stacktrace.php',
                        ],
                        'default-credentials' => [
                            'title' => 'Kredensial default',
                            'summary' => 'Panel admin masih memakai username/password bawaan vendor.',
                            'description' => '<p>Kredensial <code>admin</code>/<code>admin123</code> yang seharusnya diganti saat instalasi awal tidak pernah diubah.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab2_default_credentials.php',
                        ],
                        'directory-listing' => [
                            'title' => 'Directory listing aktif',
                            'summary' => 'Folder backup bisa dijelajahi langsung lewat browser.',
                            'description' => '<p><code>Options +Indexes</code> aktif di folder <code>backup/</code>, membocorkan file <code>.bak</code> berisi dump database & config lama.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab3_directory_listing.php',
                        ],
                        'debug-endpoint' => [
                            'title' => 'Debug endpoint tertinggal di production',
                            'summary' => 'Halaman diagnostik developer membocorkan environment variable.',
                            'description' => '<p>Endpoint ala <code>phpinfo()</code> yang dipakai saat development tidak pernah dihapus/diproteksi sebelum deploy, dan tidak ditautkan di menu mana pun - cuma bisa diakses kalau tahu path-nya.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab4_debug_endpoint.php',
                        ],
                        'cors-misconfig' => [
                            'title' => 'CORS misconfiguration',
                            'summary' => 'Server me-reflect header Origin dengan kredensial diizinkan.',
                            'description' => '<p><code>Access-Control-Allow-Origin</code> me-reflect header <code>Origin</code> apa pun dari request, dikombinasikan dengan <code>Access-Control-Allow-Credentials: true</code> - situs mana pun bisa membaca API ini pakai cookie korban.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab5_cors_misconfig.php',
                        ],
                        'missing-headers-clickjacking' => [
                            'title' => 'Header keamanan hilang - Clickjacking',
                            'summary' => 'Tanpa X-Frame-Options/CSP, halaman aksi sensitif bisa di-iframe attacker.',
                            'description' => '<p>Halaman "transfer dana" tidak mengirim <code>X-Frame-Options</code> maupun <code>frame-ancestors</code>, sehingga bisa ditumpangi lewat <code>&lt;iframe&gt;</code> transparan di halaman attacker untuk menjebak klik korban.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab6_missing_headers_clickjacking.php',
                        ],
                    ],
                ],
                'exposed-vcs' => [
                    'title' => 'Exposed VCS / Config Files',
                    'summary' => 'File/folder yang tidak seharusnya ter-deploy ke webroot publik, tapi tetap bisa diakses.',
                    'description' => '<p>Folder version control (<code>.git</code>), file konfigurasi (<code>.env</code>), dan file backup editor sering ikut ter-deploy ke webroot production tanpa disadari - dan karena web server tidak memblokir dotfile/nama file semacam ini secara default, semuanya tetap bisa diakses langsung lewat URL.</p>',
                    'labs' => [
                        'git-exposed' => [
                            'title' => 'Folder .git ter-expose',
                            'summary' => 'Seluruh riwayat commit, termasuk secret yang "sudah dihapus", bisa direkonstruksi.',
                            'description' => '<p>Subsite kecil di-deploy dengan menyalin folder kerja git apa adanya. Directory listing aktif di <code>.git/</code>, sehingga isinya bisa diunduh dan riwayat commit-nya dibaca dengan <code>git log -p</code> - termasuk file yang pernah di-commit lalu dihapus di commit berikutnya.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab7_git_exposed.php',
                        ],
                        'env-exposed' => [
                            'title' => 'File .env ter-expose',
                            'summary' => 'File konfigurasi berisi kredensial database & application key bisa diakses langsung.',
                            'description' => '<p>File <code>.env</code> ikut ter-deploy di root aplikasi, dan tidak ada aturan yang memblokir akses ke dotfile - kredensial production terbaca mentah-mentah lewat satu request.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab8_env_exposed.php',
                        ],
                        'backup-file-guess' => [
                            'title' => 'Backup file editor yang bisa ditebak',
                            'summary' => 'File recovery text editor membocorkan source code & kredensial.',
                            'description' => '<p>File seperti <code>config.php.save</code> tertinggal di webroot dari sesi edit langsung di server - karena ekstensinya bukan <code>.php</code>, isinya dikirim sebagai teks biasa, bukan dieksekusi.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab9_backup_file_guess.php',
                        ],
                    ],
                ],
                'cookie-misconfig' => [
                    'title' => 'Cookie Security Misconfiguration',
                    'summary' => 'Atribut keamanan cookie sesi/autentikasi (HttpOnly/Secure/SameSite) dilewatkan.',
                    'description' => '<p>Cookie sesi/autentikasi punya tiga atribut keamanan yang seharusnya selalu dipasang: <code>HttpOnly</code> (blokir akses lewat JavaScript), <code>Secure</code> (hanya lewat HTTPS), dan <code>SameSite</code> (kontrol pengiriman lintas situs). Melewatkan salah satunya melemahkan lapisan pertahanan yang sebetulnya murah untuk dipasang.</p>',
                    'labs' => [
                        'missing-httponly' => [
                            'title' => 'Cookie tanpa flag HttpOnly',
                            'summary' => 'Cookie sesi bisa dibaca lewat document.cookie.',
                            'description' => '<p>Tanpa <code>HttpOnly</code>, bug XSS apa pun (sekecil apa pun) langsung bisa dipakai untuk mencuri cookie sesi lewat <code>document.cookie</code>.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab10_missing_httponly.php',
                        ],
                        'missing-secure' => [
                            'title' => 'Cookie tanpa flag Secure',
                            'summary' => 'Cookie sesi tetap terkirim ke endpoint plain HTTP mana pun.',
                            'description' => '<p>Tanpa <code>Secure</code>, tidak ada jaminan browser bahwa cookie ini hanya melintas lewat koneksi terenkripsi - satu titik akses HTTP yang lupa di-redirect cukup untuk membocorkannya ke penyadap jaringan.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab11_missing_secure.php',
                        ],
                        'missing-samesite' => [
                            'title' => 'Cookie tanpa atribut SameSite',
                            'summary' => 'Atribut SameSite tidak dideklarasikan sama sekali.',
                            'description' => '<p>Browser modern menerapkan default Lax diam-diam, tapi browser lama/WebView tetap memperlakukan cookie ini sebagai <code>None</code> - dikirim di semua request lintas situs.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab12_missing_samesite.php',
                        ],
                    ],
                ],
                'http-methods' => [
                    'title' => 'Insecure HTTP Methods',
                    'summary' => 'HTTP method di luar GET/POST yang seharusnya dimatikan tapi tetap diterima server.',
                    'description' => '<p>Web server dan aplikasi PHP secara default tetap memproses method HTTP seperti <code>TRACE</code> dan <code>PUT</code> kecuali dimatikan/dibatasi secara eksplisit - keduanya jarang benar-benar dibutuhkan aplikasi, tapi kalau aktif membuka attack surface baru di luar form/endpoint yang dimaksudkan developer.</p>',
                    'labs' => [
                        'trace-method' => [
                            'title' => 'HTTP method TRACE aktif (XST)',
                            'summary' => 'Server meng-echo balik seluruh header request, termasuk Cookie.',
                            'description' => '<p>Default aman <code>TraceEnable Off</code> dari image dasar sengaja dibalik jadi <code>On</code> - request <code>TRACE</code> mendapat balasan berisi persis header yang dikirim, celah historis untuk melewati proteksi HttpOnly (Cross-Site Tracing).</p>',
                            'app' => 'secmisconfig', 'path' => 'lab13_trace_method.php',
                        ],
                        'put-method' => [
                            'title' => 'HTTP method PUT diterima tanpa validasi',
                            'summary' => 'Request PUT menyimpan file apa pun ke direktori yang dieksekusi PHP.',
                            'description' => '<p>Endpoint memproses method <code>PUT</code> dan menyimpan body request apa adanya tanpa validasi ekstensi/isi/autentikasi - webshell bisa ditanam tanpa pernah menyentuh form upload aplikasi.</p>',
                            'app' => 'secmisconfig', 'path' => 'lab14_put_method.php',
                        ],
                    ],
                ],
            ],
        ],
        'a03-software-supply-chain-failures' => [
            'code' => 'A03:2025', 'title' => 'Software Supply Chain Failures', 'status' => 'active',
            'summary' => 'Risiko dari dependency, pipeline build, dan komponen pihak ketiga.',
            'description' => '<p>Aplikasi modern bergantung pada ratusan library pihak ketiga dan pipeline CI/CD otomatis. Jika salah satu mata rantai ini disusupi, aplikasi ikut terdampak walau kode yang kita tulis sendiri aman.</p><p><strong>Contoh sederhana:</strong> Penyerang mengunggah paket npm dengan nama mirip library populer (typosquatting, mis. <code>expres</code> alih-alih <code>express</code>); developer yang salah ketik saat install tanpa sadar menjalankan kode berbahaya tersebut.</p><p>Lab hari ini mencakup tiga variasi Software Supply Chain Failures: <strong>Software Supply Chain Failures</strong> umum (Prototype Pollution, dependency confusion, secret CI/CD ter-expose, auto-update tanpa verifikasi, postinstall berbahaya), <strong>Malicious/Compromised Package Content</strong> (typosquatting, missing SRI pada skrip CDN, lockfile yang tidak ditegakkan), dan <strong>Unpinned/Mutable Build References</strong> (CI Action & base image container yang dipin ke tag mutable, bukan SHA/digest immutable).</p>',
            'vulns' => [
                'supply-chain' => [
                    'title' => 'Software Supply Chain Failures',
                    'summary' => 'Risiko dari dependency, pipeline build, dan komponen pihak ketiga yang tidak diverifikasi.',
                    'description' => '<p>Rantai suplai software modern melibatkan banyak pihak yang tidak sepenuhnya di bawah kendali developer: registry package publik, pipeline CI/CD, dan komponen pihak ketiga. Kompromi di salah satu mata rantai ini berdampak ke aplikasi meski kode sendiri tidak punya bug apa pun.</p>',
                    'labs' => [
                        'prototype-pollution' => [
                            'title' => 'Prototype Pollution di utility library lama',
                            'summary' => 'Fungsi merge/extend versi lama mengizinkan __proto__ disuntik.',
                            'description' => '<p>Mensimulasikan CVE-2018-3721 (lodash <code>merge()</code> versi lama) - JSON dari user di-deep-merge tanpa memblokir kunci <code>__proto__</code>, sehingga bisa mempolusi <code>Object.prototype</code> dan mempengaruhi objek lain di halaman.</p><p><strong>Contoh payload:</strong> <code>{"__proto__":{"isAdmin":true}}</code></p>',
                            'app' => 'supplychain', 'path' => 'lab1_prototype_pollution.php',
                        ],
                        'dependency-confusion' => [
                            'title' => 'Dependency confusion',
                            'summary' => 'Nama package internal yang tidak pernah dipublish bisa "direbut" di registry publik.',
                            'description' => '<p>Build internal fallback ke registry publik kalau nama package tidak ditemukan di registry internal - attacker yang tahu nama package internal (bocor lewat file config) bisa publish package publik bernama sama supaya kodenya ikut "terinstall" saat build berikutnya.</p>',
                            'app' => 'supplychain', 'path' => 'lab2_dependency_confusion.php',
                        ],
                        'cicd-secret-exposure' => [
                            'title' => 'Secret CI/CD ter-expose',
                            'summary' => 'File konfigurasi pipeline berisi token deploy sengaja/tidak sengaja ada di webroot.',
                            'description' => '<p>File workflow CI/CD (mis. <code>deploy.yml</code>) yang seharusnya hanya ada di repo Git malah ikut ter-deploy ke webroot, membocorkan token deploy production dalam bentuk plaintext.</p>',
                            'app' => 'supplychain', 'path' => 'lab3_cicd_secret_exposure.php',
                        ],
                        'unsigned-autoupdate' => [
                            'title' => 'Auto-update tanpa verifikasi',
                            'summary' => 'Paket update diterima & "diterapkan" tanpa cek signature/checksum sama sekali.',
                            'description' => '<p>Mekanisme auto-update mengambil paket dari sumber yang diberikan tanpa memverifikasi keasliannya - paket resmi maupun paket yang sudah dimanipulasi diperlakukan sama persis.</p>',
                            'app' => 'supplychain', 'path' => 'lab4_unsigned_autoupdate.php',
                        ],
                        'malicious-postinstall' => [
                            'title' => 'Postinstall script berbahaya',
                            'summary' => 'Script lifecycle dari dependency pihak ketiga dijalankan otomatis tanpa direview.',
                            'description' => '<p>Package manager modern menjalankan script <code>postinstall</code> milik dependency secara otomatis dengan privilese penuh - dependency yang tidak direview bisa menyelundupkan perintah shell apa pun lewat script ini.</p>',
                            'app' => 'supplychain', 'path' => 'lab5_malicious_postinstall.php',
                        ],
                    ],
                ],
                'malicious-package-content' => [
                    'title' => 'Malicious / Compromised Package Content',
                    'summary' => 'Konten dependency yang benar-benar terpasang berbeda dari yang dimaksudkan/direview tim.',
                    'description' => '<p>Bukan cuma soal registry mana yang dipakai - nama paket yang disamarkan (typosquatting), sumber eksternal yang tidak diverifikasi (missing SRI), dan lockfile yang tidak ditegakkan semuanya membuat konten yang benar-benar terpasang/dijalankan berbeda dari yang dipercaya developer.</p>',
                    'labs' => [
                        'typosquatting' => [
                            'title' => 'Typosquatting',
                            'summary' => 'Paket dengan nama nyaris identik didaftarkan attacker di registry publik.',
                            'description' => '<p>Nama paket lookalike (satu-dua karakter berbeda, mis. huruf <code>l</code> diganti <code>I</code> kapital) terdaftar di registry publik - korban ter-install paket yang salah karena copy-paste command tanpa mengecek ulang.</p>',
                            'app' => 'supplychain', 'path' => 'lab6_typosquatting.php',
                        ],
                        'missing-sri' => [
                            'title' => 'Missing Subresource Integrity (SRI)',
                            'summary' => 'Skrip pihak ketiga dimuat dari CDN tanpa atribut integrity.',
                            'description' => '<p>Tanpa atribut <code>integrity</code>, browser menjalankan apa pun isi skrip yang diterima dari CDN eksternal tanpa verifikasi - kalau CDN-nya disusupi, skrip yang sudah dimodifikasi tetap jalan penuh.</p>',
                            'app' => 'supplychain', 'path' => 'lab7_missing_sri.php',
                        ],
                        'lockfile-ignored' => [
                            'title' => 'Lockfile diabaikan saat build',
                            'summary' => 'Proses build tidak menegakkan versi yang dikunci di lockfile.',
                            'description' => '<p>Lockfile mengunci dependency ke versi & hash yang sudah direview, tapi build yang tidak memakai mode "frozen" bisa diam-diam mengambil versi lebih baru yang belum direview (dan sudah disusupi).</p>',
                            'app' => 'supplychain', 'path' => 'lab8_lockfile_ignored.php',
                        ],
                    ],
                ],
                'unpinned-build-references' => [
                    'title' => 'Unpinned / Mutable Build References',
                    'summary' => 'Referensi mutable (tag) dipakai alih-alih referensi immutable (SHA/digest).',
                    'description' => '<p>Tag seperti <code>@v1</code> atau <code>:latest</code> hanyalah pointer yang bisa dipindahkan kapan saja oleh siapa pun yang punya akses ke sumbernya - referensi immutable (commit SHA, digest) menjamin konten yang berjalan selalu persis yang pernah direview.</p>',
                    'labs' => [
                        'ci-action-mutable-tag' => [
                            'title' => 'CI Action dipin ke tag mutable',
                            'summary' => 'Workflow memanggil Action pihak ketiga lewat tag yang bisa dipindah, bukan commit SHA.',
                            'description' => '<p>Tag <code>@v1</code> pada Action CI/CD pihak ketiga bisa dipindah kapan saja oleh maintainer atau attacker yang membajak repo-nya - workflow yang pin ke tag otomatis menjalankan apa pun yang sedang ditunjuknya.</p>',
                            'app' => 'supplychain', 'path' => 'lab9_ci_action_mutable_tag.php',
                        ],
                        'mutable-base-image' => [
                            'title' => 'Container base image dipin ke tag mutable',
                            'summary' => 'Dockerfile memakai :latest alih-alih digest sha256:... yang immutable.',
                            'description' => '<p>Sama seperti Action CI/CD, tag image container bisa dipindah ke digest lain kapan saja oleh siapa pun yang punya akses push ke registry - build yang pin ke tag mewarisi apa pun yang sedang ditunjuknya.</p>',
                            'app' => 'supplychain', 'path' => 'lab10_mutable_base_image.php',
                        ],
                    ],
                ],
            ],
        ],
        'a04-cryptographic-failures' => [
            'code' => 'A04:2025', 'title' => 'Cryptographic Failures', 'status' => 'active',
            'summary' => 'Data sensitif tidak terlindungi karena kriptografi lemah/tidak ada.',
            'description' => '<p>Terjadi saat data sensitif (password, kartu kredit, data pribadi) disimpan atau dikirim tanpa enkripsi yang memadai, atau memakai algoritma yang sudah usang.</p><p><strong>Contoh sederhana:</strong> Password pengguna disimpan sebagai MD5 tanpa salt di database. Jika database bocor, hash MD5 tanpa salt sangat mudah di-crack menjadi password asli.</p><p>Lab hari ini mencakup empat variasi Cryptographic Failures: <strong>Weak Password Hashing</strong> (penyimpanan password yang mudah dipulihkan ke bentuk asli), <strong>Insecure Randomness</strong> (token/ID/kode yang seharusnya tidak bisa ditebak ternyata deterministik), <strong>Sensitive Data Exposure</strong> (data sensitif bocor lewat URL, cache, atau response yang tidak konsisten), dan <strong>JWT Vulnerabilities</strong> (token JSON Web Token yang tidak diverifikasi dengan aman).</p>',
            'vulns' => [
                'weak-hashing' => [
                    'title' => 'Weak Password Hashing',
                    'summary' => 'Password disimpan dengan cara yang mudah dipulihkan ke bentuk asli.',
                    'description' => '<p>Password yang disimpan plaintext, di-hash dengan algoritma cepat tanpa salt (MD5/SHA1), atau cuma "disamarkan" (encoding) alih-alih benar-benar di-hash, membuat database yang bocor langsung membocorkan seluruh password penggunanya.</p>',
                    'labs' => [
                        'plaintext' => [
                            'title' => 'Penyimpanan password plaintext',
                            'summary' => 'Password disimpan apa adanya, terbaca langsung kalau database bocor.',
                            'description' => '<p>Tidak ada transformasi kriptografi apa pun antara input password dan yang disimpan di database.</p>',
                            'app' => 'hashing', 'path' => 'lab1_plaintext.php',
                        ],
                        'unsalted-md5' => [
                            'title' => 'Hash MD5 tanpa salt',
                            'summary' => 'Crackable dalam hitungan detik lewat dictionary attack/rainbow table.',
                            'description' => '<p>MD5 didesain cepat dihitung (untuk checksum, bukan password) - tanpa salt, hash yang bocor bisa dicocokkan lewat lookup table online atau <code>hashcat -m 0</code>.</p>',
                            'app' => 'hashing', 'path' => 'lab2_unsalted_md5.php',
                        ],
                        'reversible-encoding' => [
                            'title' => '"Enkripsi" yang sebenarnya cuma encoding',
                            'summary' => 'Cookie "remember me" pakai base64, bukan enkripsi sungguhan.',
                            'description' => '<p>base64 adalah encoding (bisa dibalik siapa saja tanpa kunci apa pun), bukan enkripsi. Menamai variabelnya "encrypted" tidak mengubah sifat matematisnya.</p>',
                            'app' => 'hashing', 'path' => 'lab3_reversible_encoding.php',
                        ],
                    ],
                ],
                'insecure-randomness' => [
                    'title' => 'Insecure Randomness',
                    'summary' => 'Token/ID/kode yang seharusnya tidak bisa ditebak ternyata deterministik.',
                    'description' => '<p>Menggunakan <code>rand()</code>/<code>mt_rand()</code> (bukan CSPRNG) atau men-seed generator dengan nilai publik (waktu, ID user) membuat "nilai acak" bisa dihitung ulang oleh siapa pun yang tahu inputnya.</p>',
                    'labs' => [
                        'predictable-reset-token' => [
                            'title' => 'Password reset token bisa diprediksi',
                            'summary' => 'Token dibuat dari md5(username . time()).',
                            'description' => '<p>Waktu server bocor lewat header <code>Date</code>, dan username sering publik - keduanya cukup untuk menghitung ulang token.</p>',
                            'app' => 'randomness', 'path' => 'lab1_predictable_reset_token.php',
                        ],
                        'sequential-api-key' => [
                            'title' => 'API key sekuensial',
                            'summary' => 'API key cuma nilai auto-increment, bukan token acak.',
                            'description' => '<p>Menaikkan satu angka dari API key sendiri langsung menemukan API key user lain.</p>',
                            'app' => 'randomness', 'path' => 'lab2_sequential_api_key.php',
                        ],
                        'predictable-otp' => [
                            'title' => 'OTP 2FA bisa diprediksi',
                            'summary' => 'mt_srand($timestamp) membuat OTP sepenuhnya deterministik.',
                            'description' => '<p><code>mt_rand()</code> bukan CSPRNG - kalau seed-nya (waktu generate) diketahui, seluruh output bisa dihitung ulang persis sama.</p>',
                            'app' => 'randomness', 'path' => 'lab3_predictable_otp.php',
                        ],
                        'predictable-coupon' => [
                            'title' => 'Kode kupon bisa ditebak',
                            'summary' => 'Kode kupon mengikuti pola sekuensial dari nomor order.',
                            'description' => '<p>"Kode unik" ternyata cuma representasi ulang nomor order yang sekuensial, tanpa komponen acak sama sekali.</p>',
                            'app' => 'randomness', 'path' => 'lab4_predictable_coupon.php',
                        ],
                    ],
                ],
                'sensitive-data-exposure' => [
                    'title' => 'Sensitive Data Exposure',
                    'summary' => 'Data sensitif bocor lewat kanal yang tidak disadari developer.',
                    'description' => '<p>Data sensitif bisa bocor bukan cuma karena enkripsi lemah, tapi juga lewat URL yang tercatat di banyak tempat, cache yang tidak dikontrol, atau response yang menampilkan lebih banyak data daripada seharusnya.</p>',
                    'labs' => [
                        'url-leak' => [
                            'title' => 'Data sensitif di URL',
                            'summary' => 'Nomor kartu di query string bocor lewat header Referer.',
                            'description' => '<p>Browser mengirim URL lengkap (termasuk query string) sebagai Referer ke resource pihak ketiga mana pun yang dimuat halaman.</p>',
                            'app' => 'dataexposure', 'path' => 'lab1_url_leak.php',
                        ],
                        'missing-cache-control' => [
                            'title' => 'Header Cache-Control tidak diset',
                            'summary' => 'Data akun bisa tersimpan di shared cache dan bocor ke user lain.',
                            'description' => '<p>Tanpa <code>Cache-Control: no-store</code>, cache di antara server dan browser bisa menyimpan &amp; mengulang response berisi data pribadi ke pengunjung berikutnya.</p>',
                            'app' => 'dataexposure', 'path' => 'lab2_missing_cache_control.php',
                        ],
                        'unmasked-response' => [
                            'title' => 'Data sensitif tidak di-mask',
                            'summary' => 'Masking diterapkan manual per-endpoint, jadi tidak konsisten.',
                            'description' => '<p>Satu endpoint menerapkan masking kartu dengan benar, endpoint lain untuk data yang sama lupa menerapkannya.</p>',
                            'app' => 'dataexposure', 'path' => 'lab3_unmasked_response.php',
                        ],
                    ],
                ],
                'jwt' => [
                    'title' => 'JWT Vulnerabilities',
                    'summary' => 'Token JSON Web Token tidak diverifikasi dengan aman.',
                    'description' => '<p>JWT hanya seaman implementasi verifikasinya - kesalahan umum termasuk menerima algoritma <code>none</code>, secret HMAC yang lemah, tidak memverifikasi signature sama sekali, atau mempercayai header <code>kid</code> tanpa sanitasi.</p>',
                    'labs' => [
                        'alg-none' => [
                            'title' => 'Algoritma alg=none diterima',
                            'summary' => 'Server menerima token tanpa signature apa pun.',
                            'description' => '<p><code>alg: none</code> seharusnya tidak pernah diterima untuk token otentikasi, tapi server ini mempercayai payloadnya begitu saja.</p>',
                            'app' => 'jwt', 'path' => 'lab1_alg_none.php',
                        ],
                        'weak-secret' => [
                            'title' => 'Secret HMAC lemah',
                            'summary' => 'Secret pendek, brute-forceable lewat wordlist.',
                            'description' => '<p>Kekuatan HS256 bergantung penuh pada kerahasiaan &amp; entropi secret-nya - secret umum bisa ditemukan lewat <code>hashcat -m 16500</code>.</p>',
                            'app' => 'jwt', 'path' => 'lab2_weak_secret.php',
                        ],
                        'no-signature-check' => [
                            'title' => 'Signature tidak pernah diverifikasi',
                            'summary' => 'Server mempercayai payload apa adanya, signature bisa diisi apa saja.',
                            'description' => '<p>Server hanya men-decode payload dan tidak pernah menghitung ulang &amp; membandingkan signature-nya.</p>',
                            'app' => 'jwt', 'path' => 'lab3_no_signature_check.php',
                        ],
                        'kid-path-traversal' => [
                            'title' => 'Path traversal lewat header kid',
                            'summary' => 'Header kid dipakai untuk membaca file kunci tanpa sanitasi.',
                            'description' => '<p>Mengarahkan <code>kid</code> ke <code>/dev/null</code> lewat path traversal membuat server memakai kunci HMAC yang sudah diketahui pasti: string kosong.</p>',
                            'app' => 'jwt', 'path' => 'lab4_kid_path_traversal.php',
                        ],
                    ],
                ],
            ],
        ],
        'a05-injection' => [
            'code' => 'A05:2025', 'title' => 'Injection', 'status' => 'active',
            'summary' => 'Input pengguna tercampur dengan kode/perintah yang dijalankan sistem.',
            'description' => '<p>Injection terjadi ketika aplikasi mengirim data yang tidak tepercaya (input dari pengguna) ke sebuah <em>interpreter</em> - misalnya database (SQL), browser (HTML/JavaScript), atau shell sistem operasi - dan data tersebut ikut ditafsirkan sebagai bagian dari perintah/kode, bukan sekadar data biasa.</p><p><strong>Contoh sederhana:</strong> Sebuah form login membangun query seperti ini secara langsung dari input pengguna:</p><div class="example">SELECT * FROM users WHERE username = \'$username\' AND password = \'$password\'</div><p>Jika pengguna mengisi username dengan <code>admin\' -- -</code>, maka tanda kutip yang seharusnya jadi "data" malah menutup string SQL lebih awal, dan <code>-- </code> mengubah sisa query jadi komentar sehingga pengecekan password terlewati begitu saja. Prinsip yang sama berlaku pada XSS (data tercampur ke dalam HTML/JavaScript yang dieksekusi browser), Command Injection (data tercampur ke dalam perintah shell sistem operasi), LFI (nama file dari input tercampur ke dalam path yang dimasukkan <code>include()</code>), dan File Upload (isi/nama file dari pengguna dipercaya begitu saja sebagai data yang aman untuk disimpan dan diakses kembali).</p><p>Lab yang kita pelajari hari ini - <strong>SQL Injection</strong>, <strong>Cross-Site Scripting (XSS)</strong>, <strong>OS Command Injection</strong>, <strong>Local File Inclusion (LFI)</strong>, dan <strong>File Upload Vulnerabilities</strong> - semuanya adalah variasi dari masalah dasar yang sama ini.</p>',
            'vulns' => [
                'sqli' => [
                    'title' => 'SQL Injection',
                    'summary' => 'Menyisipkan perintah SQL lewat input aplikasi.',
                    'description' => '<p>SQL Injection (SQLi) terjadi ketika input pengguna digabungkan langsung ke dalam query SQL tanpa disaring/di-parameterisasi, sehingga penyerang bisa mengubah logika query untuk membaca, mengubah, atau menghapus data yang seharusnya tidak bisa mereka akses - bahkan terkadang melewati proses login sama sekali.</p><p><strong>Contoh sederhana:</strong> Halaman pencarian produk memakai query <code>SELECT * FROM products WHERE id = $id</code>. Jika parameter <code>id</code> diisi <code>0 UNION SELECT username, password FROM users</code>, hasil pencarian produk akan tercampur dengan data akun pengguna dari tabel lain.</p>',
                    'labs' => [
                        'login-bypass' => [
                            'title' => 'Login Bypass (Authentication Bypass)',
                            'summary' => 'Login sebagai admin tanpa tahu passwordnya.',
                            'description' => '<p>Query login membandingkan username & password langsung dari input, mis. <code>... WHERE username=\'$u\' AND password=\'$p\'</code>. Dengan menyisipkan tanda kutip dan komentar SQL (<code>-- </code>), bagian pengecekan password bisa "dihilangkan" dari query yang benar-benar dijalankan.</p><p><strong>Contoh payload:</strong> username = <code>admin\' -- -</code>, password = bebas.</p>',
                            'app' => 'sqli', 'path' => 'lab1_login_bypass.php',
                        ],
                        'union' => [
                            'title' => 'UNION-based SQL Injection',
                            'summary' => 'Menggabungkan hasil query dengan tabel lain pakai UNION SELECT.',
                            'description' => '<p>Jika hasil query ditampilkan langsung ke halaman, klausa <code>UNION SELECT</code> bisa dipakai untuk "menempelkan" hasil dari tabel lain (mis. tabel <code>users</code>) ke dalam tampilan yang sama, asalkan jumlah &amp; tipe kolomnya cocok.</p><p><strong>Contoh payload:</strong> <code>id=0 UNION SELECT username,password,role,1 FROM users-- -</code></p>',
                            'app' => 'sqli', 'path' => 'lab2_union.php',
                        ],
                        'error-based' => [
                            'title' => 'Error-based SQL Injection',
                            'summary' => 'Membocorkan data lewat pesan error database.',
                            'description' => '<p>Ketika aplikasi menampilkan pesan error mentah dari database, fungsi seperti <code>extractvalue()</code> bisa dipaksa gagal dengan cara yang menyisipkan hasil sebuah subquery ke dalam teks error itu sendiri - sehingga data bisa "dibaca" lewat pesan error, satu subquery per request.</p><p><strong>Contoh payload:</strong> <code>category=\' AND extractvalue(1,concat(0x7e,(SELECT version())))-- -</code></p>',
                            'app' => 'sqli', 'path' => 'lab3_error_based.php',
                        ],
                        'blind-boolean' => [
                            'title' => 'Blind SQL Injection (Boolean-based)',
                            'summary' => 'Menebak data lewat respons benar/salah, tanpa melihat data langsung.',
                            'description' => '<p>Tidak ada data maupun error yang ditampilkan - hanya ada dua kemungkinan respons (mis. "ditemukan" / "tidak ditemukan"). Respons ini dipakai sebagai <em>oracle</em> benar/salah untuk menebak data karakter demi karakter memakai kondisi seperti <code>SUBSTRING(...)=\'x\'</code>.</p><p><strong>Contoh payload:</strong> <code>id=1 AND SUBSTRING((SELECT password FROM users WHERE username=\'admin\'),1,1)=\'S\'</code></p>',
                            'app' => 'sqli', 'path' => 'lab4_blind_boolean.php',
                        ],
                        'blind-time' => [
                            'title' => 'Blind SQL Injection (Time-based)',
                            'summary' => 'Menebak data lewat jeda waktu respons server.',
                            'description' => '<p>Respons aplikasi selalu identik apa pun hasil query-nya, sehingga oracle benar/salah dari respons tidak tersedia. Sebagai gantinya, fungsi <code>SLEEP()</code> disisipkan secara kondisional - jika kondisi benar, server akan terasa lebih lambat merespons.</p><p><strong>Contoh payload:</strong> <code>id=1 AND IF(SUBSTRING((SELECT password FROM users WHERE username=\'admin\'),1,1)=\'S\',SLEEP(3),0)</code></p>',
                            'app' => 'sqli', 'path' => 'lab5_blind_time.php',
                        ],
                        'second-order' => [
                            'title' => 'Second-Order SQL Injection',
                            'summary' => 'Payload disimpan dengan aman, lalu dipakai ulang secara tidak aman.',
                            'description' => '<p>Saat disimpan (mis. saat registrasi), input memang diproses dengan aman lewat prepared statement. Masalah muncul belakangan, ketika fitur lain membaca kembali data yang tersimpan itu dan menggabungkannya ke query baru tanpa perlakuan aman yang sama.</p><p><strong>Contoh:</strong> Daftar dengan username <code>x\' UNION SELECT password FROM users WHERE username=\'admin\'-- -</code>, lalu buka fitur "lihat profil" untuk memicu query kedua yang tidak aman.</p>',
                            'app' => 'sqli', 'path' => 'lab6_second_order.php',
                        ],
                        'order-by' => [
                            'title' => 'SQL Injection pada klausa ORDER BY',
                            'summary' => 'Parameter sorting hasil query ternyata bisa disuntik.',
                            'description' => '<p>Prepared statement tidak bisa memparameterisasi nama kolom/ekspresi pada <code>ORDER BY</code>, sehingga developer sering menggabungkannya langsung dari input. UNION tidak berlaku di sini - teknik yang dipakai adalah oracle boolean lewat ekspresi <code>CASE WHEN</code>.</p><p><strong>Contoh payload:</strong> <code>sort=(CASE WHEN (1=1) THEN name ELSE price END)</code> dibandingkan dengan kondisi <code>1=2</code>.</p>',
                            'app' => 'sqli', 'path' => 'lab7_order_by.php',
                        ],
                        'stacked-queries' => [
                            'title' => 'Stacked queries',
                            'summary' => 'Satu request menjalankan lebih dari satu statement SQL sekaligus.',
                            'description' => '<p>Fitur "catatan cepat" memakai <code>mysqli_multi_query()</code> yang mendukung multi-statement per pemanggilan - berbeda dari UNION yang cuma membaca data, di sini attacker bisa menambahkan statement SQL baru (INSERT/DROP) setelah <code>;</code>.</p><p><strong>Contoh payload:</strong> <code>note=x\'); INSERT INTO notes (text) VALUES (\'injected via stacked query\'); --</code></p>',
                            'app' => 'sqli', 'path' => 'lab8_stacked_queries.php',
                        ],
                        'filter-bypass' => [
                            'title' => 'Filter/WAF bypass',
                            'summary' => 'Blacklist naif memblokir "union select" secara literal.',
                            'description' => '<p>Filter regex hanya menolak frasa <code>union select</code> dengan spasi di antaranya - menyisipkan komentar SQL inline sebagai pemisah membuat filter ini lolos begitu saja.</p><p><strong>Contoh payload:</strong> <code>category=nonexistent\' UNION/**/SELECT username,password,role,1 FROM users-- -</code></p>',
                            'app' => 'sqli', 'path' => 'lab9_filter_bypass.php',
                        ],
                        'insert-based' => [
                            'title' => 'SQLi di konteks INSERT (form registrasi)',
                            'summary' => 'Field bio saat registrasi disuntikkan ke statement INSERT.',
                            'description' => '<p>Injection tidak melulu soal SELECT/WHERE - field "bio" pada form registrasi digabung mentah ke query <code>INSERT</code>, dieksploitasi dengan teknik error-based yang sama seperti pada SELECT.</p>',
                            'app' => 'sqli', 'path' => 'lab10_insert_based.php',
                        ],
                        'cookie-based' => [
                            'title' => 'SQLi lewat cookie',
                            'summary' => 'Nilai cookie TrackingId ikut dipakai di query tanpa disadari.',
                            'description' => '<p>Fitur "produk yang baru dilihat" memakai nilai cookie <code>TrackingId</code> langsung di query - channel input yang mudah luput dari audit karena tidak terlihat sebagai parameter URL/form biasa.</p>',
                            'app' => 'sqli', 'path' => 'lab11_cookie_based.php',
                        ],
                    ],
                ],
                'xss' => [
                    'title' => 'Cross-Site Scripting (XSS)',
                    'summary' => 'Menyisipkan HTML/JavaScript yang dieksekusi di browser korban.',
                    'description' => '<p>XSS terjadi ketika input pengguna ditampilkan kembali ke halaman HTML tanpa di-encode dengan benar, sehingga browser korban ikut mengeksekusinya sebagai kode, bukan sekadar teks. Dampaknya bisa mencuri cookie/sesi, mengambil alih akun, atau mengubah tampilan halaman untuk phishing.</p><p><strong>Contoh sederhana:</strong> Kolom komentar menampilkan input apa adanya: <code>&lt;script&gt;alert(document.cookie)&lt;/script&gt;</code>. Setiap orang yang membuka halaman tersebut akan menjalankan script itu di browser mereka.</p>',
                    'labs' => [
                        'reflected-html' => [
                            'title' => 'Reflected XSS (konteks HTML body)',
                            'summary' => 'Parameter URL dicetak langsung ke body halaman.',
                            'description' => '<p>Nilai parameter (mis. hasil pencarian) dicetak langsung ke body HTML tanpa <code>htmlspecialchars()</code>, sehingga tag apa pun yang disisipkan lewat URL akan ikut dirender oleh browser.</p><p><strong>Contoh payload:</strong> <code>?q=&lt;script&gt;alert(document.domain)&lt;/script&gt;</code></p>',
                            'app' => 'xss', 'path' => 'lab1_reflected.php',
                        ],
                        'reflected-attribute' => [
                            'title' => 'Reflected XSS (konteks atribut HTML)',
                            'summary' => 'Input ditaruh di dalam atribut, perlu "keluar" dari atribut dulu.',
                            'description' => '<p>Input dicetak di dalam atribut seperti <code>value="..."</code>. Karena masih di dalam tanda kutip atribut, penyerang perlu menutup tanda kutip &amp; tag tersebut lebih dulu sebelum bisa menyisipkan tag/handler baru.</p><p><strong>Contoh payload:</strong> <code>?color="&gt;&lt;script&gt;alert(1)&lt;/script&gt;</code></p>',
                            'app' => 'xss', 'path' => 'lab2_reflected_attribute.php',
                        ],
                        'reflected-js' => [
                            'title' => 'Reflected XSS (konteks JavaScript)',
                            'summary' => 'Input disisipkan ke dalam string JavaScript inline.',
                            'description' => '<p>Input dimasukkan ke dalam variabel JavaScript, mis. <code>var x = "...";</code>. Escaping yang tidak lengkap (mis. hanya meng-escape kutip) bisa dilewati, atau tag <code>&lt;/script&gt;</code> dipakai untuk menutup blok script secara langsung di level parser HTML.</p><p><strong>Contoh payload:</strong> <code>?name=&lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt;</code></p>',
                            'app' => 'xss', 'path' => 'lab3_reflected_js.php',
                        ],
                        'stored' => [
                            'title' => 'Stored XSS',
                            'summary' => 'Payload disimpan di server dan menyerang setiap pengunjung.',
                            'description' => '<p>Payload disimpan secara permanen (mis. di kolom komentar) dan ditampilkan ke setiap orang yang membuka halaman tersebut, tanpa perlu mengirim link khusus ke korban. Ini biasanya lebih berbahaya dari reflected XSS karena bisa menyerang banyak korban sekaligus, termasuk admin.</p><p><strong>Contoh payload:</strong> <code>&lt;script&gt;alert(document.cookie)&lt;/script&gt;</code> dikirim lewat form komentar.</p>',
                            'app' => 'xss', 'path' => 'lab4_stored_comments.php',
                        ],
                        'dom-based' => [
                            'title' => 'DOM-based XSS',
                            'summary' => 'Kerentanan murni di sisi client, server tidak pernah melihat payload.',
                            'description' => '<p>JavaScript di browser membaca data yang bisa dikendalikan penyerang (mis. <code>location.hash</code>) dan menuliskannya ke <code>innerHTML</code> tanpa sanitasi. Karena fragment URL (bagian setelah <code>#</code>) tidak pernah dikirim ke server, payload ini tidak akan pernah muncul di log server.</p><p><strong>Contoh payload:</strong> buka <code>halaman.php#&lt;img src=x onerror=alert(1)&gt;</code></p>',
                            'app' => 'xss', 'path' => 'lab5_dom_xss.php',
                        ],
                        'filter-bypass' => [
                            'title' => 'XSS dengan Filter Bypass',
                            'summary' => 'Filter blacklist naif ternyata masih bisa dilewati.',
                            'description' => '<p>Aplikasi mencoba memblokir XSS dengan menghapus string tertentu (mis. <code>&lt;script&gt;</code>) secara harfiah. Filter blacklist seperti ini nyaris selalu bisa dilewati lewat variasi kapitalisasi, tag lain, atau event handler HTML.</p><p><strong>Contoh payload:</strong> <code>&lt;img src=x onerror=alert(1)&gt;</code> atau <code>&lt;svg onload=alert(1)&gt;</code></p>',
                            'app' => 'xss', 'path' => 'lab6_filter_bypass.php',
                        ],
                        'header-based' => [
                            'title' => 'Reflected XSS via HTTP Header',
                            'summary' => 'Header User-Agent ditampilkan kembali tanpa encoding.',
                            'description' => '<p>Bukan hanya parameter URL yang bisa jadi sumber input berbahaya - header HTTP seperti <code>User-Agent</code> juga sepenuhnya dikendalikan pengirim request dan mudah dimanipulasi lewat curl atau proxy seperti Burp Suite.</p><p><strong>Contoh:</strong> <code>curl -A "&lt;script&gt;alert(1)&lt;/script&gt;" http://target/halaman.php</code></p>',
                            'app' => 'xss', 'path' => 'lab7_useragent.php',
                        ],
                        'javascript-uri' => [
                            'title' => 'XSS via javascript: URI di atribut href',
                            'summary' => 'Link "website" di profil dieksekusi sebagai script saat diklik.',
                            'description' => '<p>Nilai URL sudah di-escape dengan benar sebagai atribut HTML (bukan bug attribute-injection biasa), tapi tidak ada allowlist skema URL - <code>javascript:</code> tetap jadi string atribut yang valid dan dieksekusi browser saat link-nya diklik.</p><p><strong>Contoh payload:</strong> Website = <code>javascript:alert(document.domain)</code></p>',
                            'app' => 'xss', 'path' => 'lab8_javascript_uri.php',
                        ],
                        'svg-upload' => [
                            'title' => 'Stored XSS via SVG upload',
                            'summary' => 'File SVG yang diupload sebagai avatar berisi <script> yang dieksekusi.',
                            'description' => '<p>Upload avatar tidak memvalidasi konten file sama sekali. SVG adalah XML - browser mengeksekusi <code>&lt;script&gt;</code>/<code>onload</code> di dalamnya begitu file dibuka langsung di tab baru.</p>',
                            'app' => 'xss', 'path' => 'lab9_svg_upload.php',
                        ],
                        'postmessage-xss' => [
                            'title' => 'DOM-based XSS via postMessage',
                            'summary' => 'Handler postMessage menulis data dari origin manapun ke innerHTML.',
                            'description' => '<p>Listener <code>message</code> tidak pernah memvalidasi <code>event.origin</code>, dan langsung menulis <code>event.data</code> ke <code>innerHTML</code> - halaman iframe attacker manapun bisa mengirim payload XSS.</p>',
                            'app' => 'xss', 'path' => 'lab10_postmessage_xss.php',
                        ],
                        'csp-bypass' => [
                            'title' => 'CSP yang terlihat aman tapi tidak melindungi',
                            'summary' => 'Header Content-Security-Policy terpasang tapi memakai unsafe-inline.',
                            'description' => '<p>CSP dengan <code>script-src \'self\' \'unsafe-inline\'</code> tidak benar-benar memblokir apa pun - reflected XSS biasa tetap berjalan normal meski header CSP "ada".</p>',
                            'app' => 'xss', 'path' => 'lab11_csp_bypass.php',
                        ],
                    ],
                ],
                'command-injection' => [
                    'title' => 'OS Command Injection',
                    'summary' => 'Menyisipkan perintah shell tambahan lewat input aplikasi.',
                    'description' => '<p>Command Injection terjadi ketika aplikasi memanggil perintah shell sistem operasi (mis. <code>ping</code>, <code>curl</code>) dan menggabungkan input pengguna langsung ke dalam perintah tersebut. Metakarakter shell seperti <code>; | &amp;</code> bisa dipakai untuk menjalankan perintah tambahan di luar yang dimaksud aplikasi.</p><p><strong>Contoh sederhana:</strong> Fitur "ping" menjalankan <code>ping -c 2 $host</code>. Jika <code>host</code> diisi <code>127.0.0.1; whoami</code>, shell akan menjalankan dua perintah sekaligus: ping, lalu <code>whoami</code>.</p>',
                    'labs' => [
                        'visible' => [
                            'title' => 'Command Injection (output terlihat)',
                            'summary' => 'Hasil perintah tambahan langsung tampil di halaman.',
                            'description' => '<p>Bentuk paling mudah dikenali: output dari perintah yang disisipkan langsung ditampilkan ke halaman, sehingga bisa langsung dikonfirmasi dan dieksploitasi secara in-band.</p><p><strong>Contoh payload:</strong> <code>host=127.0.0.1; id</code></p>',
                            'app' => 'cmdi', 'path' => 'lab1_visible.php',
                        ],
                        'blind' => [
                            'title' => 'Blind Command Injection (time-based)',
                            'summary' => 'Tidak ada output, gunakan jeda waktu sebagai bukti eksekusi.',
                            'description' => '<p>Output perintah tidak pernah ditampilkan maupun disimpan - respons aplikasi selalu sama. Perintah <code>sleep</code> disisipkan untuk membuktikan bahwa payload benar-benar dieksekusi lewat delay pada waktu respons.</p><p><strong>Contoh payload:</strong> <code>domain=example.com; sleep 5</code></p>',
                            'app' => 'cmdi', 'path' => 'lab2_blind.php',
                        ],
                        'filter-bypass' => [
                            'title' => 'Command Injection dengan Filter Bypass',
                            'summary' => 'Karakter ; | & diblokir, tapi newline & backtick lolos.',
                            'description' => '<p>Filter memblokir karakter pemisah perintah yang paling umum, tapi lupa mem-filter newline atau command substitution (backtick / <code>$()</code>) yang punya efek serupa.</p><p><strong>Contoh payload:</strong> <code>host=127.0.0.1%0aid</code> (newline sebagai pemisah perintah)</p>',
                            'app' => 'cmdi', 'path' => 'lab3_filter_bypass.php',
                        ],
                        'argument-injection' => [
                            'title' => 'Argument Injection',
                            'summary' => 'Bukan lewat metakarakter shell, tapi lewat flag command-line.',
                            'description' => '<p>Input sudah dibungkus aman secara shell (mis. <code>escapeshellarg()</code>), sehingga metakarakter seperti <code>; | &amp;</code> tidak berguna. Tapi jika input diteruskan sebagai argumen program tanpa penanda akhir opsi (<code>--</code>), nilai yang diawali tanda minus (<code>-</code>) akan ditafsirkan program sebagai <em>flag</em>, bukan data.</p><p><strong>Contoh payload:</strong> <code>url=-V</code> (curl menampilkan info versi, bukan mengambil URL apa pun)</p>',
                            'app' => 'cmdi', 'path' => 'lab4_argument_injection.php',
                        ],
                    ],
                ],
                'lfi' => [
                    'title' => 'Local File Inclusion (LFI)',
                    'summary' => 'Menyisipkan path file lain lewat parameter yang dipakai include()/require().',
                    'description' => '<p>LFI (Local File Inclusion) terjadi ketika aplikasi memasukkan nama/path file dari input pengguna langsung ke fungsi seperti <code>include()</code> atau <code>require()</code> tanpa validasi memadai. Penyerang bisa memanfaatkannya untuk membaca file sensitif di server (path traversal), dan pada kasus yang lebih parah, menaikkannya jadi eksekusi kode (RCE) lewat PHP stream wrapper atau log poisoning.</p><p><strong>Contoh sederhana:</strong> Halaman menampilkan konten lewat <code>include($_GET[\'page\'] . \'.php\')</code>. Jika parameter <code>page</code> diisi <code>../../../../etc/passwd%00</code> (pada sistem lama) atau cukup <code>../../../../etc/passwd</code> (tanpa ekstensi dipaksakan), isi file sistem bisa ikut ditampilkan.</p>',
                    'labs' => [
                        'basic' => [
                            'title' => 'Basic LFI (simple case)',
                            'summary' => 'Parameter page langsung masuk ke include() tanpa validasi.',
                            'description' => '<p>Tidak ada filter maupun whitelist sama sekali - path apa pun yang bisa di-resolve filesystem akan langsung dimasukkan ke <code>include()</code>.</p><p><strong>Contoh payload:</strong> <code>?page=../../../../etc/passwd</code></p>',
                            'app' => 'lfi', 'path' => 'lab1_basic.php',
                        ],
                        'absolute-bypass' => [
                            'title' => 'Traversal diblokir, absolute path lolos',
                            'summary' => 'Filter hanya mencari substring "../", path absolut tidak butuh itu sama sekali.',
                            'description' => '<p>Filter menolak input yang mengandung <code>../</code>, tapi lupa bahwa path absolut (mis. <code>/etc/passwd</code>) tidak memerlukan notasi traversal sama sekali untuk menunjuk file di luar direktori aplikasi.</p><p><strong>Contoh payload:</strong> <code>?page=/etc/passwd</code></p>',
                            'app' => 'lfi', 'path' => 'lab2_absolute_bypass.php',
                        ],
                        'nonrecursive-strip' => [
                            'title' => 'Traversal sequence dihapus non-recursive',
                            'summary' => '"../" dihapus cuma satu kali, bukan berulang.',
                            'description' => '<p><code>str_replace(\'../\', \'\', $input)</code> hanya menghapus satu lapis kemunculan. Input seperti <code>....//</code> akan tersisa <code>../</code> setelah satu kali penghapusan.</p><p><strong>Contoh payload:</strong> <code>?page=....//....//....//etc/passwd</code></p>',
                            'app' => 'lfi', 'path' => 'lab3_nonrecursive_strip.php',
                        ],
                        'double-decode' => [
                            'title' => 'Filter lalu URL-decode berlebih',
                            'summary' => 'Aplikasi men-decode input sekali lagi setelah filter traversal dijalankan.',
                            'description' => '<p>Filter <code>../</code> dijalankan duluan, baru input di-<code>urldecode()</code> manual sekali lagi setelahnya. Payload yang di-double URL-encode lolos filter (masih terenkode saat difilter), lalu "muncul" jadi traversal asli setelah decode kedua.</p><p><strong>Contoh payload:</strong> <code>?page=%252e%252e%252fetc%252fpasswd</code></p>',
                            'app' => 'lfi', 'path' => 'lab4_double_decode.php',
                        ],
                        'start-validation' => [
                            'title' => 'Validasi hanya di awal path',
                            'summary' => 'Aplikasi cuma memastikan input dimulai dengan folder yang diizinkan.',
                            'description' => '<p>Validasi hanya mengecek bahwa string input <em>dimulai</em> dengan <code>pages/</code>, tanpa menormalisasi hasil akhirnya lewat <code>realpath()</code>.</p><p><strong>Contoh payload:</strong> <code>?page=pages/../../../../etc/passwd</code></p>',
                            'app' => 'lfi', 'path' => 'lab5_start_validation.php',
                        ],
                        'extension-nullbyte' => [
                            'title' => 'Validasi ekstensi dengan null byte bypass',
                            'summary' => 'Validasi hanya cek akhiran ".png", null byte memotong path sebelum dieksekusi.',
                            'description' => '<p>Validasi memeriksa bahwa input berakhiran <code>.png</code>. Null byte (<code>%00</code>) disisipkan di antara path target dan ekstensi palsu - teknik historis dari PHP &lt; 5.3.4, disimulasikan di lab ini supaya tetap bisa dipraktikkan.</p><p><strong>Contoh payload:</strong> <code>?page=../../../../etc/passwd%00.png</code></p>',
                            'app' => 'lfi', 'path' => 'lab6_extension_nullbyte.php',
                        ],
                        'wrappers-rce' => [
                            'title' => 'LFI to RCE (PHP wrappers & log poisoning)',
                            'summary' => 'Naikkan level dari baca file jadi eksekusi kode lewat php://filter, php://input, dan log poisoning.',
                            'description' => '<p>Tidak ada validasi sama sekali. Gunakan <code>php://filter</code> untuk membaca source code tanpa mengeksekusinya, <code>php://input</code> untuk mengeksekusi body request sebagai PHP, atau racuni log request (header User-Agent) lalu include file log tersebut untuk mencapai RCE penuh.</p><p><strong>Contoh payload:</strong> <code>?page=php://filter/convert.base64-encode/resource=pages/secret_notes.php</code></p>',
                            'app' => 'lfi', 'path' => 'lab7_wrappers_rce.php',
                        ],
                    ],
                ],
                'file-upload' => [
                    'title' => 'File Upload Vulnerabilities',
                    'summary' => 'Validasi upload file yang lemah memungkinkan webshell tersimpan & tereksekusi di server.',
                    'description' => '<p>Kerentanan File Upload terjadi ketika aplikasi mengizinkan pengguna meng-upload file tanpa memvalidasi jenis, isi, atau lokasi penyimpanannya dengan benar. Jika penyerang berhasil menaruh file berisi kode (mis. skrip PHP) ke direktori yang bisa dieksekusi web server, hasilnya adalah Remote Code Execution (RCE) penuh lewat "web shell".</p><p><strong>Contoh sederhana:</strong> Fitur upload avatar hanya mengecek ekstensi file dari nama yang dikirim, tanpa memeriksa isi sebenarnya. Jika penyerang meng-upload <code>shell.php</code> berisi <code>&lt;?php system($_GET[\'cmd\']); ?&gt;</code> dan file tersebut tersimpan di folder yang bisa diakses langsung, mengunjungi <code>shell.php?cmd=id</code> akan menjalankan perintah sistem operasi.</p>',
                    'labs' => [
                        'unrestricted' => [
                            'title' => 'Remote code execution via unrestricted upload',
                            'summary' => 'Tidak ada validasi ekstensi maupun konten sama sekali.',
                            'description' => '<p>Bentuk paling dasar: file apa pun diterima dan disimpan langsung ke direktori yang bisa mengeksekusi PHP.</p><p><strong>Contoh:</strong> upload <code>shell.php</code>, akses <code>uploads/shell.php?cmd=id</code>.</p>',
                            'app' => 'upload', 'path' => 'lab1_unrestricted.php',
                        ],
                        'content-type-bypass' => [
                            'title' => 'Web shell upload via Content-Type restriction bypass',
                            'summary' => 'Validasi cuma mengandalkan header Content-Type yang dikirim client.',
                            'description' => '<p>Aplikasi memeriksa <code>$_FILES[\'file\'][\'type\']</code>, nilai yang sepenuhnya dikendalikan oleh client lewat header multipart - sama sekali tidak dicocokkan dengan isi file sesungguhnya.</p><p><strong>Contoh:</strong> <code>curl -F "file=@shell.php;type=image/png" ...</code></p>',
                            'app' => 'upload', 'path' => 'lab2_content_type.php',
                        ],
                        'path-traversal' => [
                            'title' => 'Web shell upload via path traversal',
                            'summary' => 'Upload dibatasi ke folder aman, tapi nama file tidak disanitasi dari traversal.',
                            'description' => '<p>Direktori upload "aman" mematikan eksekusi PHP, tapi nama file dari field multipart dipakai mentah untuk membangun path tujuan sehingga <code>../</code> bisa membawa file keluar ke direktori lain yang PHP-nya aktif.</p><p><strong>Contoh:</strong> <code>filename="../uploads/shell.php"</code></p>',
                            'app' => 'upload', 'path' => 'lab3_path_traversal.php',
                        ],
                        'blacklist-bypass' => [
                            'title' => 'Web shell upload via extension blacklist bypass',
                            'summary' => 'Blacklist ekstensi PHP tidak lengkap.',
                            'description' => '<p>Blacklist memblokir <code>.php/.php3/.php4/.php5/.php7</code> tapi lupa varian lain (<code>.phtml</code>, <code>.pht</code>) yang tetap dieksekusi PHP oleh konfigurasi Apache bawaan.</p><p><strong>Contoh:</strong> upload <code>shell.phtml</code>.</p>',
                            'app' => 'upload', 'path' => 'lab4_blacklist_bypass.php',
                        ],
                        'obfuscated-extension' => [
                            'title' => 'Web shell upload via extension handling override (.htaccess)',
                            'summary' => 'Blacklist ekstensi lengkap, tapi tidak menyangka nama filenya bisa jadi file konfigurasi Apache.',
                            'description' => '<p>Blacklist ekstensi script sudah lengkap, tapi direktori upload punya <code>AllowOverride All</code> aktif - file bernama persis <code>.htaccess</code> lolos validasi (ekstensinya "htaccess", bukan salah satu yang diblokir) dan langsung dihormati Apache untuk mendefinisikan ulang ekstensi mana yang dieksekusi sebagai PHP.</p><p><strong>Contoh:</strong> upload <code>.htaccess</code> berisi <code>AddType application/x-httpd-php .jpg</code>, lalu upload <code>shell.jpg</code>.</p>',
                            'app' => 'upload', 'path' => 'lab5_obfuscated_extension.php',
                        ],
                        'polyglot' => [
                            'title' => 'Remote code execution via polyglot web shell upload',
                            'summary' => 'Validasi "harus benar-benar gambar" dilewati dengan file polyglot.',
                            'description' => '<p>Ekstensi <code>.php</code> memang diizinkan, tapi aplikasi menambahkan pengecekan <code>getimagesize()</code> sebagai "keamanan tambahan". Fungsi itu hanya membaca header di awal file, sehingga file dengan header gambar valid diikuti kode PHP tetap lolos.</p><p><strong>Contoh:</strong> <code>printf \'GIF89a;\\n&lt;?php system($_GET["cmd"]); ?&gt;\' > shell.php</code></p>',
                            'app' => 'upload', 'path' => 'lab6_polyglot.php',
                        ],
                        'race-condition' => [
                            'title' => 'Web shell upload via race condition',
                            'summary' => 'File disimpan dulu ke disk, baru divalidasi & dihapus belakangan.',
                            'description' => '<p>Ada jendela waktu antara file disimpan ke direktori executable dan selesai divalidasi/dihapus. Selama jendela itu, file sudah live dan bisa diakses/dieksekusi.</p><p><strong>Contoh:</strong> upload <code>shell.php</code>, lalu akses <code>uploads/shell.php?cmd=id</code> secepatnya sebelum proses validasi selesai.</p>',
                            'app' => 'upload', 'path' => 'lab7_race_condition.php',
                        ],
                    ],
                ],
            ],
        ],
        'a06-insecure-design' => [
            'code' => 'A06:2025', 'title' => 'Insecure Design', 'status' => 'active',
            'summary' => 'Kelemahan berasal dari desain/arsitektur, bukan sekadar bug.',
            'description' => '<p>Berbeda dari kesalahan implementasi, Insecure Design adalah kelemahan yang sudah tertanam sejak tahap perancangan alur/fitur aplikasi - sehingga tidak bisa diperbaiki hanya dengan menambal kode, melainkan perlu didesain ulang.</p><p><strong>Contoh sederhana:</strong> Fitur "lupa password" mengirim kode OTP 4 digit tanpa batas percobaan (rate limiting), sehingga penyerang bisa mencoba 10.000 kombinasi dengan cepat sampai berhasil.</p><p>Lab hari ini mencakup tiga variasi <em>business logic vulnerabilities</em>: <strong>Business Logic Vulnerabilities</strong> umum (price tampering, negative quantity, coupon stacking, skip step checkout, referral abuse), <strong>Flawed Multi-Step Authentication Logic</strong> (2FA yang bisa dilewati lewat forced browsing, ganti password tanpa re-autentikasi, trusted device yang gampang dipalsukan), dan <strong>Business Rule Enforcement Gaps</strong> (HTTP Parameter Pollution pada kupon, price spoofing lewat header region, over-refund) - semuanya kode "berjalan sesuai spek", tapi speknya sendiri yang cacat.</p>',
            'vulns' => [
                'business-logic' => [
                    'title' => 'Business Logic Vulnerabilities',
                    'summary' => 'Alur bisnis yang bisa disalahgunakan meski setiap baris kode "bekerja sesuai rencana".',
                    'description' => '<p>Kerentanan desain tidak selalu berupa bug teknis seperti injection - seringkali aplikasi berjalan persis seperti yang diprogram, tapi asumsi di balik alur bisnisnya (harga selalu dari server, kuantitas selalu positif, kupon cuma sekali pakai, langkah checkout harus berurutan, satu orang cuma daftar sekali) tidak pernah benar-benar ditegakkan.</p>',
                    'labs' => [
                        'price-tampering' => [
                            'title' => 'Price tampering lewat hidden field',
                            'summary' => 'Harga produk dikirim lewat hidden input dan dipercaya mentah-mentah.',
                            'description' => '<p>Server menghitung total dari <code>$_POST[\'price\']</code> alih-alih mengambil harga asli dari katalog - hidden field bisa diubah bebas lewat DevTools/Burp sebelum submit.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab1_price_tampering.php',
                        ],
                        'negative-quantity' => [
                            'title' => 'Negative quantity',
                            'summary' => 'Kuantitas negatif membuat total belanja jadi kredit tak wajar.',
                            'description' => '<p>Harga diambil aman dari server, tapi kuantitas tidak pernah divalidasi harus positif - kuantitas negatif membuat total negatif, yang lalu "dikreditkan" ke saldo user.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab2_negative_quantity.php',
                        ],
                        'coupon-stacking' => [
                            'title' => 'Coupon stacking / reuse',
                            'summary' => 'Kode diskon sekali pakai bisa dipakai berkali-kali.',
                            'description' => '<p>Kupon tidak pernah ditandai "sudah dipakai" setelah diterapkan - mengulang request yang sama berkali-kali terus menambah diskon.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab3_coupon_stacking.php',
                        ],
                        'skip-checkout-step' => [
                            'title' => 'Skip step alur checkout bertahap',
                            'summary' => 'Step pembayaran bisa dilewati langsung ke step konfirmasi.',
                            'description' => '<p>Step terakhir (konfirmasi/selesai) tidak pernah mengecek apakah step pembayaran benar-benar terjadi lebih dulu - urutan alur cuma "dijaga" lewat tautan di UI, bukan di server.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab4_skip_checkout_step.php',
                        ],
                        'unlimited-referral-abuse' => [
                            'title' => 'Abuse bonus referral tanpa batas',
                            'summary' => 'Bonus referral bisa di-farming tanpa batas lewat akun baru berulang.',
                            'description' => '<p>Satu-satunya validasi adalah keunikan string username - tidak ada verifikasi email, limit per-IP, atau deteksi fraud, sehingga bonus bisa diklaim berkali-kali lewat akun baru yang trivial dibuat.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab5_unlimited_referral_abuse.php',
                        ],
                    ],
                ],
                'flawed-auth-workflow' => [
                    'title' => 'Flawed Multi-Step Authentication Logic',
                    'summary' => 'Alur login bertahap (password -> 2FA) yang penegakannya di server punya celah.',
                    'description' => '<p>Login bertahap (password lalu 2FA) melibatkan beberapa komponen yang masing-masing terlihat benar sendiri-sendiri - flag session, token "trusted device", form ganti password - tapi urutan/penegakannya di server tidak konsisten, membuka celah untuk melewati faktor kedua sepenuhnya.</p>',
                    'labs' => [
                        '2fa-forced-browsing' => [
                            'title' => '2FA bypass lewat forced browsing',
                            'summary' => 'Halaman dashboard cuma mengecek flag password, bukan flag OTP.',
                            'description' => '<p>Server menyimpan flag "password benar" dan "OTP terverifikasi" terpisah, tapi dashboard cuma mengecek flag pertama - mengetik langsung URL step berikutnya melewati verifikasi OTP sama sekali.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab6_2fa_forced_browsing.php',
                        ],
                        'password-change-no-reauth' => [
                            'title' => 'Ganti password tanpa re-autentikasi',
                            'summary' => 'Form ganti password tidak pernah meminta password lama.',
                            'description' => '<p>Aksi sensitif ini tidak menuntut step-up authentication apa pun - sesi yang dicuri/dipinjam sebentar cukup untuk mengunci pemilik akun asli secara permanen.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab7_password_change_no_reauth.php',
                        ],
                        'trusted-device-bypass' => [
                            'title' => 'Trusted device bypass 2FA',
                            'summary' => 'Cookie "perangkat terpercaya" isinya cuma username polos.',
                            'description' => '<p>Token "trusted device" seharusnya acak & diikat ke device tertentu setelah OTP diverifikasi - di sini cuma username polos, gampang ditebak/disalin ke device manapun untuk melewati OTP sepenuhnya.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab8_trusted_device_bypass.php',
                        ],
                    ],
                ],
                'business-rule-enforcement-gaps' => [
                    'title' => 'Business Rule Enforcement Gaps',
                    'summary' => 'Aturan bisnis yang seharusnya jelas ternyata tidak pernah benar-benar ditegakkan.',
                    'description' => '<p>"Satu kupon sekali pakai per request", "harga sesuai region asli", "refund tidak boleh melebihi pembelian" - aturan-aturan ini terdengar jelas, tapi tidak satu pun ditegakkan lewat validasi nyata di server.</p>',
                    'labs' => [
                        'coupon-parameter-pollution' => [
                            'title' => 'HTTP Parameter Pollution pada kupon',
                            'summary' => 'Kode kupon yang sama di banyak slot array diterapkan berkali-kali.',
                            'description' => '<p>Field kupon yang sah mendukung banyak kode berbeda sekaligus (array) tidak pernah men-deduplikasi kode yang SAMA muncul lebih dari sekali - diskon diterapkan berkali-kali dalam satu request.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab9_coupon_parameter_pollution.php',
                        ],
                        'region-price-spoofing' => [
                            'title' => 'Price spoofing lewat header region',
                            'summary' => 'Harga produk digital ditentukan dari header X-Region milik client.',
                            'description' => '<p>Region untuk penentuan harga diambil dari header yang sepenuhnya dikendalikan client, bukan dari sumber tepercaya seperti IP atau alamat billing terverifikasi.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab10_region_price_spoofing.php',
                        ],
                        'over-refund' => [
                            'title' => 'Over-refund lewat kuantitas return',
                            'summary' => 'Kuantitas return tidak dibandingkan dengan kuantitas yang benar-benar dibeli.',
                            'description' => '<p>Refund dihitung murni dari angka yang diminta client, tanpa validasi terhadap riwayat pembelian asli - bisa jauh melampaui total yang pernah dibeli.</p>',
                            'app' => 'insecuredesign', 'path' => 'lab11_over_refund.php',
                        ],
                    ],
                ],
            ],
        ],
        'a07-authentication-failures' => [
            'code' => 'A07:2025', 'title' => 'Authentication Failures', 'status' => 'active',
            'summary' => 'Kelemahan pada proses login & manajemen sesi.',
            'description' => '<p>Mencakup segala kelemahan pada proses memverifikasi identitas pengguna dan menjaga sesi login mereka tetap aman setelahnya.</p><p><strong>Contoh sederhana:</strong> Setelah pengguna menekan "Logout", session token lama masih tetap valid dan bisa dipakai untuk mengakses akun jika tokennya berhasil dicuri sebelumnya.</p><p>Lab hari ini mencakup empat variasi Authentication Failures: <strong>Username Enumeration</strong> (form login membocorkan validitas username lewat pesan/waktu/perilaku), <strong>Broken Brute-Force Protection</strong> (rate limiting/lockout yang tidak memadai atau bisa dilewati), <strong>Broken Session Management</strong> (token sesi yang tidak dikelola dengan aman), dan <strong>Password Reset Flaws</strong> (alur reset password yang cacat).</p>',
            'vulns' => [
                'username-enumeration' => [
                    'title' => 'Username Enumeration',
                    'summary' => 'Form login membocorkan validitas username lewat kanal tak disadari.',
                    'description' => '<p>Sekali username tervalidasi lewat pesan error, panjang response, waktu respons, atau perilaku lockout, serangan berikutnya (password spraying, brute force terarah) jadi jauh lebih efisien.</p>',
                    'labs' => [
                        'different-message' => [
                            'title' => 'Pesan error berbeda',
                            'summary' => '"User tidak ditemukan" vs "Password salah".',
                            'description' => '<p>Dua pesan error yang jelas berbeda membocorkan langsung validasi mana yang gagal.</p>',
                            'app' => 'userenum', 'path' => 'lab1_different_message.php',
                        ],
                        'subtle-difference' => [
                            'title' => 'Response nyaris identik',
                            'summary' => 'Pesan sama secara visual, beda 1 karakter di response mentah.',
                            'description' => '<p>Perbedaan sekecil apa pun (titik di akhir kalimat) tetap membedakan Content-Length response.</p>',
                            'app' => 'userenum', 'path' => 'lab2_subtle_difference.php',
                        ],
                        'response-timing' => [
                            'title' => 'Perbedaan waktu respons',
                            'summary' => 'Username valid memicu proses verifikasi yang lebih lambat.',
                            'description' => '<p>Pekerjaan tambahan (simulasi cost hashing password) hanya terjadi kalau username ditemukan - celah waktu ini jadi oracle validitas.</p>',
                            'app' => 'userenum', 'path' => 'lab3_response_timing.php',
                        ],
                        'account-lockout' => [
                            'title' => 'Account lockout membocorkan validitas',
                            'summary' => 'Pesan "akun terkunci" hanya muncul untuk username yang valid.',
                            'description' => '<p>Mekanisme keamanan (lockout) itu sendiri jadi oracle baru karena hanya "aktif" untuk username yang benar-benar ada.</p>',
                            'app' => 'userenum', 'path' => 'lab4_account_lockout.php',
                        ],
                    ],
                ],
                'brute-force-protection' => [
                    'title' => 'Broken Brute-Force Protection',
                    'summary' => 'Rate limiting/lockout tidak memadai atau bisa dilewati.',
                    'description' => '<p>Proteksi brute-force yang lemah membuat penebakan password otomatis jadi praktis, baik karena tidak ada limit sama sekali maupun karena limit yang ada bisa dihindari.</p>',
                    'labs' => [
                        'no-rate-limit' => [
                            'title' => 'Tidak ada rate limiting',
                            'summary' => 'Login bisa dicoba tanpa batas, tanpa CAPTCHA maupun lockout.',
                            'description' => '<p>Setiap kandidat password dicoba tanpa hambatan apa pun - wordlist besar bisa dihabiskan dalam hitungan detik.</p>',
                            'app' => 'bruteforce', 'path' => 'lab1_no_rate_limit.php',
                        ],
                        'xff-bypass' => [
                            'title' => 'Lockout berbasis IP, bypass X-Forwarded-For',
                            'summary' => 'Server mempercayai header X-Forwarded-For dari client.',
                            'description' => '<p>Header ini sepenuhnya dikendalikan pengirim request - mengubahnya di tiap percobaan memberi "IP baru" setiap kali.</p>',
                            'app' => 'bruteforce', 'path' => 'lab2_xff_bypass.php',
                        ],
                        'case-variation-bypass' => [
                            'title' => 'Lockout bypass via variasi kapitalisasi',
                            'summary' => 'Counter percobaan case-sensitive, padahal login case-insensitive.',
                            'description' => '<p>"admin", "Admin", "ADMIN" masing-masing dapat jatah percobaan baru, padahal menyerang akun yang sama.</p>',
                            'app' => 'bruteforce', 'path' => 'lab3_case_variation_bypass.php',
                        ],
                    ],
                ],
                'session-management' => [
                    'title' => 'Broken Session Management',
                    'summary' => 'Token sesi tidak dikelola dengan aman.',
                    'description' => '<p>Mencakup token yang tidak di-invalidate saat logout, token yang bisa ditebak, session fixation, dan token yang bocor lewat kanal yang tidak seharusnya.</p>',
                    'labs' => [
                        'token-survives-logout' => [
                            'title' => 'Token tetap valid setelah logout',
                            'summary' => 'Logout cuma hapus cookie, token tidak di-invalidate di server.',
                            'description' => '<p>Token lama yang berhasil dicuri sebelum logout tetap memberi akses penuh.</p>',
                            'app' => 'sessionmgmt', 'path' => 'lab1_token_survives_logout.php',
                        ],
                        'predictable-session-id' => [
                            'title' => 'Session ID sekuensial',
                            'summary' => 'Token cuma angka urut, bukan nilai acak.',
                            'description' => '<p>Sekali satu token diketahui, token di sekitarnya bisa ditebak lewat iterasi sederhana.</p>',
                            'app' => 'sessionmgmt', 'path' => 'lab2_predictable_session_id.php',
                        ],
                        'session-fixation' => [
                            'title' => 'Session Fixation',
                            'summary' => 'Token dari luar tetap dipakai setelah login, tidak diregenerasi.',
                            'description' => '<p>Attacker memilih token sendiri sebelum korban login - token itu "naik level" jadi sesi terotentikasi tanpa pernah diganti.</p>',
                            'app' => 'sessionmgmt', 'path' => 'lab3_session_fixation.php',
                        ],
                        'token-in-url' => [
                            'title' => 'Session token di URL',
                            'summary' => 'Token otentikasi lewat parameter URL, tercatat di access log.',
                            'description' => '<p>URL (termasuk query string) dicatat access log server, riwayat browser, dan bisa bocor lewat Referer.</p>',
                            'app' => 'sessionmgmt', 'path' => 'lab4_token_in_url.php',
                        ],
                    ],
                ],
                'password-reset' => [
                    'title' => 'Password Reset Flaws',
                    'summary' => 'Alur reset password yang cacat.',
                    'description' => '<p>Kesalahan umum pada fitur reset password: token/kode yang bocor, bisa dipakai ulang, terlalu mudah ditebak, atau link reset yang dibangun dari input tak tepercaya.</p>',
                    'labs' => [
                        'token-leak-email' => [
                            'title' => 'Token bocor lewat tracking pixel email',
                            'summary' => 'Email reset memuat gambar pelacak yang membawa token.',
                            'description' => '<p>Gambar 1x1 dari domain analytics pihak ketiga di email transaksional bisa membawa token rahasia yang sama dengan link reset.</p>',
                            'app' => 'pwreset', 'path' => 'lab1_token_leak_email.php',
                        ],
                        'token-reuse' => [
                            'title' => 'Token bisa dipakai berulang kali',
                            'summary' => 'Token tidak pernah ditandai "sudah dipakai".',
                            'description' => '<p>Token yang seharusnya sekali pakai tetap berfungsi selamanya karena tidak ada mekanisme invalidasi.</p>',
                            'app' => 'pwreset', 'path' => 'lab2_token_reuse.php',
                        ],
                        'brute-forceable-code' => [
                            'title' => 'Kode reset pendek tanpa rate limiting',
                            'summary' => 'Kode 4 digit, bisa dicoba tanpa batas.',
                            'description' => '<p>Ruang kemungkinan yang kecil (10.000) dikombinasikan dengan tidak adanya rate limiting membuat brute force jadi praktis.</p>',
                            'app' => 'pwreset', 'path' => 'lab3_brute_forceable_code.php',
                        ],
                        'host-header-poisoning' => [
                            'title' => 'Password reset poisoning lewat Host header',
                            'summary' => 'Link reset dibangun dari header Host yang dikendalikan client.',
                            'description' => '<p>Header <code>Host</code> bisa diisi bebas oleh pengirim request - server keliru mempercayainya sebagai domain aplikasi saat membangun link reset.</p>',
                            'app' => 'pwreset', 'path' => 'lab4_host_header_poisoning.php',
                        ],
                    ],
                ],
            ],
        ],
        'a08-software-data-integrity-failures' => [
            'code' => 'A08:2025', 'title' => 'Software or Data Integrity Failures', 'status' => 'active',
            'summary' => 'Aplikasi mempercayai kode/data dari sumber yang tidak terverifikasi.',
            'description' => '<p>Terjadi ketika aplikasi menerima update, plugin, atau data terserialisasi dari sumber luar tanpa memverifikasi keasliannya (mis. lewat digital signature), sehingga rentan dimanipulasi.</p><p><strong>Contoh sederhana:</strong> Aplikasi men-deserialisasi objek PHP/Java dari cookie pengguna secara langsung. Objek yang dimanipulasi bisa memicu eksekusi kode saat proses deserialisasi (insecure deserialization).</p><p>Lab hari ini mencakup tiga variasi Software or Data Integrity Failures: <strong>Data & Software Integrity Failures</strong> umum (PHP Object Injection, state cookie tanpa signature, timing attack pada signature, update tanpa checksum), <strong>Broken Integrity Verification Mechanisms</strong> (magic hash/type juggling, secret signing bocor, signature yang cuma menutupi sebagian data, checksum dari sumber tidak independen), dan <strong>Untrusted Input Shaping Program State</strong> (variable injection lewat <code>extract()</code>, dynamic dispatch dari input tak tepercaya).</p>',
            'vulns' => [
                'integrity' => [
                    'title' => 'Data & Software Integrity Failures',
                    'summary' => 'Data/objek dari sisi client dipercaya tanpa verifikasi keaslian atau integritas.',
                    'description' => '<p>Integritas berarti memastikan data/kode yang diterima benar-benar berasal dari sumber yang sah dan tidak dimodifikasi di tengah jalan. Tanpa mekanisme verifikasi (signature, checksum yang benar-benar dicek) data client-side - cookie, file upload, paket update - bisa dimanipulasi bebas.</p>',
                    'labs' => [
                        'php-object-injection' => [
                            'title' => 'PHP Object Injection via cookie',
                            'summary' => 'Cookie berisi objek serialize() PHP di-unserialize() mentah.',
                            'description' => '<p><code>unserialize()</code> dipanggil langsung ke data cookie tanpa validasi - properti objek (termasuk role) bisa diubah bebas lewat payload serialize() buatan sendiri.</p>',
                            'app' => 'dataintegrity', 'path' => 'lab1_php_object_injection.php',
                        ],
                        'unsigned-state-cookie' => [
                            'title' => 'State cookie tanpa signature',
                            'summary' => 'Cookie JSON base64 (saldo, role) tidak ditandatangani sama sekali.',
                            'description' => '<p>Server mempercayai penuh isi cookie setelah di-decode - tidak ada HMAC/signature apa pun yang mencegah field di dalamnya diubah bebas.</p>',
                            'app' => 'dataintegrity', 'path' => 'lab2_unsigned_state_cookie.php',
                        ],
                        'timing-unsafe-hmac' => [
                            'title' => 'Signature check pakai == (timing attack)',
                            'summary' => 'Verifikasi HMAC tidak constant-time, signature bisa dipalsukan byte demi byte.',
                            'description' => '<p>Signature memang di-generate dengan HMAC yang benar, tapi verifikasinya memakai perbandingan string <code>==</code> alih-alih <code>hash_equals()</code> yang constant-time - membuka celah timing attack.</p>',
                            'app' => 'dataintegrity', 'path' => 'lab3_timing_unsafe_hmac.php',
                        ],
                        'update-no-checksum' => [
                            'title' => 'Update artifact tanpa checksum',
                            'summary' => 'File update yang diupload diterapkan tanpa verifikasi apa pun.',
                            'description' => '<p>Fitur "apply update" admin menerima file apa saja dan langsung menerapkannya - tidak ada perbandingan checksum/signature terhadap artifact resmi.</p>',
                            'app' => 'dataintegrity', 'path' => 'lab4_update_no_checksum.php',
                        ],
                    ],
                ],
                'broken-integrity-verification' => [
                    'title' => 'Broken Integrity Verification Mechanisms',
                    'summary' => 'Mekanisme verifikasi integritas ADA, tapi masing-masing rusak dengan cara berbeda.',
                    'description' => '<p>Beda dari kategori dasar di atas (yang sama sekali tidak punya mekanisme verifikasi), lab-lab ini menunjukkan bahwa PUNYA signature/checksum/hash comparison saja tidak cukup - kelemahan tipe data, secret yang bocor, cakupan signature yang tidak lengkap, dan sumber pembanding yang tidak independen semuanya bisa membuat mekanisme yang "terlihat benar" tetap gagal melindungi apa pun.</p>',
                    'labs' => [
                        'magic-hash-bypass' => [
                            'title' => 'Magic Hash / Type Juggling Bypass',
                            'summary' => 'Perbandingan hash pakai == menafsirkan string "0e"+digit sebagai notasi ilmiah.',
                            'description' => '<p>Dua hash MD5 yang isinya sama sekali berbeda tapi sama-sama berbentuk "0e" diikuti hanya digit dianggap "sama" oleh operator <code>==</code>, karena keduanya dikonversi jadi angka 0 dulu sebelum dibandingkan.</p>',
                            'app' => 'dataintegrity', 'path' => 'lab5_magic_hash_bypass.php',
                        ],
                        'leaked-signing-secret' => [
                            'title' => 'Signing Secret Bocor di Client-Side JS',
                            'summary' => 'Secret HMAC untuk link reset password juga ada di file JavaScript publik.',
                            'description' => '<p>Mekanisme HMAC-nya sendiri benar, tapi secret yang dipakai menandatangani bukan rahasia sama sekali - ada di file JS yang dimuat publik untuk fitur "live preview".</p>',
                            'app' => 'dataintegrity', 'path' => 'lab6_leaked_signing_secret.php',
                        ],
                        'partial-signature-gap' => [
                            'title' => 'Signature Cuma Menutupi Sebagian Data',
                            'summary' => 'Signature transfer dana cuma mencakup field amount, bukan recipient/currency.',
                            'description' => '<p>Field yang tidak ikut ditandatangani bisa diubah bebas tanpa membuat signature-nya tidak valid - canonicalization yang tidak lengkap membuat proteksi integritasnya cuma parsial.</p>',
                            'app' => 'dataintegrity', 'path' => 'lab7_partial_signature_gap.php',
                        ],
                        'checksum-same-source' => [
                            'title' => 'Checksum dari Sumber yang Sama dengan Artifact',
                            'summary' => 'Checksum "resmi" pembanding datang dari form/party yang sama dengan artifact-nya.',
                            'description' => '<p>Perbandingan checksum-nya benar secara matematis, tapi tidak membuktikan apa pun karena checksum pembanding bukan dari kanal independen yang tepercaya.</p>',
                            'app' => 'dataintegrity', 'path' => 'lab8_checksum_same_source.php',
                        ],
                    ],
                ],
                'untrusted-input-shaping-state' => [
                    'title' => 'Untrusted Input Shaping Program State',
                    'summary' => 'Input eksternal dipercaya untuk membentuk state/kode internal program itu sendiri.',
                    'description' => '<p>Bukan cuma data biasa yang dipercaya begitu saja dari input pengguna - nama variabel internal, bahkan kode apa yang dijalankan, ikut ditentukan oleh apa yang dikirim client.</p>',
                    'labs' => [
                        'extract-variable-injection' => [
                            'title' => 'Variable Injection Lewat extract()',
                            'summary' => 'extract($_GET) menimpa variabel internal yang seharusnya cuma dari server.',
                            'description' => '<p>Variabel seperti <code>$is_admin</code> yang diinisialisasi aman di awal skrip langsung tertimpa oleh parameter URL dengan nama yang sama - mengulang masalah <code>register_globals</code> secara manual.</p>',
                            'app' => 'dataintegrity', 'path' => 'lab9_extract_variable_injection.php',
                        ],
                        'untrusted-dynamic-dispatch' => [
                            'title' => 'Dynamic Dispatch dari Input Tak Tepercaya',
                            'summary' => 'Dispatcher memanggil fungsi apa pun yang namanya cocok dengan input, bukan allowlist.',
                            'description' => '<p><code>function_exists($action)</code> dipakai untuk memutuskan boleh-tidaknya suatu action dijalankan - fungsi internal yang tidak pernah ditautkan ke UI manapun tetap reachable selama namanya bisa ditebak.</p>',
                            'app' => 'dataintegrity', 'path' => 'lab10_untrusted_dynamic_dispatch.php',
                        ],
                    ],
                ],
            ],
        ],
        'a09-logging-alerting-failures' => [
            'code' => 'A09:2025', 'title' => 'Logging & Alerting Failures', 'status' => 'active',
            'summary' => 'Serangan tidak tercatat sehingga terlambat terdeteksi.',
            'description' => '<p>Tanpa log dan alert yang memadai, tim keamanan tidak akan tahu bahwa sedang atau sudah terjadi serangan, sehingga respons insiden menjadi sangat lambat - bahkan bisa baru diketahui berbulan-bulan kemudian.</p><p><strong>Contoh sederhana:</strong> Ada 50.000 percobaan login gagal ke satu akun dalam semalam, tapi tidak ada log maupun notifikasi apa pun yang terpicu ke tim keamanan.</p><p>Lab hari ini mencakup dua variasi Logging & Alerting Failures: <strong>Logging & Alerting Failures</strong> umum (log injection/forgery, log injection ke Stored XSS, tidak ada alert brute-force, data sensitif di log), dan <strong>Alerting & Log Integrity Gaps</strong> (threshold alert yang bisa dievasi lewat pacing, audit log yang bisa dihapus user biasa, kebocoran lewat console.log di browser, dan log yang ada tapi kurang konteks untuk investigasi).</p>',
            'vulns' => [
                'logging' => [
                    'title' => 'Logging & Alerting Failures',
                    'summary' => 'Log yang tidak lengkap, tidak diproteksi, atau tidak pernah memicu alert.',
                    'description' => '<p>Logging yang buruk bukan cuma soal "tidak ada log" - log yang menulis input mentah tanpa sanitasi, dashboard yang merendernya tanpa encoding, atau log yang menyimpan data sensitif apa adanya semuanya sama-sama berbahaya.</p>',
                    'labs' => [
                        'log-injection-forgery' => [
                            'title' => 'Log injection (log forgery)',
                            'summary' => 'Newline di input memalsukan baris log yang tidak pernah terjadi.',
                            'description' => '<p>Username yang dicatat ke log tidak difilter dari karakter newline - baris log palsu bisa disisipkan untuk menyesatkan investigasi insiden.</p>',
                            'app' => 'loggingfail', 'path' => 'lab1_log_injection_forgery.php',
                        ],
                        'log-injection-stored-xss' => [
                            'title' => 'Log injection -> Stored XSS di dashboard admin',
                            'summary' => 'Dashboard log admin merender entry log sebagai HTML mentah.',
                            'description' => '<p>Log dianggap "data internal tepercaya" sehingga dirender tanpa <code>htmlspecialchars()</code> - padahal isinya tetap berasal dari input user yang tidak tepercaya.</p>',
                            'app' => 'loggingfail', 'path' => 'lab2_log_injection_stored_xss.php',
                        ],
                        'no-alerting-bruteforce' => [
                            'title' => 'Tidak ada log/alert untuk brute force',
                            'summary' => 'Ratusan percobaan login gagal tidak meninggalkan jejak sama sekali.',
                            'description' => '<p>Hanya login sukses yang dicatat - percobaan gagal (sekalipun ratusan dalam hitungan detik) tidak pernah muncul di log manapun, menunjukkan celah deteksi yang nyata.</p>',
                            'app' => 'loggingfail', 'path' => 'lab3_no_alerting_bruteforce.php',
                        ],
                        'sensitive-data-in-logs' => [
                            'title' => 'Data sensitif tercatat mentah di log',
                            'summary' => 'Log debug menyimpan nomor kartu & CVV pengguna lain dalam plaintext.',
                            'description' => '<p>Log "untuk debugging" mencatat seluruh data form apa adanya, termasuk field sensitif - begitu log ini bocor/diakses, data pengguna lain ikut terekspos.</p>',
                            'app' => 'loggingfail', 'path' => 'lab4_sensitive_data_in_logs.php',
                        ],
                    ],
                ],
                'alerting-log-integrity-gaps' => [
                    'title' => 'Alerting & Log Integrity Gaps',
                    'summary' => 'Mekanisme logging/alerting ADA, tapi masing-masing punya celah yang berbeda.',
                    'description' => '<p>Beda dari kategori dasar di atas (yang sama sekali tidak punya log/alert), lab-lab ini menunjukkan bahwa PUNYA mekanisme logging/alerting saja tidak cukup - threshold yang bisa dievasi, log yang bisa dihapus pelakunya sendiri, kanal logging yang tidak disadari (browser console), dan log yang ada tapi tidak cukup detail semuanya membuat deteksi/investigasi tetap gagal walau "terlihat" sudah ada.</p>',
                    'labs' => [
                        'threshold-evasion' => [
                            'title' => 'Alert threshold bisa dievasi lewat pacing',
                            'summary' => 'Alert cuma terpicu kalau lebih dari N kegagalan dalam satu window waktu tetap.',
                            'description' => '<p>Mengirim percobaan gagal dalam batch yang selalu di bawah ambang batas per window membuat total ratusan percobaan tidak pernah memicu satu alert pun, walau alert-nya sendiri secara teknis berfungsi.</p>',
                            'app' => 'loggingfail', 'path' => 'lab5_threshold_evasion.php',
                        ],
                        'log-tampering' => [
                            'title' => 'Audit log bisa dihapus lewat fitur "hapus riwayat"',
                            'summary' => 'Fitur privasi biasa ternyata menghapus tabel audit log investigasi yang sama.',
                            'description' => '<p>"Hapus Riwayat Aktivitas Saya" yang terlihat seperti fitur privasi biasa ternyata menghapus data audit log yang sama yang dipakai tim SOC untuk investigasi keamanan.</p>',
                            'app' => 'loggingfail', 'path' => 'lab6_log_tampering.php',
                        ],
                        'client-side-console-logging' => [
                            'title' => 'Kebocoran lewat console.log() di browser',
                            'summary' => 'Token sesi & API key ter-log ke console browser, bukan log server.',
                            'description' => '<p>Data sensitif bocor lewat kanal yang sama sekali berbeda dari log server - console browser tiap pengunjung, terlihat siapa pun yang membuka DevTools atau lewat ekstensi browser yang jahat.</p>',
                            'app' => 'loggingfail', 'path' => 'lab7_client_side_console_logging.php',
                        ],
                        'insufficient-log-context' => [
                            'title' => 'Log ada tapi kurang konteks untuk investigasi',
                            'summary' => 'Log transaksi cuma catat timestamp & jumlah, tanpa user/IP/session.',
                            'description' => '<p>Log secara teknis "ada", tapi tidak mencatat identitas yang mengaitkan (user, IP, session, request ID) - investigasi insiden jadi praktis mustahil walau log-nya sendiri tidak kosong.</p>',
                            'app' => 'loggingfail', 'path' => 'lab8_insufficient_log_context.php',
                        ],
                    ],
                ],
            ],
        ],
        'a10-mishandling-exceptional-conditions' => [
            'code' => 'A10:2025', 'title' => 'Mishandling of Exceptional Conditions', 'status' => 'active',
            'summary' => 'Penanganan error/kondisi tak terduga yang buruk membuka celah baru.',
            'description' => '<p>Terjadi ketika aplikasi tidak menangani error, input tak terduga, atau kegagalan komponen lain dengan benar - sehingga informasi sensitif bocor atau alur keamanan bisa dilewati saat sistem berada dalam kondisi tidak normal.</p><p><strong>Contoh sederhana:</strong> Saat koneksi ke layanan verifikasi pembayaran gagal/timeout, aplikasi "fail open" dan tetap menganggap transaksi berhasil, alih-alih menolaknya.</p><p>Lab hari ini mencakup dua variasi Mishandling of Exceptional Conditions: <strong>Mishandling of Exceptional Conditions</strong> umum (fail-open saat timeout, error message bocor, race condition, fail-open di blok catch), dan <strong>Non-Atomic &amp; Type-Unsafe Exceptional Handling</strong> (retry yang menyebabkan double charge, transaksi multi-step tanpa rollback, filter <code>in_array()</code> yang bisa dilewati lewat tipe data tak terduga, dan fail-open dari response API yang gagal di-parse).</p>',
            'vulns' => [
                'exceptions' => [
                    'title' => 'Mishandling of Exceptional Conditions',
                    'summary' => 'Error, input tak terduga, atau kegagalan komponen lain ditangani dengan cara yang justru membuka celah.',
                    'description' => '<p>Kode yang berjalan mulus untuk kasus normal seringkali punya asumsi tersembunyi yang tidak pernah diuji: bagaimana kalau layanan eksternal gagal? bagaimana kalau input di luar rentang yang diharapkan? bagaimana kalau dua request datang nyaris bersamaan? Default yang aman untuk semua kondisi ini adalah <em>menolak</em>, bukan meloloskan.</p>',
                    'labs' => [
                        'fail-open-payment-timeout' => [
                            'title' => 'Fail-open saat payment gateway timeout',
                            'summary' => 'Verifikasi pembayaran yang gagal tetap dianggap berhasil.',
                            'description' => '<p>Saat pemanggilan layanan verifikasi pembayaran melempar exception (timeout/gagal), blok catch-nya malah mengasumsikan pembayaran berhasil alih-alih menolak transaksi.</p>',
                            'app' => 'exceptcond', 'path' => 'lab1_fail_open_payment_timeout.php',
                        ],
                        'error-message-info-leak' => [
                            'title' => 'Error message bocor dari input tak terduga',
                            'summary' => 'Input di luar rentang wajar memicu error PHP mentah yang membocorkan internal.',
                            'description' => '<p>Tidak ada validasi untuk kasus tepi (nol, negatif, tipe salah) - PHP menampilkan error bawaan lengkap dengan path file & baris kode saat kasus-kasus ini terjadi.</p>',
                            'app' => 'exceptcond', 'path' => 'lab2_error_message_info_leak.php',
                        ],
                        'race-condition-giftcard' => [
                            'title' => 'Race condition di pengecekan "sudah dipakai"',
                            'summary' => 'Redeem gift card yang sama dua kali lewat request paralel.',
                            'description' => '<p>Cek "sudah dipakai" dan penandaan "sudah dipakai" terjadi di dua langkah terpisah dengan jeda di antaranya - request yang dikirim nyaris bersamaan bisa sama-sama lolos pengecekan sebelum salah satunya sempat menandai kartu sebagai terpakai.</p>',
                            'app' => 'exceptcond', 'path' => 'lab3_race_condition_giftcard.php',
                        ],
                        'failopen-catch-block' => [
                            'title' => 'Fail-open di dalam blok catch keamanan',
                            'summary' => 'Input aneh membuat pengecekan akses error, lalu catch block meloloskan akses.',
                            'description' => '<p>Pengecekan kepemilikan dibungkus try/catch - input yang tidak terduga (mis. tipe data salah) membuat pengecekan itu sendiri melempar exception, dan blok catch-nya meloloskan akses alih-alih menolak.</p>',
                            'app' => 'exceptcond', 'path' => 'lab4_failopen_catch_block.php',
                        ],
                    ],
                ],
                'non-atomic-type-unsafe-handling' => [
                    'title' => 'Non-Atomic & Type-Unsafe Exceptional Handling',
                    'summary' => 'Retry, multi-step transaction, dan filter yang gagal menangani kondisi tak terduga dengan aman.',
                    'description' => '<p>Kondisi tak terduga bukan cuma soal exception yang melempar error - operasi yang di-retry tanpa idempotency, transaksi multi-step tanpa rollback, tipe data yang tidak divalidasi sebelum masuk logika filter, dan default value yang diam-diam menggantikan hasil pengecekan keamanan semuanya adalah bentuk lain dari penanganan kondisi tak terduga yang gagal.</p>',
                    'labs' => [
                        'duplicate-charge-retry' => [
                            'title' => 'Retry setelah timeout menyebabkan double charge',
                            'summary' => 'Operasi non-idempotent di-retry tanpa mengecek apakah request sebelumnya sudah berhasil.',
                            'description' => '<p>Saat response pembayaran "timeout" (padahal charge-nya sudah benar-benar diproses di backend), klik "Coba Lagi" mengirim ulang charge yang sama tanpa idempotency key - order yang sama ter-charge dua kali.</p>',
                            'app' => 'exceptcond', 'path' => 'lab5_duplicate_charge_retry.php',
                        ],
                        'no-rollback-partial-failure' => [
                            'title' => 'Transaksi multi-step tanpa rollback',
                            'summary' => 'Step kedua transfer dana gagal, tapi step pertama (pengurangan saldo) tidak pernah dibatalkan.',
                            'description' => '<p>Transfer dana dilakukan lewat dua step terpisah tanpa transaction/rollback yang membungkus keduanya - kalau step kedua gagal karena input tak terduga, saldo yang sudah dikurangi di step pertama tetap hilang begitu saja.</p>',
                            'app' => 'exceptcond', 'path' => 'lab6_no_rollback_partial_failure.php',
                        ],
                        'type-confusion-filter-bypass' => [
                            'title' => 'Filter in_array() dilewati lewat tipe data tak terduga',
                            'summary' => 'Mengirim field sebagai array alih-alih string membuat blocklist tidak pernah cocok.',
                            'description' => '<p>Filter blocklist mengasumsikan input selalu string - mengirim field yang sama sebagai array (<code>field[]=admin</code>) membuat <code>in_array()</code> tidak pernah menemukan kecocokan, melewati filter dengan nilai yang identik.</p>',
                            'app' => 'exceptcond', 'path' => 'lab7_type_confusion_filter_bypass.php',
                        ],
                        'malformed-response-fail-open' => [
                            'title' => 'Fail-open dari response API yang gagal di-parse',
                            'summary' => 'Response fraud-check yang bentuknya tak terduga membuat pengecekan diam-diam dianggap aman.',
                            'description' => '<p>Default value dari operator <code>??</code> dipakai untuk field keamanan - saat response API berbentuk tak terduga (bukan timeout, tapi shape yang salah), field yang hilang diam-diam dianggap "tidak flagged" alih-alih "belum pernah benar-benar dicek".</p>',
                            'app' => 'exceptcond', 'path' => 'lab8_malformed_response_fail_open.php',
                        ],
                    ],
                ],
            ],
        ],
    ];
}

function find_category($id) {
    $all = owasp_categories();
    return $all[$id] ?? null;
}

function find_vuln($cat_id, $vuln_id) {
    $cat = find_category($cat_id);
    if (!$cat || empty($cat['vulns'][$vuln_id])) return null;
    return $cat['vulns'][$vuln_id];
}

function find_lab($cat_id, $vuln_id, $lab_id) {
    $vuln = find_vuln($cat_id, $vuln_id);
    if (!$vuln || empty($vuln['labs'][$lab_id])) return null;
    return $vuln['labs'][$lab_id];
}
