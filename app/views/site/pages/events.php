<?php
use Adepc\Schedule;

/** @var array $events @var array $churches */
?>
<?= partial('components/page-hero', ['eyebrow' => t('events.hero.eyebrow'), 'title' => t('events.hero.title'), 'lead' => t('events.hero.lead'), 'tone' => 'blue']) ?>
<section class="section-y bg-paper text-ink">
  <div class="edge"><?= partial('components/event-list', ['events' => $events, 'churches' => $churches, 'detailed' => true]) ?></div>
</section>
<section aria-labelledby="schedule-title" class="section-y bg-cream text-ink">
  <div class="edge grid-12 gap-y-10">
    <div class="col-span-4 md:col-span-8 lg:col-span-4">
      <h2 id="schedule-title" class="display text-display-m"><?= e(t('events.scheduleTitle')) ?></h2>
      <p class="mt-6 text-lead text-stone"><?= e(t('events.scheduleLead')) ?></p>
    </div>
    <div class="col-span-4 overflow-x-auto md:col-span-8 lg:col-span-7 lg:col-start-6">
      <table class="w-full min-w-[30rem] border-collapse text-left">
        <caption class="sr-only"><?= e(t('events.scheduleTitle')) ?></caption>
        <thead>
          <tr class="caption text-stone">
            <th scope="col" class="rule border-b pb-4 font-semibold"><?= e(t('events.church')) ?></th>
            <th scope="col" class="rule border-b pb-4 font-semibold"><?= e(t('common.sunday')) ?></th>
            <th scope="col" class="rule border-b pb-4 font-semibold"><?= e(t('common.friday')) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($churches as $c): $sun = Schedule::serviceOn($c, 'sunday'); $fri = Schedule::serviceOn($c, 'friday'); ?>
          <tr class="rule border-b">
            <th scope="row" class="py-5 pr-4"><a class="display text-h2 hover:text-blue" href="<?= e(url('church', ['slug' => $c['slug']])) ?>"><?= e(L($c, 'city')) ?></a></th>
            <td class="py-5 pr-4 text-h3 font-semibold tabular-nums"><?= $sun ? e(Schedule::formatTime($sun['time'])) : '' ?></td>
            <td class="py-5 text-h3 font-semibold tabular-nums"><?= $fri ? e(Schedule::formatTime($fri['time'])) : '' ?></td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
