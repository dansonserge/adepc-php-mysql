<?php
use Adepc\Schedule;
use Adepc\Site;

/** The churches as one edge-to-edge band of photography. @var array $churches */
?>
<section aria-labelledby="churches-title" class="bg-cream pt-(--section-y) text-ink">
  <div class="edge grid-12 items-end gap-y-6 pb-12 md:pb-16">
    <div class="col-span-4 md:col-span-5 lg:col-span-7">
      <?= eyebrow(e(t('home.churches.eyebrow'))) ?>
      <h2 id="churches-title" class="display mt-6 text-display-m" data-reveal="up"><?= e(t('home.churches.title')) ?></h2>
    </div>
    <div class="col-span-4 md:col-span-3 lg:col-span-4 lg:col-start-9">
      <p class="text-lead" data-reveal="up"><?= e(t('home.churches.lead')) ?></p>
      <?= arrow_link(url('churches'), t('home.churches.all'), 'mt-6') ?>
    </div>
  </div>
  <ul class="flex snap-x snap-mandatory overflow-x-auto bg-ink [scrollbar-width:none] md:grid md:grid-cols-4 md:overflow-visible">
    <?php foreach ($churches as $i => $church):
        $sunday = Schedule::serviceOn($church, 'sunday');
        $friday = Schedule::serviceOn($church, 'friday'); ?>
    <li class="group relative w-[82vw] shrink-0 snap-start border-r border-ink md:w-auto md:border-r last:border-r-0">
      <a class="relative block h-[72svh] min-h-[28rem] overflow-hidden text-white md:h-[80svh] md:max-h-[56rem]" href="<?= e(url('church', ['slug' => $church['slug']])) ?>">
        <?= photo(Site::media($church['hero_media_id']), ['focus' => $church['band_focus'] ?: null, 'sizes' => '(min-width: 768px) 25vw, 82vw', 'class' => 'absolute inset-0 transition-transform duration-[1.2s] ease-expo group-hover:scale-105']) ?>
        <div class="absolute inset-0 bg-linear-to-t from-ink/95 via-ink/10 via-55% to-transparent"></div>
        <span class="caption absolute top-6 left-6 tabular-nums"><?= e(pad2($i + 1)) ?></span>
        <div class="absolute inset-x-0 bottom-0 p-6 lg:p-8">
          <h3 class="display text-[clamp(2.75rem,4.6vw,5.5rem)] leading-[0.9]"><?= e(L($church, 'city')) ?></h3>
          <p class="mt-4 text-small text-white/85"><?= e(nb($church['street'])) ?></p>
          <dl class="mt-4 flex gap-6 border-t border-white/25 pt-4 text-small">
            <?php if ($sunday): ?><div><dt class="caption text-white/70"><?= e(t('common.sunday')) ?></dt><dd class="mt-1 font-semibold"><?= e(Schedule::formatTime($sunday['time'])) ?></dd></div><?php endif ?>
            <?php if ($friday): ?><div><dt class="caption text-white/70"><?= e(t('common.friday')) ?></dt><dd class="mt-1 font-semibold"><?= e(Schedule::formatTime($friday['time'])) ?></dd></div><?php endif ?>
          </dl>
        </div>
        <span aria-hidden="true" class="absolute inset-x-0 bottom-0 h-1.5 origin-left scale-x-0 bg-gold transition-transform duration-500 ease-expo group-hover:scale-x-100"></span>
      </a>
    </li>
    <?php endforeach ?>
  </ul>
</section>
