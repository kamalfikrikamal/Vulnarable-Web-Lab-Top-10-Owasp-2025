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
            'code' => 'A02:2025', 'title' => 'Security Misconfiguration', 'status' => 'soon',
            'summary' => 'Konfigurasi keamanan server/aplikasi yang salah atau longgar.',
            'description' => '<p>Terjadi saat server, framework, database, atau layanan cloud dikonfigurasi secara tidak aman - biasanya karena memakai pengaturan bawaan (default) tanpa dikeraskan (hardening).</p><p><strong>Contoh sederhana:</strong> Mode debug Laravel/Django masih aktif di production sehingga error menampilkan detail source code dan environment variable (termasuk password database) ke publik.</p>',
        ],
        'a03-software-supply-chain-failures' => [
            'code' => 'A03:2025', 'title' => 'Software Supply Chain Failures', 'status' => 'soon',
            'summary' => 'Risiko dari dependency, pipeline build, dan komponen pihak ketiga.',
            'description' => '<p>Aplikasi modern bergantung pada ratusan library pihak ketiga dan pipeline CI/CD otomatis. Jika salah satu mata rantai ini disusupi, aplikasi ikut terdampak walau kode yang kita tulis sendiri aman.</p><p><strong>Contoh sederhana:</strong> Penyerang mengunggah paket npm dengan nama mirip library populer (typosquatting, mis. <code>expres</code> alih-alih <code>express</code>); developer yang salah ketik saat install tanpa sadar menjalankan kode berbahaya tersebut.</p>',
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
            'code' => 'A06:2025', 'title' => 'Insecure Design', 'status' => 'soon',
            'summary' => 'Kelemahan berasal dari desain/arsitektur, bukan sekadar bug.',
            'description' => '<p>Berbeda dari kesalahan implementasi, Insecure Design adalah kelemahan yang sudah tertanam sejak tahap perancangan alur/fitur aplikasi - sehingga tidak bisa diperbaiki hanya dengan menambal kode, melainkan perlu didesain ulang.</p><p><strong>Contoh sederhana:</strong> Fitur "lupa password" mengirim kode OTP 4 digit tanpa batas percobaan (rate limiting), sehingga penyerang bisa mencoba 10.000 kombinasi dengan cepat sampai berhasil.</p>',
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
            'code' => 'A08:2025', 'title' => 'Software or Data Integrity Failures', 'status' => 'soon',
            'summary' => 'Aplikasi mempercayai kode/data dari sumber yang tidak terverifikasi.',
            'description' => '<p>Terjadi ketika aplikasi menerima update, plugin, atau data terserialisasi dari sumber luar tanpa memverifikasi keasliannya (mis. lewat digital signature), sehingga rentan dimanipulasi.</p><p><strong>Contoh sederhana:</strong> Aplikasi men-deserialisasi objek PHP/Java dari cookie pengguna secara langsung. Objek yang dimanipulasi bisa memicu eksekusi kode saat proses deserialisasi (insecure deserialization).</p>',
        ],
        'a09-logging-alerting-failures' => [
            'code' => 'A09:2025', 'title' => 'Logging & Alerting Failures', 'status' => 'soon',
            'summary' => 'Serangan tidak tercatat sehingga terlambat terdeteksi.',
            'description' => '<p>Tanpa log dan alert yang memadai, tim keamanan tidak akan tahu bahwa sedang atau sudah terjadi serangan, sehingga respons insiden menjadi sangat lambat - bahkan bisa baru diketahui berbulan-bulan kemudian.</p><p><strong>Contoh sederhana:</strong> Ada 50.000 percobaan login gagal ke satu akun dalam semalam, tapi tidak ada log maupun notifikasi apa pun yang terpicu ke tim keamanan.</p>',
        ],
        'a10-mishandling-exceptional-conditions' => [
            'code' => 'A10:2025', 'title' => 'Mishandling of Exceptional Conditions', 'status' => 'soon',
            'summary' => 'Penanganan error/kondisi tak terduga yang buruk membuka celah baru.',
            'description' => '<p>Terjadi ketika aplikasi tidak menangani error, input tak terduga, atau kegagalan komponen lain dengan benar - sehingga informasi sensitif bocor atau alur keamanan bisa dilewati saat sistem berada dalam kondisi tidak normal.</p><p><strong>Contoh sederhana:</strong> Saat koneksi ke layanan verifikasi pembayaran gagal/timeout, aplikasi "fail open" dan tetap menganggap transaksi berhasil, alih-alih menolaknya.</p>',
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
