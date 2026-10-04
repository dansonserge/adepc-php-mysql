<?php
use Adepc\Site;

$interac = setting('giving.interac.email');
$paypal = [
    'sdk' => strtr(setting('giving.paypal.sdk_url'), ['{client}' => rawurlencode(setting('giving.paypal.client_id')), '{currency}' => rawurlencode(setting('giving.paypal.currency'))]),
    'button' => setting('giving.paypal.button_id'),
    'loading' => t('giving.paypalLoading'),
    'error' => t('giving.paypalError'),
];
?>
<?= partial('components/page-hero', ['eyebrow' => t('give.hero.eyebrow'), 'title' => t('give.hero.title'), 'lead' => t('give.hero.lead'), 'photo' => Site::slot('give.hero'), 'titleClass' => 'text-display-xl']) ?>
<section aria-labelledby="ways-title" class="section-y bg-paper text-ink">
  <div class="edge">
    <h2 id="ways-title" class="caption"><?= e(t('give.ways')) ?></h2>
    <div class="mt-8 grid gap-px bg-ink/15 lg:grid-cols-2">
      <article class="bg-paper py-10 lg:pr-12">
        <p class="caption text-blue tabular-nums"><?= e(pad2(1)) ?></p>
        <h3 class="display mt-4 text-display-m"><?= e(t('giving.paypalTitle')) ?></h3>
        <p class="mt-5 max-w-[44ch] text-lead text-stone"><?= e(t('giving.paypalBody')) ?></p>
        <div class="mt-10 max-w-sm"><div class="min-h-[22rem]" data-js-paypal="<?= e(json_encode($paypal, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>"><div id="paypal-donate"></div></div></div>
      </article>
      <article class="bg-paper py-10 lg:pl-12">
        <p class="caption text-blue tabular-nums"><?= e(pad2(2)) ?></p>
        <h3 class="display mt-4 text-display-m"><?= e(t('giving.interacTitle')) ?></h3>
        <p class="mt-5 max-w-[44ch] text-lead text-stone"><?= e(t('giving.interacBody')) ?></p>
        <p class="mt-8 border-l-4 border-gold pl-4 text-h2 font-semibold break-all select-all"><?= e($interac) ?></p>
        <?= partial('components/copy-button', ['value' => $interac, 'class' => 'mt-6']) ?>
      </article>
    </div>
  </div>
</section>
<section class="on-gold section-y bg-gold text-ink">
  <figure class="edge grid-12 gap-y-6">
    <blockquote class="col-span-4 font-serif text-[clamp(1.75rem,3.4vw,3.5rem)] leading-[1.15] italic md:col-span-8 lg:col-span-9"><?= e(t('format.quote', ['text' => t('giving.verse')])) ?></blockquote>
    <figcaption class="caption col-span-4 md:col-span-8"><?= e(t('giving.verseRef')) ?></figcaption>
  </figure>
</section>
<section class="section-y bg-ink text-white">
  <div class="edge grid-12 items-end gap-y-8">
    <h2 class="display col-span-4 text-display-m md:col-span-5 lg:col-span-7"><?= e(t('give.questions')) ?></h2>
    <div class="col-span-4 md:col-span-3 lg:col-span-5">
      <p class="text-lead text-white/85"><?= e(t('give.questionsLead')) ?></p>
      <?= arrow_link('mailto:' . setting('org.email'), setting('org.email'), 'mt-6 text-gold normal-case!') ?>
    </div>
  </div>
</section>
