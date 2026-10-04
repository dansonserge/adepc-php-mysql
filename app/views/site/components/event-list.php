<?php
use Adepc\Img;
use Adepc\Schedule;
use Adepc\Site;

/**
 * Editorial list: huge date (or rhythm) on the left, title, then where and when.
 * @var array $events @var array $churches @var bool $detailed
 */
$detailed = $detailed ?? false;
$cityOf = static fn ($id) => ($c = Site::churchById($id)) ? L($c, 'city') : '';
?>
<ol class="rule border-t">
  <?php foreach ($events as $event):
      $where = $event['all_churches'] ? t('common.allChurches') : implode(t('format.separator'), array_map($cityOf, $event['church_ids']));
      $featured = $event['kind'] === 'featured';
      $media = Site::media($event['media_id']);
      $people = array_values(array_filter(array_map('trim', explode("\n", (string) $event['people'])), 'strlen')); ?>
  <li class="group rule relative border-b" data-reveal="up">
    <article class="grid-12 gap-y-5 py-8 md:py-12">
      <div class="col-span-4 md:col-span-2 lg:col-span-3">
        <?php if ($event['date_mode'] === 'date' && $event['event_date']): $d = Schedule::eventDate($event['event_date']); ?>
        <p class="display leading-[0.85]"><span class="block text-display-m"><?= e($d['day']) ?></span><span class="block text-h2"><?= e(t('date.monthYear', ['month' => $d['month'], 'year' => $d['year']])) ?></span></p>
        <?php elseif ($event['date_mode'] === 'text'): ?>
        <p class="display text-h2 leading-none"><?= e(L($event, 'date_text')) ?></p>
        <?php else: ?>
        <p class="display text-h2 leading-none"><?= e(L($event, 'recurrence')) ?></p>
        <?php endif ?>
        <?php if ($featured): ?><p class="caption mt-4 text-blue"><?= e(t('events.featured')) ?></p><?php endif ?>
      </div>
      <div class="col-span-4 md:col-span-4 lg:col-span-5">
        <h3 class="display leading-[0.95] <?= $featured ? 'text-h1' : 'text-h2' ?>"><?= e(L($event, 'title')) ?></h3>
        <p class="mt-4 max-w-[52ch] text-stone <?= $detailed ? 'text-body' : 'text-small' ?>"><?= e(L($event, 'description')) ?></p>
        <?php if ($people): ?>
        <p class="mt-4 text-small"><span class="caption mr-2 text-stone"><?= e(t('common.with')) ?></span><?= e(implode(t('format.separator'), $people)) ?></p>
        <?php endif ?>
      </div>
      <dl class="col-span-4 space-y-3 text-small md:col-span-2 lg:col-span-3 lg:col-start-10">
        <div>
          <dt class="caption text-stone"><?= e(t('events.church')) ?></dt>
          <dd class="mt-1 font-semibold"><?= e($where) ?></dd>
        </div>
        <?php if (L($event, 'venue') !== ''): ?>
        <div>
          <dt class="caption text-stone"><?= e(t('events.venue')) ?></dt>
          <dd class="mt-1"><?= e(L($event, 'venue')) ?></dd>
        </div>
        <?php endif ?>
        <?php if ($event['time_mode'] !== 'none'): ?>
        <div>
          <dt class="caption text-stone"><?= e(t('events.time')) ?></dt>
          <dd class="mt-1"><?= e($event['time_mode'] === 'time' ? Schedule::formatTime((string) $event['event_time']) : L($event, 'time_text')) ?></dd>
        </div>
        <?php endif ?>
        <?php if ($event['show_sunday_times']): ?>
        <div>
          <dt class="sr-only"><?= e(t('common.sunday')) ?></dt>
          <dd class="mt-1 space-y-0.5">
            <?php foreach ($churches as $c): $s = Schedule::serviceOn($c, 'sunday'); if (!$s) continue; ?>
            <span class="flex justify-between gap-4 tabular-nums"><span><?= e(L($c, 'city')) ?></span><span class="font-semibold"><?= e(Schedule::formatTime($s['time'])) ?></span></span>
            <?php endforeach ?>
          </dd>
        </div>
        <?php endif ?>
        <?php if ($detailed && !empty($event['registration_url'])): ?>
        <div class="pt-2">
          <dt class="sr-only"><?= e(t('events.registration')) ?></dt>
          <dd><?= external_button($event['registration_url'], t('events.register'), 'ink') ?></dd>
        </div>
        <?php endif ?>
      </dl>
      <?php if ($media): ?>
      <div class="relative col-span-4 aspect-[3/2] overflow-hidden md:hidden"><?= Img::tag($media, ['alt' => L($media, 'alt'), 'fill' => true, 'sizes' => '100vw', 'class' => 'object-cover']) ?></div>
      <?php endif ?>
    </article>
    <?php if ($media): ?>
    <div aria-hidden="true" class="pointer-events-none absolute top-1/2 right-[24%] hidden aspect-[4/3] w-[16vw] -translate-y-1/2 scale-95 overflow-hidden opacity-0 transition duration-500 ease-expo group-hover:scale-100 group-hover:opacity-100 lg:block"><?= Img::tag($media, ['alt' => '', 'fill' => true, 'sizes' => '16vw', 'class' => 'object-cover']) ?></div>
    <?php endif ?>
  </li>
  <?php endforeach ?>
</ol>
