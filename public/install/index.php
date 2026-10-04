<?php
declare(strict_types=1);

/*
 * One-time web installer. Locks itself once an admin account exists.
 */
$public = dirname(__DIR__);
foreach ([$public . '/../app', $public . '/app', dirname($public) . '/adepc-app'] as $dir) {
    if (is_file($dir . '/bootstrap.php')) {
        define('PUBLIC_DIR', $public);
        require $dir . '/bootstrap.php';
        break;
    }
}
if (!defined('APP_DIR')) {
    http_response_code(500);
    exit('app/ folder not found');
}

use Adepc\Config;
use Adepc\Db;
use Adepc\Seeder;

header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$lang = in_array($_GET['lang'] ?? $_POST['lang'] ?? '', ['fr', 'en'], true) ? ($_GET['lang'] ?? $_POST['lang']) : 'fr';
$L = (array) json_decode((string) file_get_contents(__DIR__ . '/lang/' . $lang . '.json'), true);
$s = static fn (string $k, array $v = []) => strtr($L[$k] ?? $k, array_combine(array_map(static fn ($x) => '{' . $x . '}', array_keys($v)), array_values($v)) ?: []);

$installed = false;
if (Config::exists()) {
    try {
        $installed = Seeder::installed(Db::pdo());
    } catch (Throwable) {
        $installed = false;
    }
}

$checks = [
    'php' => PHP_VERSION_ID >= 80100,
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'gd' => function_exists('imagewebp') && !empty(gd_info()['WebP Support']),
    'mbstring' => extension_loaded('mbstring'),
    'fileinfo' => extension_loaded('fileinfo'),
];
$writable = [APP_DIR, APP_DIR . '/storage/cache', APP_DIR . '/storage/logs', PUBLIC_DIR . '/media', PUBLIC_DIR . '/media/images', PUBLIC_DIR . '/media/video', PUBLIC_DIR . '/media/icons'];
foreach ($writable as $path) {
    $checks['w:' . $path] = is_dir($path) && is_writable($path);
}
$ready = !in_array(false, $checks, true);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$values = [
    'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'site_url' => $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'admin_name' => '', 'admin_email' => '', 'admin_lang' => $lang,
];
$error = null;
$done = false;

// Double-submit token: the installer has no session.
if (empty($_COOKIE['adepc_install'])) {
    $token = bin2hex(random_bytes(16));
    setcookie('adepc_install', $token, ['path' => '/install', 'httponly' => true, 'samesite' => 'Strict']);
} else {
    $token = (string) $_COOKIE['adepc_install'];
}

