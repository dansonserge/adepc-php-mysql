<?php
use Adepc\Site;

/** @var ?array $latest @var array $filled @var array $empty */
$poster = static function (array $v, string $sizes): string {
    if ($v['poster_url']) {
        return '<img src="' . e($v['poster_url']) . '" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">';
    }
    return photo($v['poster'], ['sizes' => $sizes, 'class' => 'absolute inset-0', 'decorative' => true, 'reveal' => false]);
};
?>
<?= partial('components/page-hero', ['eyebrow' => t('watch.hero.eyebrow'), 'title' => t('watch.hero.title'), 'lead' => t('watch.hero.lead'), 'tone' => 'night', 'titleClass' => 'text-display-xl']) ?>
<section aria-labelledby="latest-title" class="section-y bg-ink text-white">
  <div class="edge">
    <h2 id="latest-title" class="display text-display-m"><?= e(t('watch.latest')) ?></h2>
    <?php if ($latest): ?>
    <?= partial('components/film-player', ['source' => $latest['source'], 'title' => L($latest, 'title'), 'class' => 'mt-10 aspect-video max-h-[85svh] w-full', 'poster' => $poster($latest, '100vw')]) ?>
    <?php else: ?>
    <div class="relative mt-10 flex min-h-[20rem] w-full flex-col items-start justify-end gap-4 border border-white/15 p-6 sm:aspect-video sm:max-h-[70svh] md:p-10">
      <?= icon('play', 'absolute top-1/2 left-1/2 h-20 w-20 -translate-x-1/2 -translate-y-1/2 text-white/15 md:h-32 md:w-32') ?>
      <p class="max-w-[52ch] text-body text-white/80"><?= e(t('watch.latestPending')) ?></p>
    </div>
    <?php endif ?>
  </div>
</section>
<nav aria-label="<?= e(t('watch.hero.title')) ?>" class="sticky top-(--header-h) z-30 border-y border-ink/10 bg-paper/95 backdrop-blur">
  <ul class="edge flex gap-6 overflow-x-auto py-4 [scrollbar-width:none] md:gap-10">
    <?php foreach (Site::d()['categories'] as $c): ?>
    <li class="shrink-0"><a href="#<?= e($c['slug']) ?>" class="text-nav font-semibold uppercase hover:text-blue"><?= e(L($c, 'name')) ?></a></li>
    <?php endforeach ?>
  </ul>
</nav>
<div class="bg-paper text-ink">
  <?php foreach ($filled as ['category' => $c, 'list' => $list]): ?>
  <section id="<?= e($c['slug']) ?>" aria-labelledby="<?= e($c['slug']) ?>-title" class="rule scroll-mt-40 border-b py-16 md:py-24">
    <div class="edge">
      <h2 id="<?= e($c['slug']) ?>-title" class="display text-display-m"><?= e(L($c, 'name')) ?></h2>
      <ul class="mt-10 grid gap-x-(--gutter) gap-y-12 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($list as $v):
            $bits = array_values(array_filter([(string) $v['date_label'], $v['duration_seconds'] ? t('watch.seconds', ['count' => $v['duration_seconds']]) : ''], 'strlen')); ?>
        <li>
          <?= partial('components/film-player', ['source' => $v['source'], 'title' => L($v, 'title'), 'class' => 'aspect-video', 'buttonClass' => 'bottom-0 left-0 h-16 w-16', 'poster' => $poster($v, '(min-width: 1280px) 33vw, (min-width: 768px) 50vw, 100vw')]) ?>
          <h3 class="display mt-4 text-h2"><?= e(L($v, 'title')) ?></h3>
          <p class="caption mt-2 text-stone"><?= e(implode(t('format.separator'), $bits)) ?></p>
        </li>
        <?php endforeach ?>
      </ul>
    </div>
  </section>
  <?php endforeach ?>
  <?php if ($empty): ?>
  <section class="py-16 md:py-24">
    <ul class="edge rule border-t">
      <?php foreach ($empty as $c): ?>
      <li id="<?= e($c['slug']) ?>" class="rule grid-12 scroll-mt-40 items-baseline gap-y-2 border-b py-6">
        <h2 class="display col-span-4 text-h1 md:col-span-3 lg:col-span-4"><?= e(L($c, 'name')) ?></h2>
        <p class="col-span-4 max-w-[56ch] text-body text-stone md:col-span-5 lg:col-span-7 lg:col-start-6"><?= e(L($c, 'empty')) ?></p>
      </li>
      <?php endforeach ?>
    </ul>
  </section>
  <?php endif ?>
</div>
