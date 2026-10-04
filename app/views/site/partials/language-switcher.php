<?php
use Adepc\Site;

/** @var array $alternates @var string $class */
// Real links to the same page in the other language, not a JS toggle.
$i = 0;
?><nav aria-label="<?= e(t('nav.language')) ?>" class="<?= e($class ?? '') ?>"><ul class="flex items-center text-nav font-semibold"><?php foreach (Site::locales() as $code => $l): ?><li class="flex items-center"><?php if ($i++ > 0): ?><span aria-hidden="true" class="mx-2 h-3 w-px bg-current opacity-40"></span><?php endif ?><?php if ($code === Site::$locale): ?><span aria-current="true" class="py-2"><abbr title="<?= e($l['name']) ?>" class="no-underline"><?= e($l['short_label']) ?></abbr></span><?php else: ?><a hreflang="<?= e($code) ?>" lang="<?= e($code) ?>" aria-label="<?= e($l['name']) ?>" class="py-2 opacity-60 transition-opacity hover:opacity-100" href="<?= e($alternates[$code] ?? Site::url('home', $code)) ?>"><?= e($l['short_label']) ?></a><?php endif ?></li><?php endforeach ?></ul></nav>
