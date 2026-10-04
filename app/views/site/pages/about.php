<?php
use Adepc\Site;

/** @var array $churches */
$figure = static function (array $item) use ($churches): string {
    return match ($item['value_source']) {
        'churches' => (string) count($churches),
        'cities' => (string) count(array_unique(array_column($churches, 'locality'))),
        'provinces' => (string) count(array_unique(array_column($churches, 'region'))),
        default => (string) $item['value'],
    };
};
?>
<?= partial('components/page-hero', ['eyebrow' => t('about.hero.eyebrow'), 'title' => t('about.hero.title'), 'photo' => Site::slot('about.hero')]) ?>
<section aria-labelledby="who-title" class="section-y bg-paper text-ink">
  <div class="edge grid-12 gap-y-10">
    <div class="col-span-4 md:col-span-8 lg:col-span-3"><?= eyebrow('<span id="who-title">' . e(t('about.who.eyebrow')) . '</span>', '', 'h2') ?></div>
    <p class="col-span-4 text-h2 leading-[1.15] font-semibold md:col-span-8 lg:col-span-9" data-reveal="up"><?= e(t('about.who.p1')) ?></p>
    <p class="col-span-4 text-lead text-stone md:col-span-4 lg:col-span-4 lg:col-start-4" data-reveal="up"><?= e(t('about.who.p2')) ?></p>
    <p class="col-span-4 text-lead text-stone md:col-span-4 lg:col-span-4 lg:col-start-9" data-reveal="up"><?= e(t('about.who.p3')) ?></p>
  </div>
</section>
<section aria-labelledby="heritage-title" class="section-y bg-ink text-white">
  <div class="edge">
    <?= eyebrow('<span id="heritage-title">' . e(t('about.heritage.eyebrow')) . '</span>', '', 'h2') ?>
    <p class="mt-8 max-w-[48ch] text-lead text-white/85"><?= e(t('about.heritage.lead')) ?></p>
    <div class="mt-14 grid gap-px bg-white/20 md:grid-cols-2">
      <?php foreach (Site::items('about.heritage') as $h): ?>
      <div class="bg-ink py-8 md:pr-10 md:odd:pr-10 md:even:pl-10">
        <p class="display text-display-l leading-[0.85]" data-reveal="up"><?= e(L($h, 'short')) ?></p>
        <p class="mt-4 text-lead text-fog"><?= e(L($h, 'title')) ?></p>
      </div>
      <?php endforeach ?>
    </div>
  </div>
</section>
<section class="bg-blue text-white">
  <dl class="edge grid grid-cols-2 gap-px py-16 md:grid-cols-4 md:py-24">
    <?php foreach (Site::items('about.glance') as $g): ?>
    <div class="flex flex-col-reverse py-4">
      <dt class="caption mt-4 text-white/80"><?= e(L($g, 'title')) ?></dt>
      <dd class="display text-display-xl leading-[0.8] tabular-nums"><?= e($figure($g)) ?></dd>
    </div>
    <?php endforeach ?>
  </dl>
</section>
<section aria-labelledby="story-title" class="section-y bg-paper text-ink">
  <div class="grid-12 items-center gap-y-12">
    <?= photo(Site::slot('about.story'), ['sizes' => '(min-width: 1024px) 50vw, 100vw', 'class' => 'col-span-4 aspect-[4/3] md:col-span-4 lg:col-span-6']) ?>
    <div class="col-span-4 space-y-5 px-(--edge) md:col-span-4 md:pl-0 lg:col-span-5 lg:col-start-8">
      <?= eyebrow('<span id="story-title">' . e(t('about.story.eyebrow')) . '</span>', '', 'h2') ?>
      <p class="pt-3 text-h3 leading-snug font-semibold" data-reveal="up"><?= e(t('about.story.p1')) ?></p>
      <p class="text-lead text-stone" data-reveal="up"><?= e(t('about.story.p2')) ?></p>
      <p class="text-lead text-stone" data-reveal="up"><?= e(t('about.story.p3')) ?></p>
    </div>
  </div>
</section>
<section id="mission" aria-labelledby="mission-title" class="section-y scroll-mt-20 bg-cream text-ink">
  <div class="edge grid-12 gap-y-10">
    <div class="col-span-4 md:col-span-8 lg:col-span-4">
      <?= eyebrow(e(t('about.mission.eyebrow'))) ?>
      <h2 id="mission-title" class="display mt-6 text-display-m"><?= e(t('home.mission.title')) ?></h2>
    </div>
    <div class="col-span-4 md:col-span-8 lg:col-span-7 lg:col-start-6">
      <p class="text-lead" data-reveal="up"><?= e(t('about.mission.body')) ?></p>
      <figure class="mt-12 border-t-2 border-ink pt-6">
        <figcaption class="caption text-stone"><?= e(t('about.mission.bannerTitle')) ?></figcaption>
        <ol class="mt-5 space-y-4">
          <?php foreach (Site::items('about.mission.banner') as $i => $b): ?>
          <li class="flex gap-5 text-h3 leading-snug font-semibold"><span class="display text-blue tabular-nums"><?= e(pad2($i + 1)) ?></span><span><?= e(L($b, 'title')) ?></span></li>
          <?php endforeach ?>
        </ol>
      </figure>
    </div>
  </div>
