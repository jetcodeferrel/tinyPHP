<?php
declare(strict_types=1);

use TinyPHP\Config\Env;
use TinyPHP\Security\Csrf;

require dirname(__DIR__) . '/vendor/autoload.php';

Env::load(dirname(__DIR__) . '/.env');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('UTC');
