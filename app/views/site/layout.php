<?php
use Adepc\Front\Seo;
use Adepc\Site;

/** @var string $body @var array $meta */
$active = $meta['active'] ?? $meta['page'];
$alternates = $meta['alternates'] ?? [];
// Rendered before <head> so their eager/preloaded images can be preloaded there.
$header = partial('partials/header', ['active' => $active, 'alternates' => $alternates]);
$footer = partial('partials/footer', ['alternates' => $alternates]);
$tabbar = partial('partials/tabbar', ['active' => $active]);
?><!DOCTYPE html>
<html lang="<?= e(Site::$locale) ?>" data-scroll-behavior="smooth">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>document.documentElement.classList.add('js')</script>
<link rel="preload" href="/assets/fonts/instrument-sans-latin.woff2" as="font" crossorigin="" type="font/woff2">
<?= Seo::imagePreloads() ?>
<link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
<?= Seo::head($meta) ?>
</head>
<body>
<a href="#main" class="sr-only-focusable fixed top-3 left-3 z-[100] bg-gold px-5 py-3 text-nav font-semibold text-ink uppercase"><?= e(t('nav.skip')) ?></a>
<?= $header ?>
<main id="main" tabindex="-1" class="outline-none"><?= $body ?></main>
<?= $footer ?>
<?= $tabbar ?>
<?= partial('partials/consent') ?>
<script type="module" src="<?= e(asset('/assets/js/site/main.js')) ?>"></script>
</body>
</html>
