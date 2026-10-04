<?php
// Giving as part of the mission, full width and impossible to miss.
$interac = setting('giving.interac.email');
?>
<section aria-labelledby="give-title" class="on-gold section-y bg-gold text-ink">
  <div class="edge">
    <?= eyebrow(e(t('home.give.eyebrow'))) ?>
    <div class="mt-6 grid-12 items-end gap-y-8">
      <?= fit_text([t('home.give.title')], 'h2', 'col-span-4 leading-[0.78] md:col-span-5 md:[--fit-span:0.6] lg:col-span-7 lg:[--fit-span:0.56]', '26rem', 0.54, false, 'give-title') ?>
      <p class="col-span-4 max-w-[26ch] text-h3 leading-tight font-semibold md:col-span-3 lg:col-span-5 lg:pb-4" data-reveal="up"><?= e(t('home.give.lead')) ?></p>
    </div>
    <div class="mt-14 grid-12 gap-y-10 md:mt-20">
      <figure class="col-span-4 border-l-2 border-ink pl-5 md:col-span-8 lg:col-span-5">
        <blockquote class="font-serif text-h3 leading-snug italic"><?= e(t('giving.verse')) ?></blockquote>
        <figcaption class="caption mt-3"><?= e(t('giving.verseRef')) ?></figcaption>
      </figure>
      <div class="col-span-4 border-t-2 border-ink pt-6 md:col-span-4 lg:col-span-3 lg:col-start-7">
        <h3 class="display text-h2"><?= e(t('giving.paypalTitle')) ?></h3>
        <p class="mt-3 text-small"><?= e(t('giving.paypalBody')) ?></p>
        <?= button_link(url('give'), t('home.give.cta'), 'ink', 'mt-6') ?>
      </div>
      <div class="col-span-4 border-t-2 border-ink pt-6 md:col-span-4 lg:col-span-3 lg:col-start-10">
        <h3 class="display text-h2"><?= e(t('giving.interacTitle')) ?></h3>
        <p class="mt-3 text-small"><?= e(t('giving.interacBody')) ?></p>
        <p class="mt-3 font-semibold break-all select-all"><?= e($interac) ?></p>
        <?= partial('components/copy-button', ['value' => $interac, 'class' => 'mt-4']) ?>
      </div>
    </div>
  </div>
</section>
