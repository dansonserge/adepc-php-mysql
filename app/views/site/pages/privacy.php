<?php
$policy = paragraphs(settingL('privacy.policy'));
$officer = paragraphs(settingL('privacy.officer'));
?>
<?= partial('components/page-hero', ['eyebrow' => t('privacy.hero.eyebrow'), 'title' => t('privacy.hero.title'), 'tone' => 'night', 'titleClass' => 'text-display-m']) ?>
<section class="section-y bg-paper text-ink">
  <div class="edge grid-12 gap-y-14">
    <div class="col-span-4 md:col-span-8 lg:col-span-7">
      <h2 class="display text-h1"><?= e(t('privacy.summaryTitle')) ?></h2>
      <div class="mt-8 space-y-6 text-lead">
        <p><?= e(t('privacy.summary1')) ?></p>
        <p><?= e(t('privacy.summary2')) ?></p>
        <p><?= e(t('privacy.summary3')) ?></p>
      </div>
      <?php if (setting('analytics.ga_id') !== ''): ?>
      <div class="mt-10"><button type="button" class="<?= e(button_class('outline-dark')) ?>" data-js-consent-reopen><span><?= e(t('privacy.changeConsent')) ?></span></button></div>
      <?php endif ?>
    </div>
    <aside class="col-span-4 space-y-10 md:col-span-8 lg:col-span-4 lg:col-start-9">
      <?php if ($policy): ?>
      <div class="rule border-t-2 pt-6">
        <h2 class="caption"><?= e(t('privacy.policyTitle')) ?></h2>
        <div class="mt-4 space-y-4 text-body"><?php foreach ($policy as $p): ?><p><?= e($p) ?></p><?php endforeach ?></div>
      </div>
      <?php endif ?>
      <?php if ($officer): ?>
      <div class="rule border-t-2 pt-6">
        <h2 class="caption"><?= e(t('privacy.officerTitle')) ?></h2>
        <div class="mt-4 space-y-4 text-body"><?php foreach ($officer as $p): ?><p><?= e($p) ?></p><?php endforeach ?></div>
      </div>
      <?php endif ?>
    </aside>
  </div>
</section>
