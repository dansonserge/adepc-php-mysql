<?php
use Adepc\Site;

// Editorial spreads: a photograph bled to one edge, index and text on the other, alternating.
?>
<section aria-labelledby="pillars-title" class="bg-cream pt-(--section-y) pb-[calc(var(--section-y)/2)] text-ink">
  <h2 id="pillars-title" class="caption edge flex items-center gap-3"><span aria-hidden="true" class="h-px w-8 bg-current opacity-60"></span><?= e(t('home.pillars.eyebrow')) ?></h2>
  <?php foreach (Site::items('home.pillars') as $i => $pillar): $flip = $i % 2 === 1; $id = 'pillar-' . (slugify((string) $pillar['title_en']) ?: $pillar['id']); ?>
  <article aria-labelledby="<?= e($id) ?>" class="grid-12 items-center gap-y-8 py-10 md:py-14">
    <?= photo(Site::media($pillar['media_id']), [
        'sizes' => '(min-width: 1024px) 58vw, (min-width: 768px) 62vw, 100vw',
        'class' => 'col-span-4 aspect-[4/5] md:row-start-1 md:col-span-5 md:aspect-[3/4] lg:col-span-7 lg:aspect-[5/4] ' . ($flip ? 'md:col-start-4 lg:col-start-6' : 'md:col-start-1'),
    ]) ?>
    <div class="col-span-4 px-(--edge) md:row-start-1 md:col-span-3 lg:col-span-4 <?= $flip ? 'md:col-start-1 md:pr-0' : 'md:col-start-6 md:pl-0 lg:col-start-9' ?>">
      <p class="display text-display-l leading-[0.8] text-blue tabular-nums" aria-hidden="true"><?= e(pad2($i + 1)) ?></p>
      <h3 id="<?= e($id) ?>" class="display mt-6 text-display-m" data-reveal="up"><?= e(L($pillar, 'title')) ?></h3>
      <p class="mt-6 max-w-[34ch] text-lead text-stone" data-reveal="up"><?= e(L($pillar, 'body')) ?></p>
    </div>
  </article>
  <?php endforeach ?>
</section>
