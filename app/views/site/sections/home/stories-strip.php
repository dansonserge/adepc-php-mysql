<?php
use Adepc\Site;

// An asymmetric composition: big, small, small, wide.
$layout = [
    ['home.stories.1', 'col-span-4 aspect-[4/3] md:col-span-5 md:aspect-auto md:h-[46vw] lg:col-span-7 lg:h-[40vw]', '(min-width:1024px) 58vw, (min-width:768px) 62vw, 100vw'],
    ['home.stories.2', 'col-span-2 aspect-[3/4] md:col-span-3 md:aspect-auto md:h-[46vw] lg:col-span-5 lg:h-[40vw]', '(min-width:1024px) 42vw, (min-width:768px) 38vw, 50vw'],
    ['home.stories.3', 'col-span-2 aspect-[3/4] md:col-span-3 md:aspect-auto md:h-[36vw] lg:col-span-4 lg:h-[30vw]', '(min-width:1024px) 33vw, (min-width:768px) 38vw, 50vw'],
    ['home.stories.4', 'col-span-4 aspect-[16/10] md:col-span-5 md:aspect-auto md:h-[36vw] lg:col-span-8 lg:h-[30vw]', '(min-width:1024px) 66vw, 62vw'],
];
?>
<section aria-labelledby="stories-title" class="section-y bg-ink text-white">
  <div class="edge grid-12 items-end gap-y-6">
    <div class="col-span-4 md:col-span-5 lg:col-span-8">
      <?= eyebrow(e(t('home.stories.eyebrow'))) ?>
      <h2 id="stories-title" class="display mt-6 text-display-m" data-reveal="up"><?= e(t('home.stories.title')) ?></h2>
    </div>
    <p class="col-span-4 text-lead text-white/80 md:col-span-3 lg:col-span-4" data-reveal="up"><?= e(t('home.stories.lead')) ?></p>
  </div>
  <div class="mt-12 grid grid-cols-4 gap-1 md:mt-16 md:grid-cols-8 lg:grid-cols-12">
    <?php foreach ($layout as [$slot, $cls, $sizes]): $m = Site::slot($slot); if (!$m) continue; $church = Site::churchById($m['church_id']); ?>
    <figure class="relative <?= e($cls) ?>">
      <?= photo($m, ['sizes' => $sizes, 'class' => 'absolute inset-0']) ?>
      <?php if ($church): ?><figcaption class="caption absolute bottom-0 left-0 bg-ink/70 px-3 py-2 backdrop-blur-sm"><?= e(t('format.churchCity', ['city' => L($church, 'city')])) ?></figcaption><?php endif ?>
    </figure>
    <?php endforeach ?>
  </div>
  <div class="edge mt-12"><?= button_link(url('stories'), t('home.stories.cta'), 'light') ?></div>
</section>
