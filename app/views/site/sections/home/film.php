<?php
use Adepc\Site;

/** @var array $film */
$posterMedia = Site::slot('home.film.poster');
?>
<section id="film" aria-labelledby="film-title" class="bg-ink text-white">
  <div class="edge flex flex-col gap-6 pt-(--section-y) pb-10 md:flex-row md:items-end md:justify-between">
    <?= fit_text([t('home.film.title')], 'h2', 'leading-[0.85]', '12rem', 0.52, false, 'film-title') ?>
    <p class="caption shrink-0 text-fog md:pb-3"><?= e(t('home.film.eyebrow')) ?></p>
  </div>
  <?= partial('components/film-player', [
      'source' => $film['source'],
      'title' => L($film, 'title'),
      'class' => 'aspect-[4/5] w-full sm:aspect-video sm:max-h-[90svh]',
      'poster' => photo($posterMedia, ['sizes' => '100vw', 'class' => 'absolute inset-0', 'decorative' => true]),
      'children' => '<div class="pointer-events-none absolute inset-0 bg-linear-to-t from-ink/80 via-transparent to-ink/30"></div>'
          . '<div class="pointer-events-none absolute inset-x-0 bottom-0 edge flex items-end justify-between gap-6 pb-8"><p class="max-w-[34ch] text-lead">' . e(t('home.film.caption')) . '</p></div>',
  ]) ?>
</section>
