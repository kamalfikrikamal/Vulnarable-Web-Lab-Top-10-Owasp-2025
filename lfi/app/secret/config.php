<?php
// File ini SENGAJA ditaruh di luar webroot (/var/www/secret/, bukan /var/www/html/) supaya
// tidak bisa diakses langsung lewat HTTP - hanya bisa dibaca lewat kerentanan LFI/path
// traversal di aplikasi web-nya.
define('DB_HOST', 'internal-db.local');
define('DB_USER', 'app_service');
define('DB_PASS', 'S3cr3t_DB_P4ssw0rd!');
define('FLAG', 'LFI2-outside-webroot-file-read');
