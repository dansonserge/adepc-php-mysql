<?php
use Adepc\Img;
use Adepc\Site;

/**
 * Editorial list: huge date (or rhythm) on the left, title, then where and when.
 * @var array $events @var array $churches @var bool $detailed
 */
$detailed = $detailed ?? false;
?>
<ol class="rule border-t">
  <?php foreach ($events as $event):
      $featured = $event['kind'] === 'featured';
      $media = Site::media($event['media_id']);
      $people = array_values(array_filter(array_map('trim', explode("\n", (string) $event['people'])), 'strlen')); ?>
  <li class="group rule relative border-b" data-reveal="up">
    <article class="grid-12 gap-y-5 py-8 md:py-12">
      <div class="col-span-4 md:col-span-2 lg:col-span-3">
        <?= partial('components/event-date', ['event' => $event]) ?>
        <?php if ($featured): ?><p class="caption mt-4 text-blue"><?= e(t('events.featured')) ?></p><?php endif ?>
      </div>
      <div class="col-span-4 md:col-span-4 lg:col-span-5">
        <h3 id="event-<?= (int) $event['id'] ?>-title" class="display leading-[0.95] <?= $featured ? 'text-h1' : 'text-h2' ?>"><?= e(L($event, 'title')) ?></h3>
        <p class="mt-4 max-w-[52ch] text-stone <?= $detailed ? 'text-body' : 'text-small' ?>"><?= e(L($event, 'description')) ?></p>
        <?php if ($people): ?>
        <p class="mt-4 text-small"><span class="caption mr-2 text-stone"><?= e(t('common.with')) ?></span><?= e(implode(t('format.separator'), $people)) ?></p>
        <?php endif ?>
        <?= arrow_link(url('event', ['slug' => $event['slug']]), t('events.details'), 'mt-6', ['aria-describedby' => 'event-' . (int) $event['id'] . '-title']) ?>
      </div>
      <?= partial('components/event-facts', ['event' => $event, 'churches' => $churches, 'detailed' => $detailed, 'class' => 'col-span-4 space-y-3 text-small md:col-span-2 lg:col-span-3 lg:col-start-10']) ?>
      <?php if ($media): ?>
      <div class="relative col-span-4 aspect-[3/2] overflow-hidden md:hidden"><?= Img::tag($media, ['alt' => L($media, 'alt'), 'fill' => true, 'sizes' => '100vw', 'class' => 'object-cover']) ?></div>
      <?php endif ?>
    </article>
    <?php if ($media): ?>
    <div aria-hidden="true" class="pointer-events-none absolute top-1/2 right-[24%] hidden aspect-[4/3] w-[16vw] -translate-y-1/2 scale-95 overflow-hidden opacity-0 transition duration-500 ease-expo group-hover:scale-100 group-hover:opacity-100 lg:block"><?= Img::tag($media, ['alt' => '', 'fill' => true, 'sizes' => '16vw', 'class' => 'object-cover']) ?></div>
    <?php endif ?>
  </li>
  <?php endforeach ?>
</ol>
