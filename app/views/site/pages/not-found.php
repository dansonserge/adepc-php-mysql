<section class="flex min-h-[80svh] flex-col justify-end bg-ink pt-[calc(var(--header-h)+4rem)] pb-16 text-white">
  <div class="edge">
    <p class="display text-display-xl leading-[0.8] text-gold"><?= e(t('notFound.code')) ?></p>
    <h1 class="display mt-6 text-display-m"><?= e(t('notFound.title')) ?></h1>
    <p class="mt-6 max-w-[40ch] text-lead text-white/85"><?= e(t('notFound.lead')) ?></p>
    <?= button_link(url('home'), t('notFound.home'), 'gold', 'mt-10') ?>
  </div>
</section>
