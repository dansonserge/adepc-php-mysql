<?php
use Adepc\Front\Videos;
use Adepc\Site;

/** @var ?array $latest @var array $videos */
$count = static fn ($c) => count(array_filter($videos, static fn ($v) => (int) $v['category']['id'] === (int) $c['id']));
?>
<section aria-labelledby="watch-title" class="section-y bg-cream text-ink">
  <div class="edge grid-12 gap-y-12">
    <div class="col-span-4 md:col-span-8 lg:col-span-8">
      <?= eyebrow(e(t('home.watch.eyebrow'))) ?>
      <h2 id="watch-title" class="display mt-6 text-display-m" data-reveal="up"><?= e(t('home.watch.title')) ?></h2>
      <?php if ($latest): ?>
      <?= partial('components/film-player', [
          'source' => $latest['source'],
          'title' => L($latest, 'title'),
          'class' => 'mt-10 aspect-video',
          'poster' => $latest['poster_url']
              ? '<img src="' . e($latest['poster_url']) . '" alt="" class="absolute inset-0 h-full w-full object-cover">'
              : photo($latest['poster'], ['sizes' => '(min-width: 1024px) 66vw, 100vw', 'class' => 'absolute inset-0', 'decorative' => true, 'reveal' => false]),
          'children' => '<p class="pointer-events-none absolute inset-x-0 bottom-0 bg-linear-to-t from-ink to-transparent p-6 pt-16 text-h3 font-semibold text-white">' . e(L($latest, 'title')) . '</p>',
      ]) ?>
      <?php else: ?>
      <div class="relative mt-10 flex min-h-[18rem] flex-col items-start justify-end gap-4 bg-ink p-6 text-white sm:aspect-video md:p-10">
        <?= icon('play', 'absolute top-1/2 left-1/2 h-16 w-16 -translate-x-1/2 -translate-y-1/2 text-white/20 md:h-24 md:w-24') ?>
        <p class="max-w-[44ch] text-small text-white/80 md:text-body"><?= e(t('home.watch.lead')) ?></p>
      </div>
      <?php endif ?>
    </div>
    <nav aria-label="<?= e(t('watch.hero.title')) ?>" class="col-span-4 md:col-span-8 lg:col-span-4 lg:pt-28">
      <ul class="rule border-t">
        <?php foreach (Site::d()['categories'] as $c): $n = $count($c); ?>
        <li class="rule border-b"><a class="group flex items-baseline justify-between gap-4 py-4 hover:text-blue" href="<?= e(url('watch', [], [], $c['slug'])) ?>"><span class="display text-h2 leading-none"><?= e(L($c, 'name')) ?></span><?php if ($n > 0): ?><span class="caption text-stone tabular-nums"><?= e(pad2($n)) ?></span><?php endif ?></a></li>
        <?php endforeach ?>
      </ul>
      <?= arrow_link(url('watch'), t('home.watch.cta'), 'mt-8') ?>
    </nav>
  </div>
</section>
