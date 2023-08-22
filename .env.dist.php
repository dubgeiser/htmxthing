<?php
#
# Default configuation.
#
# Copy this file to `.env.php` and edit to suit your needs.
#

$conf = [
    # DB settings, you should at least change user and pass.
    'db' => [
        'dsn' => 'mysql:dbname=test;socket=/tmp/mysql.sock',
        'user' => '',
        'pass' => ''
    ],

    # Optional, no need to change if these defaults are all right for you.
    'debug' => true,
    'template_dir' => __DIR__ . '/../../templates',
    'template_cache_dir' => __DIR__ . '/../../templates/cached_templates',
];
