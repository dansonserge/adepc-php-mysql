<?php
use Adepc\Img;
use Adepc\Schedule;
use Adepc\Site;

/** @var array $alternates */
$logo = Site::slot('brand.logo');
$phone = setting('org.phone');
$social = Site::items('social');
$short = setting('site.short_name');
?>
<footer class="bg-ink text-white">
  <div class="edge grid-12 gap-y-14 pt-[var(--section-y)] pb-14">
    <div class="col-span-4 md:col-span-8 lg:col-span-4">
      <div class="flex items-start gap-5">
        <div class="relative h-20 w-20 shrink-0 bg-white p-1.5"><?php if ($logo): ?><?= Img::tag($logo, ['alt' => Site::L($logo, 'alt'), 'sizes' => '80px', 'class' => 'h-full w-full object-contain']) ?><?php endif ?></div>
        <p class="text-small text-fog"><span class="block font-semibold text-white"><?= e(Site::setting('org.name.' . Site::defaultLocale())) ?></span><?= e(t('footer.tagline')) ?></p>
      </div>
      <figure class="mt-10 border-l-2 border-gold pl-5">
        <blockquote class="font-serif text-h3 italic leading-snug"><?= e(t('format.quoteTight', ['text' => settingL('motto.text')])) ?></blockquote>
        <figcaption class="caption mt-3 text-fog"><?= e(settingL('motto.ref')) ?></figcaption>
      </figure>
    </div>
    <div class="col-span-4 lg:col-span-4">
      <h2 class="caption mb-6 text-fog"><?= e(t('footer.visit')) ?></h2>
      <ul class="grid gap-5 sm:grid-cols-2">
        <?php foreach (Site::churches() as $c): $sunday = Schedule::serviceOn($c, 'sunday'); ?>
        <li><a class="group block" href="<?= e(url('church', ['slug' => $c['slug']])) ?>"><span class="display block text-[1.75rem] leading-none group-hover:text-gold"><?= e(L($c, 'city')) ?></span><span class="mt-1.5 block text-small text-fog"><?= e(nb($c['street'])) ?><?php if ($sunday): ?><br><?= e(t('format.dayTime', ['day' => t('common.sunday'), 'time' => Schedule::formatTime($sunday['time'])])) ?><?php endif ?></span></a></li>
        <?php endforeach ?>
      </ul>
    </div>
    <div class="col-span-2 lg:col-span-2">
      <h2 class="caption mb-6 text-fog"><?= e(t('footer.explore')) ?></h2>
      <ul class="space-y-1">
        <?php foreach (Site::menu('footer') as $item): ?>
        <li><a class="inline-block py-1 text-body hover:text-gold" href="<?= e(url($item['page_key'])) ?>"><?= e(L($item, 'label')) ?></a></li>
        <?php endforeach ?>
      </ul>
    </div>
    <div class="col-span-2 lg:col-span-2">
      <h2 class="caption mb-6 text-fog"><?= e(t('footer.contact')) ?></h2>
      <address class="space-y-2.5 not-italic">
        <a href="mailto:<?= e(setting('org.email')) ?>" class="block break-all hover:text-gold"><?= e(setting('org.email')) ?></a>
        <p class="text-small text-fog"><?= e(t('format.hqLine', ['label' => settingL('hq.label'), 'building' => setting('hq.building')])) ?><br><?= e(nb(setting('hq.street'))) ?><br><?= e(t('format.localityPostal', ['locality' => setting('hq.locality'), 'postal' => setting('hq.postal_code')])) ?></p>
        <?php if ($phone !== ''): ?><p><?= e($phone) ?></p><?php endif ?>
      </address>
      <?php if ($social): ?>
      <h2 class="caption mt-10 mb-4 text-fog"><?= e(t('footer.follow')) ?></h2>
      <ul class="space-y-2">
        <?php foreach ($social as $s): ?>
        <li><a href="<?= e($s['url']) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-gold"><?= e(L($s, 'title')) ?></a></li>
        <?php endforeach ?>
      </ul>
      <?php endif ?>
    </div>
  </div>
  <div aria-hidden="true" class="edge overflow-hidden"><div data-word="<?= e($short) ?>" class="display fit select-none leading-[0.78] text-white/[0.07] before:content-[attr(data-word)]" style="--chars:<?= js_length($short) ?>;--k:0.56;--fit-max:40rem"></div></div>
  <div class="edge flex flex-col gap-4 border-t border-white/15 py-6 text-small text-fog sm:flex-row sm:items-center sm:justify-between">
    <p><?= e(t('footer.rights', ['year' => (new DateTimeImmutable('now', Schedule::tz()))->format('Y')])) ?></p>
    <div class="flex items-center gap-6">
      <a class="hover:text-white" href="<?= e(url('privacy')) ?>"><?= e(t('footer.privacy')) ?></a>
      <?= partial('partials/language-switcher', ['alternates' => $alternates, 'class' => 'text-white']) ?>
    </div>
  </div>
</footer>
