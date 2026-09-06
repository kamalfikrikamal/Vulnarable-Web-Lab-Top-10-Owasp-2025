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
            'code' => 'A01:2025', 'title' => 'Broken Access Control', 'status' => 'soon',
            'summary' => 'Pembatasan hak akses tidak diterapkan dengan benar.',
            'description' => '<p>Terjadi ketika aplikasi gagal memastikan bahwa pengguna hanya bisa mengakses data atau fungsi yang menjadi haknya. Akibatnya, pengguna biasa bisa mengakses data pengguna lain, atau bahkan fungsi khusus admin.</p><p><strong>Contoh sederhana:</strong> URL <code>/invoice?id=1001</code> menampilkan invoice milik kita. Jika kita ubah manual menjadi <code>/invoice?id=1002</code> dan ternyata bisa melihat invoice milik orang lain tanpa otorisasi, itu disebut <em>Insecure Direct Object Reference (IDOR)</em>, salah satu bentuk Broken Access Control.</p>',
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
            'code' => 'A04:2025', 'title' => 'Cryptographic Failures', 'status' => 'soon',
            'summary' => 'Data sensitif tidak terlindungi karena kriptografi lemah/tidak ada.',
            'description' => '<p>Terjadi saat data sensitif (password, kartu kredit, data pribadi) disimpan atau dikirim tanpa enkripsi yang memadai, atau memakai algoritma yang sudah usang.</p><p><strong>Contoh sederhana:</strong> Password pengguna disimpan sebagai MD5 tanpa salt di database. Jika database bocor, hash MD5 tanpa salt sangat mudah di-crack menjadi password asli.</p>',
        ],
        'a05-injection' => [
            'code' => 'A05:2025', 'title' => 'Injection', 'status' => 'active',
            'summary' => 'Input pengguna tercampur dengan kode/perintah yang dijalankan sistem.',
            'description' => '<p>Injection terjadi ketika aplikasi mengirim data yang tidak tepercaya (input dari pengguna) ke sebuah <em>interpreter</em> - misalnya database (SQL), browser (HTML/JavaScript), atau shell sistem operasi - dan data tersebut ikut ditafsirkan sebagai bagian dari perintah/kode, bukan sekadar data biasa.</p><p><strong>Contoh sederhana:</strong> Sebuah form login membangun query seperti ini secara langsung dari input pengguna:</p><div class="example">SELECT * FROM users WHERE username = \'$username\' AND password = \'$password\'</div><p>Jika pengguna mengisi username dengan <code>admin\' -- -</code>, maka tanda kutip yang seharusnya jadi "data" malah menutup string SQL lebih awal, dan <code>-- </code> mengubah sisa query jadi komentar sehingga pengecekan password terlewati begitu saja. Prinsip yang sama berlaku pada XSS (data tercampur ke dalam HTML/JavaScript yang dieksekusi browser) dan Command Injection (data tercampur ke dalam perintah shell sistem operasi).</p><p>Lab yang kita pelajari hari ini - <strong>SQL Injection</strong>, <strong>Cross-Site Scripting (XSS)</strong>, dan <strong>OS Command Injection</strong> - semuanya adalah variasi dari masalah dasar yang sama ini.</p>',
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
            ],
        ],
        'a06-insecure-design' => [
            'code' => 'A06:2025', 'title' => 'Insecure Design', 'status' => 'soon',
            'summary' => 'Kelemahan berasal dari desain/arsitektur, bukan sekadar bug.',
            'description' => '<p>Berbeda dari kesalahan implementasi, Insecure Design adalah kelemahan yang sudah tertanam sejak tahap perancangan alur/fitur aplikasi - sehingga tidak bisa diperbaiki hanya dengan menambal kode, melainkan perlu didesain ulang.</p><p><strong>Contoh sederhana:</strong> Fitur "lupa password" mengirim kode OTP 4 digit tanpa batas percobaan (rate limiting), sehingga penyerang bisa mencoba 10.000 kombinasi dengan cepat sampai berhasil.</p>',
        ],
        'a07-authentication-failures' => [
            'code' => 'A07:2025', 'title' => 'Authentication Failures', 'status' => 'soon',
            'summary' => 'Kelemahan pada proses login & manajemen sesi.',
            'description' => '<p>Mencakup segala kelemahan pada proses memverifikasi identitas pengguna dan menjaga sesi login mereka tetap aman setelahnya.</p><p><strong>Contoh sederhana:</strong> Setelah pengguna menekan "Logout", session token lama masih tetap valid dan bisa dipakai untuk mengakses akun jika tokennya berhasil dicuri sebelumnya.</p>',
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
