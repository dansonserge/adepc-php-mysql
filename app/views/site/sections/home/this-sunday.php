<?php
use Adepc\Schedule;

/** "What happens this Sunday?" in one glance. @var array $churches */
$next = Schedule::nextSunday();
$friday = $churches ? Schedule::serviceOn($churches[0], 'friday') : null;
?>
<section id="this-sunday" aria-labelledby="sunday-title" class="section-y bg-paper text-ink">
  <div class="edge">
    <div class="grid-12 items-end gap-y-8">
      <div class="col-span-4 md:col-span-5 lg:col-span-7">
        <?= eyebrow(e(t('home.sunday.eyebrow'))) ?>
        <h2 id="sunday-title" class="display mt-6 text-display-m" data-reveal="up"><?= e(t('home.sunday.title')) ?></h2>
      </div>
      <p class="col-span-4 md:col-span-3 md:text-right lg:col-span-5" data-reveal="up"><?php if ($next['isToday']): ?><span class="caption mb-2 block text-blue"><?= e(t('home.sunday.today')) ?></span><?php endif ?><span class="display block text-h2 first-letter:uppercase"><?= e(Schedule::dayDate($next['date'])) ?></span><span class="caption mt-2 block text-stone"><?= e(t('common.localTime')) ?></span></p>
    </div>
    <ol class="rule mt-12 border-t md:mt-16">
      <?php foreach (Schedule::bySundayTime($churches) as $church):
          $sunday = Schedule::serviceOn($church, 'sunday');
          if (!$sunday) continue;
          $p = Schedule::timeParts($sunday['time']); ?>
      <li class="rule border-b" data-reveal="up">
        <div class="grid-12 items-center gap-y-3 py-6 md:py-8">
          <p class="display col-span-2 text-display-m tabular-nums md:col-span-2 lg:col-span-3"><time datetime="<?= e($sunday['time']) ?>"><?= e($p['main']) ?><?php if ($p['suffix'] !== ''): ?><span class="ml-1.5 align-top text-h3 leading-none"><?= e($p['suffix']) ?></span><?php endif ?></time></p>
          <h3 class="display col-span-2 text-right text-h1 md:col-span-3 md:text-left lg:col-span-4"><a class="decoration-gold decoration-4 underline-offset-8 hover:underline" href="<?= e(url('church', ['slug' => $church['slug']])) ?>"><?= e(L($church, 'city')) ?></a></h3>
          <p class="col-span-4 text-small text-stone md:col-span-3"><?= e(nb($church['street'])) ?><br><?= e(t('format.localityRegionPostal', ['locality' => $church['locality'], 'region' => $church['region'], 'postal' => $church['postal_code']])) ?></p>
          <div class="col-span-4 md:col-span-3 md:col-start-6 lg:col-span-2 lg:col-start-auto lg:text-right"><?= arrow_link(Schedule::directionsUrl($church), t('common.directions')) ?></div>
        </div>
      </li>
      <?php endforeach ?>
    </ol>
    <div class="mt-10 flex flex-col gap-8 md:mt-14 lg:flex-row lg:items-center lg:justify-between">
      <?php if ($friday): ?><p class="max-w-[40ch] text-lead" data-reveal="up"><?= e(t('home.sunday.friday', ['time' => Schedule::formatTime($friday['time'])])) ?></p><?php endif ?>
      <div class="flex flex-wrap gap-3">
        <?= button_link(url('churches'), t('home.sunday.findChurch'), 'ink') ?>
        <?= button_link(url('watch'), t('home.sunday.watchOnline'), 'outline-dark') ?>
      </div>
    </div>
  </div>
</section>
