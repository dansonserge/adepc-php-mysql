<?php
use Adepc\Admin\A;
use Adepc\Admin\Auth;
use Adepc\Site;

/** @var string $body @var string $title @var string $section @var bool $bare */
$user = Auth::user();
$nav = [
    ['dashboard', '/admin', 'admin.nav.dashboard'],
    ['-', null, 'admin.nav.groupContent'],
    ['pages', '/admin/pages', 'admin.nav.pages'],
    ['churches', '/admin/churches', 'admin.nav.churches'],
    ['events', '/admin/events', 'admin.nav.events'],
    ['videos', '/admin/videos', 'admin.nav.videos'],
    ['categories', '/admin/categories', 'admin.nav.categories'],
    ['stories', '/admin/stories', 'admin.nav.stories'],
    ['media', '/admin/media', 'admin.nav.media'],
    ['menus', '/admin/menus', 'admin.nav.menus'],
    ['texts', '/admin/texts', 'admin.nav.texts'],
    ['purposes', '/admin/purposes', 'admin.nav.purposes'],
    ['-', null, 'admin.nav.groupSite'],
    ['settings', '/admin/settings', 'admin.nav.settings'],
    ['languages', '/admin/languages', 'admin.nav.languages'],
    ['icons', '/admin/icons', 'admin.nav.icons'],
    ['redirects', '/admin/redirects', 'admin.nav.redirects'],
];
if (Auth::isAdmin()) {
    $nav[] = ['users', '/admin/users', 'admin.nav.users'];
}
$here = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH);
$langSwitch = '<form method="post" action="/admin/ui-language" class="flex items-center text-nav font-semibold">' . csrf_field()
    . '<input type="hidden" name="back" value="' . e($_SERVER['REQUEST_URI'] ?? '/admin') . '">';
foreach (A::LOCALES as $i => $l) {
    $langSwitch .= ($i ? '<span aria-hidden="true" class="mx-2 h-3 w-px bg-current opacity-40"></span>' : '')
        . '<button type="submit" name="lang" value="' . $l . '" class="py-2 ' . ($l === A::$locale ? '' : 'opacity-60 hover:opacity-100') . '"' . ($l === A::$locale ? ' aria-current="true"' : '') . '>' . e(strtoupper($l)) . '</button>';
}
$langSwitch .= '</form>';
$flash = A::takeFlash();
?><!DOCTYPE html>
<html lang="<?= e(A::$locale) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · <?= e(at('admin.brand')) ?></title>
<link rel="stylesheet" href="<?= e(asset('/assets/css/admin.css')) ?>">
<?php if ($icon = Site::slot('brand.icon')): ?><link rel="icon" href="<?= e(Site::mediaUrl($icon)) ?>"><?php endif ?>
</head>
<body class="bg-paper text-ink">
<?php if ($bare): ?>
<main class="flex min-h-svh flex-col items-center justify-center bg-ink px-4 py-12 text-white">
  <div class="w-full max-w-md">
    <div class="mb-8 flex items-center justify-between">
      <p class="display text-[1.75rem] leading-none"><?= e(Site::setting('site.short_name')) ?> <span class="text-gold"><?= e(at('admin.brandShort')) ?></span></p>
      <?= $langSwitch ?>
    </div>
    <?php foreach ($flash as [$type, $key, $vars]): ?>
    <p role="status" class="mb-4 border-l-4 <?= $type === 'ok' ? 'border-gold' : 'border-ember' ?> bg-white/10 px-4 py-3 text-small"><?= e(at($key, $vars)) ?></p>
    <?php endforeach ?>
    <div class="bg-paper p-6 text-ink md:p-8"><?= $body ?></div>
  </div>
</main>
<?php else: ?>
<div class="min-h-svh lg:grid lg:grid-cols-[16rem_1fr]">
  <aside class="bg-ink text-white lg:sticky lg:top-0 lg:h-svh lg:overflow-y-auto">
    <div class="flex items-center justify-between gap-4 px-5 py-5">
      <a href="/admin" class="display text-[1.5rem] leading-none"><?= e(Site::setting('site.short_name')) ?> <span class="text-gold"><?= e(at('admin.brandShort')) ?></span></a>
      <button type="button" class="text-nav font-semibold uppercase lg:hidden" data-admin-nav-toggle aria-expanded="false"><?= e(at('admin.nav.menu')) ?></button>
    </div>
    <nav aria-label="<?= e(at('admin.nav.label')) ?>" class="hidden pb-8 lg:block" data-admin-nav>
      <ul>
        <?php foreach ($nav as [$key, $href, $label]): ?>
        <?php if ($key === '-'): ?>
        <li class="caption mt-6 px-5 pb-2 text-fog"><?= e(at($label)) ?></li>
        <?php else: $on = $section === $key; ?>
        <li><a href="<?= e($href) ?>"<?= $on ? ' aria-current="page"' : '' ?> class="block border-l-4 px-5 py-2 text-small <?= $on ? 'border-gold bg-white/10 font-semibold' : 'border-transparent text-white/80 hover:bg-white/5 hover:text-white' ?>"><?= e(at($label)) ?></a></li>
        <?php endif ?>
        <?php endforeach ?>
      </ul>
    </nav>
  </aside>
  <div class="min-w-0">
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-ink/10 bg-white px-5 py-3 md:px-8">
      <a href="<?= e(Site::url('home', A::$locale)) ?>" target="_blank" rel="noopener" class="text-small font-semibold text-blue underline-offset-4 hover:underline"><?= e(at('admin.viewSite')) ?></a>
      <div class="flex items-center gap-5">
        <?= $langSwitch ?>
        <a href="/admin/account" class="text-small"<?= $section === 'account' ? ' aria-current="page"' : '' ?>><?= e($user['name'] ?? '') ?></a>
        <form method="post" action="/admin/logout"><?= csrf_field() ?><button type="submit" class="text-small font-semibold underline underline-offset-4"><?= e(at('admin.logout')) ?></button></form>
      </div>
    </header>
    <main class="px-5 py-8 md:px-8 md:py-10" id="main">
      <h1 class="display text-h1"><?= e($title) ?></h1>
      <?php foreach ($flash as [$type, $key, $vars]): ?>
      <p role="<?= $type === 'ok' ? 'status' : 'alert' ?>" class="mt-6 border-l-4 <?= $type === 'ok' ? 'border-gold bg-gold/10' : 'border-ember bg-ember/10' ?> px-4 py-3 text-small font-semibold"><?= e(at($key, $vars)) ?></p>
      <?php endforeach ?>
      <div class="mt-8"><?= $body ?></div>
    </main>
  </div>
</div>
<?php endif ?>
<script type="module" src="<?= e(asset('/assets/js/admin.js')) ?>"></script>
</body>
</html>
