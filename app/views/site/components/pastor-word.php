<?php
use Adepc\Site;

/** @var string $tone cream|paper */
$tone = $tone ?? 'paper';
?>
<section id="pastor" aria-labelledby="pastor-title" class="section-y text-ink <?= $tone === 'cream' ? 'bg-cream' : 'bg-paper' ?>">
  <div class="grid-12 items-center gap-y-12">
    <div class="col-span-4 md:col-span-4 lg:col-span-5">
      <?= photo(Site::slot('pastor.photo'), ['sizes' => '(min-width: 1024px) 42vw, (min-width: 768px) 50vw, 100vw', 'class' => 'aspect-[4/5] w-full md:mr-0', 'blend' => $tone]) ?>
    </div>
    <div class="edge col-span-4 md:col-span-4 md:pl-0 lg:col-span-6 lg:col-start-7">
      <?= eyebrow(e(t('home.pastor.eyebrow'))) ?>
      <h2 id="pastor-title" class="display mt-6 text-display-m" data-reveal="up"><?= e(t('home.pastor.title')) ?></h2>
      <div class="mt-8 max-w-[44ch] space-y-5">
        <p class="font-serif text-h3 italic leading-snug" data-reveal="up"><?= e(t('home.pastor.p1')) ?></p>
        <p class="text-lead text-stone" data-reveal="up"><?= e(t('home.pastor.p2')) ?></p>
      </div>
      <div class="mt-10 flex flex-wrap items-center gap-x-10 gap-y-6">
        <div class="border-l-2 border-gold pl-4">
          <p class="font-semibold"><?= e(settingL('pastor.name')) ?></p>
          <p class="mt-1 text-small text-stone"><?= e(settingL('pastor.role')) ?></p>
        </div>
        <?= button_link(url('contact'), t('home.pastor.cta'), 'ink') ?>
      </div>
    </div>
  </div>
</section>
