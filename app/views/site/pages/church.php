<?php
use Adepc\Schedule;
use Adepc\Site;

/** @var array $church @var array $others */
$city = L($church, 'city');
$token = setting('maps.mapbox_token');
$moments = array_values(array_filter(Site::d()['media'], static fn ($m) => (int) $m['church_id'] === (int) $church['id'] && (int) $m['in_church_moments'] === 1 && $m['kind'] === 'image'));
$visit = url('contact', [], ['church' => $church['slug'], 'purpose' => 'visit']);
$back = '<a class="group inline-flex items-center gap-3 text-nav font-semibold uppercase hover:text-gold" href="' . e(url('churches')) . '">' . arrow('rotate-180') . e(t('churches.page.back')) . '</a>';
?>
<?= partial('components/page-hero', ['eyebrow' => t('churches.page.eyebrow'), 'title' => $city, 'lead' => L($church, 'intro'), 'photo' => Site::media($church['hero_media_id']), 'children' => $back]) ?>
<section aria-labelledby="services-title" class="section-y bg-paper text-ink">
  <div class="edge grid-12 gap-y-12">
    <div class="col-span-4 md:col-span-8 lg:col-span-6">
      <h2 id="services-title" class="display text-display-m"><?= e(t('churches.page.services')) ?></h2>
      <div class="mt-10"><?= partial('components/church-facts', ['church' => $church, 'showPastor' => true]) ?></div>
      <div class="mt-10 flex flex-wrap gap-3">
        <?= external_button(Schedule::directionsUrl($church), t('common.directions'), 'ink') ?>
        <?= button_link($visit, t('churches.page.visitCta'), 'outline-dark') ?>
      </div>
    </div>
    <div class="col-span-4 h-[26rem] md:col-span-8 lg:col-span-5 lg:col-start-8 lg:h-auto lg:min-h-[32rem]">
      <?php if ($token !== ''): ?>
      <?= partial('components/church-map', ['label' => $church['name'], 'pins' => [[
          'label' => $city, 'name' => $church['name'], 'href' => Schedule::directionsUrl($church), 'lat' => (float) $church['lat'], 'lng' => (float) $church['lng'],
      ]]]) ?>
      <?php else: ?>
      <?= photo($moments[1] ?? Site::media($church['hero_media_id']), ['sizes' => '(min-width: 1024px) 40vw, 100vw', 'class' => 'h-full w-full']) ?>
      <?php endif ?>
    </div>
  </div>
</section>
<section aria-labelledby="about-title" class="section-y bg-cream text-ink">
  <div class="edge grid-12 gap-y-8">
    <div class="col-span-4 md:col-span-8 lg:col-span-5">
      <?= eyebrow(e(t('format.churchCity', ['city' => $city]))) ?>
      <h2 id="about-title" class="display mt-6 text-display-m" data-reveal="up"><?= e(L($church, 'about_title')) ?></h2>
    </div>
    <div class="col-span-4 space-y-5 text-lead md:col-span-6 lg:col-span-5 lg:col-start-8 lg:pt-14">
      <p data-reveal="up"><?= e(t('churches.page.aboutP1')) ?></p>
      <p class="text-stone" data-reveal="up"><?= e(t('churches.page.aboutP2', ['city' => $city])) ?></p>
    </div>
  </div>
  <?php if ($moments): ?>
  <div class="mt-16 md:mt-24">
    <h3 class="caption edge mb-6"><?= e(t('churches.page.moments')) ?></h3>
    <ul class="grid grid-cols-2 gap-1 md:grid-cols-3">
      <?php foreach (array_slice($moments, 0, 6) as $i => $m): ?>
      <li class="<?= $i === 0 ? 'col-span-2 md:row-span-2' : '' ?>"><?= photo($m, [
          'sizes' => $i === 0 ? '(min-width: 768px) 66vw, 100vw' : '(min-width: 768px) 33vw, 50vw',
          'class' => $i === 0 ? 'aspect-[4/3] w-full md:aspect-auto md:h-full' : 'aspect-[4/3] w-full',
      ]) ?></li>
      <?php endforeach ?>
    </ul>
  </div>
  <?php endif ?>
</section>
<section class="on-gold section-y bg-gold text-ink">
  <div class="edge grid-12 items-end gap-y-8">
    <h2 class="display col-span-4 text-display-m md:col-span-5 lg:col-span-7"><?= e(t('churches.page.visitTitle')) ?></h2>
    <div class="col-span-4 md:col-span-3 lg:col-span-5">
      <p class="text-lead"><?= e(t('churches.page.visitLead')) ?></p>
      <?= button_link($visit, t('churches.page.visitCta'), 'ink', 'mt-8') ?>
    </div>
  </div>
</section>
<section aria-labelledby="others-title" class="section-y bg-ink text-white">
  <div class="edge">
    <h2 id="others-title" class="caption"><?= e(t('churches.page.others')) ?></h2>
    <ul class="mt-8 border-t border-white/20">
      <?php foreach ($others as $c): $sunday = Schedule::serviceOn($c, 'sunday'); ?>
      <li class="border-b border-white/20"><a class="group grid-12 items-baseline gap-y-2 py-6" href="<?= e(url('church', ['slug' => $c['slug']])) ?>"><span class="display col-span-4 text-display-m group-hover:text-gold md:col-span-5 lg:col-span-6"><?= e(L($c, 'city')) ?></span><span class="col-span-3 text-lead text-fog md:col-span-2 lg:col-span-4"><?= e(t('format.dayTime', ['day' => t('common.sunday'), 'time' => $sunday ? Schedule::formatTime($sunday['time']) : ''])) ?></span><span class="col-span-1 justify-self-end"><?= arrow() ?></span></a></li>
      <?php endforeach ?>
    </ul>
    <?= arrow_link(url('churches'), t('churches.page.back'), 'mt-10') ?>
  </div>
</section>
