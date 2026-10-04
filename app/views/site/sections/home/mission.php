<?php
use Adepc\Site;
?>
<section aria-labelledby="mission-title" class="section-y bg-blue text-white">
  <div class="edge">
    <div class="grid-12 gap-y-8">
      <div class="col-span-4 md:col-span-8 lg:col-span-4">
        <?= eyebrow(e(t('home.mission.eyebrow'))) ?>
        <h2 id="mission-title" class="sr-only"><?= e(t('home.mission.title')) ?></h2>
      </div>
      <p class="col-span-4 text-lead md:col-span-6 lg:col-span-6 lg:col-start-7" data-reveal="up"><?= e(t('home.mission.lead')) ?></p>
    </div>
    <ol class="mt-14 border-t border-white/25 md:mt-20">
      <?php foreach (Site::items('home.mission.rows') as $i => $row): ?>
      <li class="border-b border-white/25" data-reveal="up">
        <div class="grid-12 items-baseline gap-y-3 py-6 md:py-8">
          <span class="caption col-span-4 text-gold tabular-nums md:col-span-1"><?= e(pad2($i + 1)) ?></span>
          <p class="display col-span-4 text-display-l md:col-span-7 lg:col-span-6" aria-hidden="true"><?= e(L($row, 'title')) ?></p>
          <p class="col-span-4 max-w-[36ch] text-lead text-white/85 md:col-span-8 md:col-start-2 lg:col-span-5 lg:col-start-8"><?= e(L($row, 'body')) ?></p>
        </div>
      </li>
      <?php endforeach ?>
    </ol>
    <?= arrow_link(url('about', [], [], 'mission'), t('home.mission.more'), 'mt-10 text-white') ?>
  </div>
</section>
