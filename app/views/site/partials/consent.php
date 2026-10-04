<?php
// Québec's Law 25: Google Analytics (cookies) loads only after "Allow".
// Without a GA4 ID there is nothing to consent to, so nothing renders.
$ga = setting('analytics.ga_id');
if ($ga === '') {
    return;
}
?>
<template data-js-consent-template data-js-ga-id="<?= e($ga) ?>">
  <section aria-label="<?= e(t('consent.label')) ?>" class="fixed inset-x-0 bottom-[var(--tabbar-h)] z-40 border-t-4 border-gold bg-ink text-white md:inset-x-auto md:right-(--edge) md:bottom-(--edge) md:max-w-md md:border-t-0 md:border-l-4" data-js-consent>
    <div class="p-5 md:p-6">
      <p class="text-small"><?= e(t('consent.text')) ?></p>
      <div class="mt-4 flex flex-wrap items-center gap-3">
        <button type="button" class="<?= e(button_class('gold', 'min-h-11 px-5')) ?>" data-js-consent-choice="granted"><span><?= e(t('consent.accept')) ?></span></button>
        <button type="button" class="<?= e(button_class('outline-light', 'min-h-11 px-5')) ?>" data-js-consent-choice="denied"><span><?= e(t('consent.decline')) ?></span></button>
        <a class="text-small underline underline-offset-4" href="<?= e(url('privacy')) ?>"><?= e(t('consent.more')) ?></a>
      </div>
    </div>
  </section>
</template>
