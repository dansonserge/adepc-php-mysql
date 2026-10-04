<?php
use Adepc\Site;

// URLs outside every language: one document that speaks all of them.
$default = Site::defaultLocale();
$others = array_keys(Site::otherLocales());
?><!DOCTYPE html>
<html lang="<?= e($default) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(str_replace('%s', t('notFound.code'), t('meta.titleTemplate'))) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
</head>
<body class="bg-ink text-white">
<main class="edge flex min-h-svh flex-col justify-end pb-16">
  <p class="display text-display-xl leading-[0.8] text-gold"><?= e(t('notFound.code')) ?></p>
  <h1 class="display mt-6 text-display-m"><?= e(t('notFound.title')) ?><?php foreach ($others as $l): ?><span lang="<?= e($l) ?>" class="block text-white/60"><?= e(Site::t('notFound.title', [], $l)) ?></span><?php endforeach ?></h1>
  <p class="mt-10 flex flex-wrap gap-3">
    <a href="<?= e(Site::url('home', $default)) ?>" class="inline-flex min-h-14 items-center bg-gold px-7 text-nav font-semibold text-ink uppercase"><?= e(t('notFound.homeShort')) ?></a>
    <?php foreach ($others as $l): ?><a href="<?= e(Site::url('home', $l)) ?>" lang="<?= e($l) ?>" class="inline-flex min-h-14 items-center border border-white/70 px-7 text-nav font-semibold uppercase"><?= e(Site::t('notFound.homeShort', [], $l)) ?></a><?php endforeach ?>
  </p>
</main>
</body>
</html>
