<?php
/** @var array $events @var array $churches */
?>
<section aria-labelledby="events-title" class="section-y bg-paper text-ink">
  <div class="edge">
    <?= fit_text([t('home.events.title')], 'h2', 'leading-[0.85]', '9rem', 0.5, false, 'events-title') ?>
    <div class="mt-12 md:mt-16"><?= partial('components/event-list', ['events' => $events, 'churches' => $churches]) ?></div>
    <?= arrow_link(url('events'), t('home.events.all'), 'mt-10') ?>
  </div>
</section>
