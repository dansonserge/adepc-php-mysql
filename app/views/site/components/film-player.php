<?php
use Adepc\Site;

/**
 * One big PLAY button over a poster; pressing it swaps in the real player.
 * Nothing is downloaded until then.
 * @var array $source ['kind' => file|youtube, ...] @var string $title
 * @var string $poster (HTML) @var string $children (HTML) @var string $class @var string $buttonClass
 */
$player = $source['kind'] === 'youtube'
    ? ['kind' => 'youtube', 'src' => str_replace('{id}', rawurlencode($source['id']), setting('youtube.embed_url'))]
    : ['kind' => 'file', 'mp4' => $source['mp4'], 'webm' => $source['webm'] ?? null];
?>
<div class="relative overflow-hidden bg-ink <?= e($class ?? '') ?>" data-js-film="<?= e(json_encode($player + ['title' => $title], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>">
  <?= $poster ?><?= $children ?? '' ?>
  <button type="button" aria-label="<?= e(t('common.playFilm', ['title' => $title])) ?>" class="group absolute z-10 flex items-center justify-center bg-gold text-ink transition-transform duration-500 ease-expo hover:scale-105 <?= e(($buttonClass ?? '') ?: 'top-1/2 left-1/2 h-24 w-24 -translate-x-1/2 -translate-y-1/2 md:h-32 md:w-32') ?>" data-js-film-play><?= icon('play', 'h-8 w-8 translate-x-0.5 md:h-10 md:w-10') ?></button>
</div>
