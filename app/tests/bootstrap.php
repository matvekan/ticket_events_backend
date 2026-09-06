<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_ENV'] ?? null) {
    $_ENV['APP_ENV'] = $_SERVER['APP_ENV'];
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