</section>
<section aria-labelledby="vision-title" class="section-y bg-night text-white">
  <div class="edge grid-12 gap-y-10">
    <div class="col-span-4 md:col-span-8 lg:col-span-6">
      <?= eyebrow(e(t('about.vision.eyebrow'))) ?>
      <h2 id="vision-title" class="display mt-6 text-display-m" data-reveal="up"><?= e(t('about.vision.title')) ?></h2>
    </div>
    <div class="col-span-4 md:col-span-8 lg:col-span-5 lg:col-start-8 lg:pt-14">
      <p class="text-lead text-white/85"><?= e(t('about.vision.body')) ?></p>
      <figure class="mt-10 border-l-2 border-gold pl-5">
        <blockquote class="font-serif text-h3 italic"><?= e(t('format.quote', ['text' => t('about.vision.verse')])) ?></blockquote>
        <figcaption class="caption mt-3 text-fog"><?= e(t('about.vision.verseRef')) ?></figcaption>
      </figure>
    </div>
  </div>
</section>
<section aria-labelledby="strategic-title" class="section-y bg-paper text-ink">
  <div class="edge">
    <?= eyebrow(e(t('about.strategic.eyebrow'))) ?>
    <h2 id="strategic-title" class="display mt-6 text-display-m"><?= e(t('about.strategic.title')) ?></h2>
    <ol class="rule mt-12 border-t">
      <?php foreach (Site::items('about.strategic') as $i => $s): ?>
      <li class="rule border-b" data-reveal="up">
        <div class="grid-12 items-baseline gap-y-2 py-6 md:py-8">
          <span class="caption col-span-4 text-blue tabular-nums md:col-span-1"><?= e(pad2($i + 1)) ?></span>
          <h3 class="display col-span-4 text-h1 md:col-span-7 lg:col-span-6"><?= e(L($s, 'title')) ?></h3>
          <p class="col-span-4 max-w-[40ch] text-lead text-stone md:col-span-7 md:col-start-2 lg:col-span-5 lg:col-start-8"><?= e(L($s, 'body')) ?></p>
        </div>
      </li>
      <?php endforeach ?>
    </ol>
  </div>
</section>
<section aria-labelledby="values-title" class="section-y bg-cream text-ink">
  <div class="edge">
    <?= eyebrow('<span id="values-title">' . e(t('about.values.eyebrow')) . '</span>', '', 'h2') ?>
    <ul class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-2 md:gap-x-10">
      <?php foreach (Site::items('about.values') as $i => $v): ?>
      <li class="display flex items-center gap-6 text-display-m md:gap-10"><?php if ($i > 0): ?><span aria-hidden="true" class="block h-4 w-4 bg-gold md:h-5 md:w-5"></span><?php endif ?><?= e(L($v, 'title')) ?></li>
      <?php endforeach ?>
    </ul>
  </div>
</section>
<section aria-labelledby="beliefs-title" class="section-y bg-ink text-white">
  <div class="edge">
    <?= eyebrow(e(t('about.beliefs.eyebrow'))) ?>
    <h2 id="beliefs-title" class="display mt-6 text-display-m"><?= e(t('about.beliefs.title')) ?></h2>
    <div class="mt-14 grid gap-px bg-white/20 md:grid-cols-3">
      <?php foreach (Site::items('about.beliefs') as $i => $b): ?>
      <article class="bg-ink py-8 md:px-8 md:first:pl-0">
        <p class="caption text-gold tabular-nums"><?= e(pad2($i + 1)) ?></p>
        <h3 class="display mt-4 text-h1"><?= e(L($b, 'title')) ?></h3>
        <p class="mt-4 text-lead text-white/80"><?= e(L($b, 'body')) ?></p>
      </article>
      <?php endforeach ?>
    </div>
  </div>
</section>
<?= partial('components/pastor-word') ?>
<section class="section-y bg-blue text-white">
  <div class="edge grid-12 items-end gap-y-8">
    <h2 class="display col-span-4 text-display-l md:col-span-5 lg:col-span-7"><?= e(t('about.cta.title')) ?></h2>
    <div class="col-span-4 md:col-span-3 lg:col-span-5">
      <p class="text-lead text-white/85"><?= e(t('about.cta.lead')) ?></p>
      <?= button_link(url('churches'), t('about.cta.button'), 'gold', 'mt-8') ?>
    </div>
  </div>
</section>
<section class="bg-paper py-16 text-ink">
  <figure class="edge text-center">
    <blockquote class="mx-auto max-w-[30ch] font-serif text-h2 italic"><?= e(t('format.quote', ['text' => settingL('motto.text')])) ?></blockquote>
    <figcaption class="caption mt-4 text-stone"><?= e(settingL('motto.ref')) ?></figcaption>
  </figure>
</section>