if (!$installed && $ready && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    foreach ($values as $k => $v) {
        $values[$k] = trim((string) ($_POST[$k] ?? $v));
    }
    $password = (string) ($_POST['admin_pass'] ?? '');
    if (!hash_equals($token, (string) ($_POST['token'] ?? ''))) {
        $error = $s('errorToken');
    } elseif ($values['db_name'] === '' || $values['db_user'] === '' || $values['admin_name'] === ''
        || !filter_var($values['admin_email'], FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 10
        || !filter_var($values['site_url'], FILTER_VALIDATE_URL)) {
        $error = $s('errorFields');
    } else {
        $db = ['host' => $values['db_host'], 'port' => (int) $values['db_port'], 'name' => $values['db_name'], 'user' => $values['db_user'], 'pass' => $values['db_pass']];
        try {
            $pdo = Db::connect($db);
            if (Seeder::installed($pdo)) {
                $error = $s('errorNotEmpty');
            } else {
                Seeder::schema($pdo);
                if ((int) $pdo->query('SELECT COUNT(*) FROM locales')->fetchColumn() === 0) {
                    Seeder::seed($pdo);
                }
                Seeder::createAdmin($pdo, $values['admin_name'], $values['admin_email'], $password, in_array($values['admin_lang'], ['fr', 'en'], true) ? $values['admin_lang'] : 'fr');
                $pdo->prepare("UPDATE settings SET value = ? WHERE skey = 'site.url'")->execute([rtrim($values['site_url'], '/')]);
                $pdo->prepare("UPDATE settings SET value = ? WHERE skey IN ('mail.to') AND (value IS NULL OR value = '')")->execute([$values['admin_email']]);
                if (!is_writable(APP_DIR)) {
                    $error = $s('errorWrite', ['path' => APP_DIR]);
                } else {
                    Config::write(['db' => $db, 'app_key' => bin2hex(random_bytes(32)), 'debug' => false]);
                    foreach (glob(APP_DIR . '/storage/cache/*.php') ?: [] as $f) {
                        @unlink($f);
                    }
                    $done = true;
                }
            }
        } catch (Throwable $e) {
            $error = $s('errorDb', ['error' => $e->getMessage()]);
        }
    }
}

$input = 'block w-full border border-ink/25 bg-white px-3 py-2.5 text-body focus:border-blue focus:outline-none';
$label = 'caption mb-2 block text-stone';
$field = static function (string $name, string $text, string $type = 'text', string $extra = '') use ($values, $input, $label): string {
    return '<div><label for="' . $name . '" class="' . $label . '">' . htmlspecialchars($text) . '</label><input id="' . $name . '" name="' . $name . '" type="' . $type . '" value="' . ($type === 'password' ? '' : htmlspecialchars($values[$name] ?? '')) . '" class="' . $input . '"' . $extra . '></div>';
};
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= htmlspecialchars($s('title')) ?></title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="bg-ink text-white">
<main class="mx-auto max-w-3xl px-4 py-12">
  <div class="flex items-center justify-between gap-4">
    <h1 class="display text-h1"><?= htmlspecialchars($s('title')) ?></h1>
    <a href="?lang=<?= $lang === 'fr' ? 'en' : 'fr' ?>" class="text-nav font-semibold uppercase underline underline-offset-4"><?= htmlspecialchars($s('language')) ?></a>
  </div>
  <div class="mt-8 bg-paper p-6 text-ink md:p-8">
  <?php if ($installed): ?>
    <p class="text-lead"><?= htmlspecialchars($s('already')) ?></p>
    <p class="mt-6"><a href="/admin" class="inline-flex min-h-11 items-center bg-ink px-5 text-nav font-semibold text-white uppercase"><?= htmlspecialchars($s('openAdmin')) ?></a></p>
  <?php elseif ($done): ?>
    <h2 class="display text-h2"><?= htmlspecialchars($s('done')) ?></h2>
    <p class="mt-4 text-body"><?= htmlspecialchars($s('doneLead')) ?></p>
    <p class="mt-6 flex flex-wrap gap-3"><a href="/" class="inline-flex min-h-11 items-center bg-gold px-5 text-nav font-semibold text-ink uppercase"><?= htmlspecialchars($s('openSite')) ?></a><a href="/admin" class="inline-flex min-h-11 items-center bg-ink px-5 text-nav font-semibold text-white uppercase"><?= htmlspecialchars($s('openAdmin')) ?></a></p>
  <?php else: ?>
    <p class="text-body"><?= htmlspecialchars($s('lead')) ?></p>
    <h2 class="display mt-8 text-h3"><?= htmlspecialchars($s('requirements')) ?></h2>
    <ul class="mt-3 space-y-1 text-small">
      <?php foreach ($checks as $k => $ok): ?>
      <li class="flex justify-between gap-4 border-b border-ink/10 py-1.5"><span><?= htmlspecialchars(str_starts_with($k, 'w:') ? $s('writable', ['path' => substr($k, 2)]) : $s($k)) ?></span><span class="font-semibold <?= $ok ? 'text-blue' : 'text-ember' ?>"><?= htmlspecialchars($s($ok ? 'ok' : 'missing')) ?></span></li>
      <?php endforeach ?>
    </ul>
    <?php if (!$ready): ?>
    <p role="alert" class="mt-4 border-l-4 border-ember pl-3 text-small font-semibold"><?= htmlspecialchars($s('fixFirst')) ?></p>
    <?php else: ?>
    <form method="post" class="mt-8 space-y-8">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"><input type="hidden" name="lang" value="<?= $lang ?>">
      <?php if ($error): ?><p role="alert" class="border-l-4 border-ember pl-3 text-small font-semibold"><?= htmlspecialchars($error) ?></p><?php endif ?>
      <fieldset class="space-y-4"><legend class="display text-h3"><?= htmlspecialchars($s('database')) ?></legend>
        <p class="text-small text-stone"><?= htmlspecialchars($s('databaseHelp')) ?></p>
        <div class="grid gap-4 md:grid-cols-[1fr_8rem]"><?= $field('db_host', $s('dbHost')) ?><?= $field('db_port', $s('dbPort'), 'number') ?></div>
        <?= $field('db_name', $s('dbName'), 'text', ' required') ?><?= $field('db_user', $s('dbUser'), 'text', ' required autocomplete="off"') ?><?= $field('db_pass', $s('dbPass'), 'password', ' autocomplete="off"') ?>
      </fieldset>
      <fieldset class="space-y-4"><legend class="display text-h3"><?= htmlspecialchars($s('site')) ?></legend><?= $field('site_url', $s('siteUrl'), 'url', ' required') ?></fieldset>
      <fieldset class="space-y-4"><legend class="display text-h3"><?= htmlspecialchars($s('admin')) ?></legend>
        <?= $field('admin_name', $s('adminName'), 'text', ' required') ?><?= $field('admin_email', $s('adminEmail'), 'email', ' required') ?><?= $field('admin_pass', $s('adminPass'), 'password', ' required minlength="10" autocomplete="new-password"') ?>
        <div><label for="admin_lang" class="<?= $label ?>"><?= htmlspecialchars($s('adminLang')) ?></label><select id="admin_lang" name="admin_lang" class="<?= $input ?> pr-9"><option value="fr"<?= $values['admin_lang'] === 'fr' ? ' selected' : '' ?>><?= htmlspecialchars($s('langFr')) ?></option><option value="en"<?= $values['admin_lang'] === 'en' ? ' selected' : '' ?>><?= htmlspecialchars($s('langEn')) ?></option></select></div>
      </fieldset>
      <button type="submit" class="inline-flex min-h-14 items-center bg-ink px-7 text-nav font-semibold text-white uppercase"><?= htmlspecialchars($s('submit')) ?></button>
    </form>
    <?php endif ?>
  <?php endif ?>
  </div>
</main>
</body>
</html>
