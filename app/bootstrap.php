<?php
declare(strict_types=1);

define('APP_DIR', __DIR__);
if (!defined('PUBLIC_DIR')) {
    define('PUBLIC_DIR', dirname(__DIR__) . '/public');
}

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'Adepc\\', 6) === 0) {
        $file = APP_DIR . '/src/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    } elseif (strncmp($class, 'PHPMailer\\PHPMailer\\', 20) === 0) {
        $file = APP_DIR . '/vendor/phpmailer/src/' . substr($class, 20) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require APP_DIR . '/src/helpers.php';

ini_set('log_errors', '1');
ini_set('error_log', APP_DIR . '/storage/logs/php-' . date('Y-m') . '.log');
error_reporting(E_ALL);
ini_set('display_errors', Adepc\Config::get('debug') ? '1' : '0');
mb_internal_encoding('UTF-8');
