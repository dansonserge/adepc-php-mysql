<?php
declare(strict_types=1);

// Front controller. On cPanel the app/ folder sits beside public_html
// (recommended) or inside it, where app/.htaccess denies web access.
$candidates = [__DIR__ . '/../app', __DIR__ . '/app', dirname(__DIR__) . '/adepc-app'];
foreach ($candidates as $dir) {
    if (is_file($dir . '/bootstrap.php')) {
        define('PUBLIC_DIR', __DIR__);
        require $dir . '/bootstrap.php';
        Adepc\App::run();
        exit;
    }
}
http_response_code(500);
exit;
