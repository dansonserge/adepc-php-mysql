<?php
declare(strict_types=1);

/*
 * Dev/test installer (the web installer at /install/ does the same on cPanel).
 * Drops and recreates every table, loads the seed and creates an admin.
 *
 *   php tools/install-cli.php --host=127.0.0.1 --port=3307 --db=adepc --user=adepc --pass=… \
 *       --admin-email=admin@example.com --admin-pass=… [--site-url=http://localhost:8080]
 */
define('PUBLIC_DIR', dirname(__DIR__) . '/public');
require dirname(__DIR__) . '/app/bootstrap.php';

use Adepc\Config;
use Adepc\Db;
use Adepc\Seeder;

$o = getopt('', ['host:', 'port:', 'db:', 'user:', 'pass:', 'admin-email:', 'admin-pass:', 'admin-name::', 'site-url::', 'config-host::', 'keep-config']);
$db = ['host' => $o['host'] ?? '127.0.0.1', 'port' => (int) ($o['port'] ?? 3306), 'name' => $o['db'], 'user' => $o['user'], 'pass' => $o['pass']];
$pdo = Db::connect($db);
foreach (array_reverse(array_merge(Seeder::TABLES, ['admins', 'login_attempts'])) as $t) {
    $pdo->exec("DROP TABLE IF EXISTS `$t`");
}
Seeder::schema($pdo);
Seeder::seed($pdo);
Seeder::createAdmin($pdo, $o['admin-name'] ?? 'Admin', $o['admin-email'], $o['admin-pass'], 'fr');
if (!empty($o['site-url'])) {
    $pdo->prepare("UPDATE settings SET value = ? WHERE skey = 'site.url'")->execute([$o['site-url']]);
}
if (!isset($o['keep-config'])) {
    // The web container reaches the database by its service name.
    Config::write(['db' => ['host' => $o['config-host'] ?? $db['host']] + $db, 'app_key' => bin2hex(random_bytes(32)), 'debug' => false]);
}
array_map('unlink', glob(APP_DIR . '/storage/cache/*.php') ?: []);
echo "installed\n";
