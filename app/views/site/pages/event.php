<?php
use Adepc\Img;
use Adepc\Schedule;
use Adepc\Site;

/** @var array $event @var array $churches */
$featured = $event['kind'] === 'featured';
$title = L($event, 'title');
$people = array_values(array_filter(array_map('trim', explode("\n", (string) $event['people'])), 'strlen'));
$items = array_values(array_filter(array_map([Site::class, 'media'], $event['gallery'])));
$when = match (true) {
    $event['date_mode'] === 'date' && (bool) $event['event_date'] => Schedule::dayDate(new DateTimeImmutable($event['event_date'] . ' 12:00:00', Schedule::tz())),
    $event['date_mode'] === 'text' => L($event, 'date_text'),
    default => L($event, 'recurrence'),
};
$lead = implode(t('format.separator'), array_filter([$when, event_where($event)], 'strlen'));
$back = '<a class="group inline-flex items-center gap-3 text-nav font-semibold uppercase hover:text-gold" href="' . e(url('events')) . '">' . arrow('rotate-180') . e(t('events.back')) . '</a>';
$backBottom = '<div class="edge mt-12 md:mt-16">' . arrow_link(url('events'), t('events.back')) . '</div>';
?>
<?= partial('components/page-hero', ['eyebrow' => $featured ? t('events.featured') : t('events.hero.eyebrow'), 'title' => $title, 'titleClass' => 'text-display-m', 'lead' => $lead, 'photo' => Site::media($event['media_id']), 'children' => $back]) ?>
<section class="section-y bg-paper text-ink">
  <div class="edge grid-12 gap-y-12">
    <div class="col-span-4 md:col-span-5 lg:col-span-7">
      <?= partial('components/event-date', ['event' => $event]) ?>
      <div class="mt-10 max-w-[60ch] space-y-5 text-lead">
        <?php foreach (paragraphs(L($event, 'description')) as $p): ?><p><?= e($p) ?></p><?php endforeach ?>
      </div>
      <?php if ($people): ?>
      <p class="mt-8 text-body"><span class="caption mr-2 text-stone"><?= e(t('common.with')) ?></span><?= e(implode(t('format.separator'), $people)) ?></p>
      <?php endif ?>
    </div>
    <?= partial('components/event-facts', ['event' => $event, 'churches' => $churches, 'detailed' => true, 'class' => 'col-span-4 space-y-5 text-body md:col-span-3 lg:col-span-4 lg:col-start-9']) ?>
  </div>
  <?php if (!$items): ?><?= $backBottom ?><?php endif ?>
</section>
<?php if ($items): ?>
<section aria-labelledby="gallery-title" class="bg-cream py-(--section-y) text-ink">
  <h2 id="gallery-title" class="display edge mb-10 text-display-m"><?= e(t('events.gallery')) ?></h2>
  <div data-js-lightbox>
    <ul class="grid grid-cols-2 gap-1 md:grid-cols-3">
      <?php foreach ($items as $m): ?>
      <?php if ($m['kind'] === 'image'): [, $full] = Img::sources($m, null); ?>
      <li><a href="<?= e($full) ?>" class="group relative block aspect-[4/3] overflow-hidden bg-ink/10" data-js-lightbox-item><?= Img::tag($m, [
          'alt' => L($m, 'alt'),
          'fill' => true,
          'blur' => true,
          'sizes' => '(min-width: 768px) 33vw, 50vw',
          'position' => $m['focus'] ?: '50% 50%',
          'class' => 'object-cover transition-transform duration-500 ease-expo group-hover:scale-105',
      ]) ?></a></li>
      <?php else: ?>
      <li class="relative aspect-[4/3] bg-ink"><video controls preload="metadata" playsinline class="absolute inset-0 h-full w-full object-contain" aria-label="<?= e($title) ?>"><source src="<?= e(Site::mediaUrl($m)) ?>#t=0.1" type="<?= e($m['mime']) ?>"></video></li>
      <?php endif ?>
      <?php endforeach ?>
    </ul>
    <dialog aria-label="<?= e(t('events.gallery')) ?>" class="lightbox m-0 h-dvh max-h-none w-full max-w-none bg-ink p-0 text-white" data-js-lightbox-dialog>
      <div class="flex h-full flex-col">
        <div class="edge flex items-center justify-between py-3">
          <p class="caption tabular-nums" aria-live="polite" data-js-lightbox-count></p>
          <button type="button" aria-label="<?= e(t('events.galleryClose')) ?>" class="flex h-12 w-12 items-center justify-center transition-colors hover:text-gold" data-js-lightbox-close><?= icon('close', 'h-6 w-6') ?></button>
        </div>
        <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-6 md:px-20" data-js-lightbox-stage>
          <img alt="" class="max-h-full max-w-full object-contain" data-js-lightbox-image>
          <button type="button" aria-label="<?= e(t('events.galleryPrev')) ?>" class="group absolute top-1/2 left-2 flex h-12 w-12 -translate-y-1/2 items-center justify-center bg-ink/60 transition-colors hover:text-gold md:left-4" data-js-lightbox-prev><?= icon('arrow', 'h-3 w-5 rotate-180') ?></button>
          <button type="button" aria-label="<?= e(t('events.galleryNext')) ?>" class="group absolute top-1/2 right-2 flex h-12 w-12 -translate-y-1/2 items-center justify-center bg-ink/60 transition-colors hover:text-gold md:right-4" data-js-lightbox-next><?= icon('arrow', 'h-3 w-5') ?></button>
        </div>
      </div>
    </dialog>
  </div>
  <?= $backBottom ?>
</section>
<?php endif ?>
