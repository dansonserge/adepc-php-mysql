<?php
use Adepc\Site;

/** @var string $active @var array $alternates */
$isActive = static fn (string $page): bool => $page === $active || ($page === 'churches' && $active === 'church');
$cta = Site::menu('cta')[0] ?? null;
$wordmark = partial('partials/wordmark');
?>
<header class="fixed inset-x-0 top-0 z-50 text-white transition-colors duration-500 bg-transparent" data-js-header>
  <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-40 bg-linear-to-b from-ink/60 to-transparent transition-opacity duration-500 opacity-100" data-js-header-shade></div>
  <div class="edge flex h-[var(--header-h)] items-center justify-between gap-6">
    <a aria-label="<?= e(t('nav.homeLabel')) ?>" class="shrink-0" href="<?= e(url('home')) ?>"><?= $wordmark ?></a>
    <nav aria-label="<?= e(t('nav.primary')) ?>" class="hidden lg:block">
      <ul class="flex items-center gap-1 xl:gap-3">
        <?php foreach (Site::menu('header') as $item): ?>
        <li><a<?= attrs(['aria-current' => $isActive($item['page_key']) ? 'page' : null]) ?> class="relative block px-3 py-3 text-nav font-semibold uppercase after:absolute after:inset-x-3 after:bottom-1.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-gold after:transition-transform after:duration-300 hover:after:scale-x-100 aria-[current=page]:after:scale-x-100" href="<?= e(url($item['page_key'])) ?>"><?= e(L($item, 'label')) ?></a></li>
        <?php endforeach ?>
      </ul>
    </nav>
    <div class="flex items-center gap-4 sm:gap-6">
      <?= partial('partials/language-switcher', ['alternates' => $alternates, 'class' => '']) ?>
      <?php if ($cta): ?>
      <div class="hidden sm:block"><a class="<?= e(button_class('gold', 'min-h-11 px-5')) ?>" href="<?= e(url($cta['page_key'])) ?>"><span><?= e(L($cta, 'label')) ?></span></a></div>
      <?php endif ?>
      <button type="button" aria-haspopup="dialog" aria-label="<?= e(t('nav.openMenu')) ?>" class="-mr-2 flex h-11 w-11 items-center justify-center lg:hidden" data-js-menu-open><?= icon('menu', 'h-7 w-7') ?></button>
    </div>
  </div>
  <dialog aria-label="<?= e(t('nav.menu')) ?>" class="menu-dialog m-0 h-dvh max-h-none w-full max-w-none bg-ink p-0 text-white" data-js-menu>
    <div class="edge flex h-full flex-col">
      <div class="flex h-[var(--header-h)] shrink-0 items-center justify-between">
        <a aria-label="<?= e(t('nav.homeLabel')) ?>" href="<?= e(url('home')) ?>"><?= $wordmark ?></a>
        <button type="button" aria-label="<?= e(t('nav.closeMenu')) ?>" class="-mr-2 flex h-11 w-11 items-center justify-center" data-js-menu-close><?= icon('close', 'h-7 w-7') ?></button>
      </div>
      <nav aria-label="<?= e(t('nav.primary')) ?>" class="flex-1 overflow-y-auto py-6">
        <ul class="border-t border-white/15">
          <?php foreach (Site::menu('overlay') as $i => $item): ?>
          <li class="border-b border-white/15"><a<?= attrs(['aria-current' => $isActive($item['page_key']) ? 'page' : null]) ?> class="group flex items-baseline gap-4 py-3 aria-[current=page]:text-gold" href="<?= e(url($item['page_key'])) ?>"><span class="caption w-6 shrink-0 text-fog tabular-nums"><?= e(pad2($i + 1)) ?></span><span class="display text-[clamp(2.75rem,12vw,5rem)] leading-[0.95]"><?= e(L($item, 'label')) ?></span></a></li>
          <?php endforeach ?>
        </ul>
      </nav>
      <div class="flex shrink-0 items-center justify-between gap-4 border-t border-white/15 py-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
        <?= partial('partials/language-switcher', ['alternates' => $alternates, 'class' => '']) ?>
        <?php if ($cta): ?>
        <a class="<?= e(button_class('gold')) ?>" href="<?= e(url($cta['page_key'])) ?>"><span><?= e(L($cta, 'label')) ?></span><?= arrow() ?></a>
        <?php endif ?>
      </div>
    </div>
  </dialog>
</header>
