<?php
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/.env.php');

use htmxthing\Application;

$app = new Application($conf);
$app->run();
