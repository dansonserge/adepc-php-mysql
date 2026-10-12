<?php
use Adepc\Schedule;

/**
 * Where and when: churches, venue, time, Sunday times, registration.
 * @var array $event @var array $churches @var bool $detailed @var string $class
 */
$detailed = $detailed ?? false;
?>
<dl class="<?= e($class) ?>">
  <div>
    <dt class="caption text-stone"><?= e(t('events.church')) ?></dt>
    <dd class="mt-1 font-semibold"><?= e(event_where($event)) ?></dd>
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
