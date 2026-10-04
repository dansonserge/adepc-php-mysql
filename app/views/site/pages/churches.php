<?php
use Adepc\Schedule;
use Adepc\Site;

/** @var array $churches */
$token = setting('maps.mapbox_token');
?>
<?= partial('components/page-hero', ['eyebrow' => t('churches.hero.eyebrow'), 'title' => t('churches.hero.title'), 'lead' => t('churches.hero.lead'), 'photo' => Site::slot('churches.hero')]) ?>
<section class="bg-paper text-ink">
  <?php foreach ($churches as $i => $church): ?>
  <article aria-labelledby="church-<?= e($church['slug']) ?>" class="rule grid-12 items-center gap-y-8 border-b py-12 md:py-16">
    <?= photo(Site::media($church['hero_media_id']), ['sizes' => '(min-width: 1024px) 42vw, (min-width: 768px) 50vw, 100vw', 'class' => 'col-span-4 aspect-[4/3] md:col-span-4 lg:col-span-5']) ?>
    <div class="col-span-4 px-(--edge) md:col-span-4 md:pl-0 lg:col-span-6 lg:col-start-7">
      <p class="caption text-stone tabular-nums"><?= e(pad2($i + 1)) ?></p>
      <h2 id="church-<?= e($church['slug']) ?>" class="display mt-3 text-display-m"><?= e(L($church, 'city')) ?></h2>
      <p class="mt-4 max-w-[46ch] text-lead text-stone"><?= e(L($church, 'summary')) ?></p>
      <div class="mt-8"><?= partial('components/church-facts', ['church' => $church]) ?></div>
      <div class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-4">
        <?= button_link(url('church', ['slug' => $church['slug']]), $church['name'], 'ink') ?>
        <?= arrow_link(Schedule::directionsUrl($church), t('common.directions')) ?>
      </div>
    </div>
  </article>
  <?php endforeach ?>
</section>
<?php if ($token !== ''): ?>
<section aria-labelledby="map-title" class="bg-cream pt-(--section-y) text-ink">
  <h2 id="map-title" class="display edge text-display-m"><?= e(t('churches.map.title')) ?></h2>
  <div class="mt-10 h-[70svh] min-h-[26rem]"><?= partial('components/church-map', ['label' => t('churches.map.label'), 'pins' => array_map(static fn ($c) => [
      'label' => L($c, 'city'), 'name' => $c['name'], 'href' => url('church', ['slug' => $c['slug']]), 'lat' => (float) $c['lat'], 'lng' => (float) $c['lng'],
  ], $churches)]) ?></div>
</section>
<?php endif ?>
<section class="section-y bg-ink text-white">
  <div class="edge grid-12 items-end gap-y-8">
    <h2 class="display col-span-4 text-display-m md:col-span-5 lg:col-span-7"><?= e(t('churches.directory.title')) ?></h2>
    <div class="col-span-4 md:col-span-3 lg:col-span-5">
      <p class="text-lead text-white/85"><?= e(t('churches.directory.lead')) ?></p>
      <?= button_link(url('contact'), t('churches.directory.cta'), 'gold', 'mt-8') ?>
    </div>
  </div>
</section>
