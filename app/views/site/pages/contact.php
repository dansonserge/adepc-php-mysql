<?php
use Adepc\Schedule;
use Adepc\Site;

/** @var array $churches @var string $defaultChurch @var string $defaultPurpose @var array $state */
?>
<?= partial('components/page-hero', ['eyebrow' => t('contact.hero.eyebrow'), 'title' => t('contact.hero.title'), 'lead' => t('contact.hero.lead'), 'tone' => 'blue', 'titleClass' => 'text-display-m']) ?>
<section aria-labelledby="form-title" class="section-y bg-paper text-ink">
  <div class="edge grid-12 gap-y-16">
    <div class="col-span-4 md:col-span-8 lg:col-span-7">
      <h2 id="form-title" class="display text-display-m"><?= e(t('contact.form.title')) ?></h2>
      <div class="mt-12"><?= partial('components/contact-form', ['churches' => $churches, 'defaultChurch' => $defaultChurch, 'defaultPurpose' => $defaultPurpose, 'state' => $state]) ?></div>
    </div>
    <aside class="col-span-4 space-y-12 md:col-span-8 lg:col-span-4 lg:col-start-9">
      <div>
        <?= eyebrow(e(t('contact.general'))) ?>
        <a href="mailto:<?= e(setting('org.email')) ?>" class="mt-4 block text-h2 font-semibold break-all hover:text-blue"><?= e(setting('org.email')) ?></a>
        <p class="mt-4 text-small text-stone"><?= e(t('format.hqLine', ['label' => settingL('hq.label'), 'building' => setting('hq.building')])) ?><br><?= e(t('format.streetLocalityPostal', ['street' => nb(setting('hq.street')), 'locality' => setting('hq.locality'), 'postal' => setting('hq.postal_code')])) ?></p>
      </div>
      <figure class="border-l-2 border-gold pl-5">
        <blockquote class="font-serif text-h3 leading-snug italic"><?= e(t('format.quote', ['text' => t('contact.form.verse')])) ?></blockquote>
        <figcaption class="caption mt-3 text-stone"><?= e(t('contact.form.verseRef')) ?></figcaption>
      </figure>
    </aside>
  </div>
</section>
<section aria-labelledby="reach-title" class="section-y bg-cream text-ink">
  <div class="edge">
    <h2 id="reach-title" class="display text-display-m"><?= e(t('contact.reach')) ?></h2>
    <ul class="rule mt-12 border-t">
      <?php foreach ($churches as $c): ?>
      <li class="rule border-b">
        <div class="grid-12 items-baseline gap-y-3 py-6">
          <a class="display col-span-4 text-h1 hover:text-blue md:col-span-3 lg:col-span-4" href="<?= e(url('church', ['slug' => $c['slug']])) ?>"><?= e(L($c, 'city')) ?></a>
          <p class="col-span-4 text-body md:col-span-2 lg:col-span-2"><?php if (!empty($c['phone'])): ?><a href="<?= e(Schedule::telHref($c['phone'])) ?>" class="font-semibold underline-offset-4 hover:underline"><span class="sr-only"><?= e(t('format.srLabel', ['label' => t('common.phone')])) ?></span><?= e($c['phone']) ?></a><?php endif ?></p>
          <p class="col-span-4 text-body break-all md:col-span-3 lg:col-span-3"><?php if (!empty($c['email'])): ?><a href="mailto:<?= e($c['email']) ?>" class="underline-offset-4 hover:underline"><?= e($c['email']) ?></a><?php endif ?></p>
          <p class="col-span-4 text-small text-stone md:col-span-8 lg:col-span-3"><?= e(t('format.streetLocality', ['street' => nb($c['street']), 'locality' => $c['locality']])) ?></p>
        </div>
      </li>
      <?php endforeach ?>
    </ul>
  </div>
</section>
