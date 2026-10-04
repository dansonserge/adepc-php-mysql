<?php
use Adepc\Site;

/** @var string $active */
// Phones only: service times (Churches) and Give one thumb-tap away.
?>
<nav aria-label="<?= e(t('nav.mobile')) ?>" class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-ink pb-[env(safe-area-inset-bottom)] text-white md:hidden">
  <ul class="grid h-[var(--tabbar-h)] grid-cols-5">
    <?php foreach (Site::menu('tabbar') as $item):
        $page = $item['page_key'];
        $on = $page === 'home' ? $active === 'home' : ($page === $active || ($page === 'churches' && $active === 'church'));
        $tone = $item['highlight'] ? 'bg-gold text-ink' : ($on ? 'text-gold' : 'text-white/80'); ?>
    <li><a<?= attrs(['aria-current' => $on ? 'page' : null]) ?> class="flex h-full flex-col items-center justify-center gap-1 text-[0.6875rem] font-semibold tracking-[0.04em] uppercase <?= e($tone) ?>" href="<?= e(url($page)) ?>"><?= $item['icon'] ? icon($item['icon'], 'h-5 w-5') : '' ?><span><?= e(L($item, 'label')) ?></span></a></li>
    <?php endforeach ?>
  </ul>
</nav>
