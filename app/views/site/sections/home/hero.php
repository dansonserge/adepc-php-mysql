<?php
use Adepc\Img;
use Adepc\Schedule;
use Adepc\Site;

/** @var array $churches */
$poster = Site::slot('home.hero.poster');
$mp4 = Site::slot('home.hero.mp4');
$webm = Site::slot('home.hero.webm');
$lines = array_map(static fn ($i) => L($i, 'title'), Site::items('home.hero.lines'));
$video = $mp4 ? json_encode([
    'mp4' => Site::mediaUrl($mp4),
    'webm' => $webm ? Site::mediaUrl($webm) : null,
    'pause' => t('common.pauseBackground'),
    'play' => t('common.playBackground'),
    'pauseIcon' => icon('pause', 'h-4 w-4'),
    'playIcon' => icon('play', 'h-4 w-4'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
?>
<section aria-labelledby="hero-title" class="relative isolate flex min-h-[calc(100svh-var(--tabbar-h))] flex-col justify-end overflow-hidden bg-ink text-white"<?= attrs(['data-js-hero-video' => $video]) ?>>
  <div aria-hidden="true" class="absolute inset-0 -z-30"><?php if ($poster): ?><?= Img::tag($poster, ['alt' => '', 'fill' => true, 'preload' => true, 'sizes' => '(orientation: portrait) 120vh, 100vw', 'blur' => true, 'class' => 'object-cover']) ?><?php endif ?></div>
  <div aria-hidden="true" class="absolute inset-0 -z-10 bg-linear-to-t from-ink via-ink/45 to-ink/25"></div>
  <div class="edge pt-[calc(var(--header-h)+3rem)] pb-10 md:pb-14">
    <p class="caption fade-late mb-6 max-w-[34ch] text-white/85"><?= e(t('home.hero.eyebrow')) ?></p>
    <?= fit_text($lines, 'h1', 'leading-[0.84]', 'min(17rem, 23svh)', 0.5, true, 'hero-title') ?>
    <div class="fade-late mt-8 grid-12 items-end gap-y-8 md:mt-10">
      <p class="col-span-4 max-w-[38ch] text-lead text-white/90 md:col-span-5"><?= e(t('home.hero.lead')) ?></p>
      <div class="col-span-4 flex flex-wrap gap-3 md:col-span-3 md:justify-end lg:col-span-5 lg:col-start-8">
        <?= button_link(url('churches'), t('home.hero.join'), 'gold') ?>
        <a href="#film" class="<?= e(button_class('outline-light')) ?>"><span><?= e(t('home.hero.watch')) ?></span><?= arrow() ?></a>
      </div>
    </div>
  </div>
  <a href="#this-sunday" class="group fade-late relative block border-t border-white/20 bg-ink/50 backdrop-blur-md transition-colors hover:bg-ink">
    <div class="edge flex min-h-16 items-center justify-between gap-6 py-4">
      <span class="caption shrink-0 text-gold"><?= e(t('home.hero.next')) ?></span>
      <ul class="hidden flex-1 items-center justify-end gap-8 text-small md:flex">
        <?php foreach (Schedule::bySundayTime($churches) as $c): $sunday = Schedule::serviceOn($c, 'sunday'); ?>
        <li class="flex items-baseline gap-2 whitespace-nowrap"><span class="font-semibold tabular-nums"><?= $sunday ? e(Schedule::formatTime($sunday['time'])) : '' ?></span><span class="text-white/70"><?= e(L($c, 'city')) ?></span></li>
        <?php endforeach ?>
      </ul>
      <span class="flex items-center gap-3 text-nav font-semibold uppercase md:hidden"><?= e(t('home.hero.scroll')) ?><?= arrow('rotate-90') ?></span>
      <?= arrow('hidden rotate-90 md:block') ?>
    </div>
  </a>
</section>
