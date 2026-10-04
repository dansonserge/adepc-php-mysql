<?php
use Adepc\Img;
use Adepc\Site;

/** @var array $stories @var array $gallery @var array $churches */
$counts = ['all' => t('stories.count', ['count' => count($gallery)])];
foreach ($churches as $c) {
    $counts[$c['slug']] = t('stories.count', ['count' => count(array_filter($gallery, static fn ($m) => (int) $m['church_id'] === (int) $c['id']))]);
}
$filters = array_merge([['value' => 'all', 'label' => t('stories.all')]], array_map(static fn ($c) => ['value' => $c['slug'], 'label' => L($c, 'city')], $churches));
?>
<?= partial('components/page-hero', ['eyebrow' => t('stories.hero.eyebrow'), 'title' => t('stories.hero.title'), 'lead' => t('stories.hero.lead'), 'photo' => Site::slot('stories.hero')]) ?>
<section aria-labelledby="testimonies-title" class="bg-night text-white">
  <h2 id="testimonies-title" class="sr-only"><?= e(t('stories.testimonies')) ?></h2>
  <?php foreach ($stories as $story): ?>
  <figure class="grid-12 items-stretch">
    <?= photo(Site::media($story['media_id']), ['sizes' => '(min-width: 1024px) 42vw, 100vw', 'class' => 'col-span-4 aspect-[4/5] md:col-span-4 md:aspect-auto md:min-h-[36rem] lg:col-span-5']) ?>
    <div class="edge col-span-4 flex flex-col justify-center py-(--section-y) md:col-span-4 lg:col-span-7 lg:pl-[6vw]">
      <p class="caption text-gold"><?= e(t('stories.testimonies')) ?></p>
      <blockquote class="mt-8 font-serif text-[clamp(1.75rem,3.2vw,3.25rem)] leading-[1.15] italic"><?= e(t('format.quote', ['text' => L($story, 'quote')])) ?></blockquote>
      <figcaption class="mt-10 border-l-2 border-gold pl-4"><span class="display block text-h3"><?= e($story['name']) ?></span><span class="mt-1 block text-small text-fog"><?= e(L($story, 'detail')) ?></span></figcaption>
    </div>
  </figure>
  <?php endforeach ?>
</section>
<section aria-labelledby="gallery-title" class="bg-paper py-(--section-y) text-ink">
  <h2 id="gallery-title" class="display edge mb-10 text-display-m"><?= e(t('stories.gallery')) ?></h2>
  <div data-js-gallery="<?= e(json_encode($counts, JSON_UNESCAPED_UNICODE)) ?>">
    <div class="edge flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div role="group" aria-label="<?= e(t('stories.filter')) ?>" class="flex flex-wrap gap-2">
        <?php foreach ($filters as $f): ?>
        <button type="button" aria-pressed="<?= $f['value'] === 'all' ? 'true' : 'false' ?>" class="min-h-11 border border-ink px-4 text-nav font-semibold uppercase transition-colors hover:bg-ink hover:text-white aria-pressed:bg-ink aria-pressed:text-white" data-js-filter="<?= e($f['value']) ?>"><?= e($f['label']) ?></button>
        <?php endforeach ?>
      </div>
      <p class="caption text-stone" aria-live="polite" data-js-count><?= e($counts['all']) ?></p>
    </div>
    <ul class="mt-10 columns-2 gap-1 md:columns-3 xl:columns-4">
      <?php foreach ($gallery as $m): $church = Site::churchById($m['church_id']); ?>
      <li class="relative mb-1 break-inside-avoid"<?= attrs(['data-js-church' => $church['slug'] ?? '']) ?>><?= Img::tag($m, ['alt' => L($m, 'alt'), 'sizes' => '(min-width: 1280px) 25vw, (min-width: 768px) 33vw, 50vw', 'blur' => true, 'class' => 'h-auto w-full']) ?><?php if ($church): ?><span class="caption absolute bottom-0 left-0 bg-ink/70 px-2.5 py-1.5 text-white backdrop-blur-sm"><?= e(t('format.churchCity', ['city' => L($church, 'city')])) ?></span><?php endif ?></li>
      <?php endforeach ?>
    </ul>
  </div>
</section>
<section class="on-gold section-y bg-gold text-ink">
  <div class="edge grid-12 items-end gap-y-8">
    <h2 class="display col-span-4 text-display-m lg:col-span-7"><?= e(t('stories.shareTitle')) ?></h2>
    <div class="col-span-4 lg:col-span-5">
      <p class="text-lead"><?= e(t('stories.shareLead')) ?></p>
      <?= button_link(url('contact', [], ['purpose' => 'story']), t('stories.shareCta'), 'ink', 'mt-8') ?>
    </div>
  </div>
</section>
