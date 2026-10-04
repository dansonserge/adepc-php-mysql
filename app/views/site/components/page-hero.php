<?php
use Adepc\Img;
use Adepc\Site;

/**
 * Every inner page opens on a dark band with one huge statement.
 * @var string $eyebrow @var string $title @var ?string $lead @var ?array $photo
 * @var string $tone ink|blue|night @var string $children (HTML) @var string $titleClass
 */
$tones = ['ink' => 'bg-ink', 'blue' => 'bg-blue', 'night' => 'bg-night'];
$photo = $photo ?? null;
$lead = $lead ?? '';
$children = $children ?? '';
?>
<section class="relative isolate flex flex-col justify-end overflow-hidden text-white <?= e($tones[$tone ?? 'ink']) ?> <?= $photo ? 'min-h-[86svh]' : 'min-h-[60svh]' ?>">
  <?php if ($photo): ?>
  <div aria-hidden="true" class="absolute inset-0 -z-10">
    <?= Img::tag($photo, ['alt' => '', 'fill' => true, 'preload' => true, 'sizes' => '(orientation: portrait) 120vh, 100vw', 'blur' => true, 'class' => 'object-cover', 'position' => $photo['focus'] ?: '50% 50%']) ?>
    <div class="absolute inset-0 bg-linear-to-t from-ink via-ink/50 to-ink/30"></div>
  </div>
  <?php endif ?>
  <div class="edge pt-[calc(var(--header-h)+4rem)] pb-12 md:pb-16">
    <?= eyebrow(e($eyebrow), 'fade-late') ?>
    <h1 class="display rise mt-6 max-w-[16ch] <?= e($titleClass ?? 'text-display-l') ?>"><span class="rise-line"><span><?= e($title) ?></span></span></h1>
    <?php if ($lead !== '' || $children !== ''): ?>
    <div class="fade-late mt-8 grid-12 items-end gap-y-8 md:mt-10">
      <?php if ($lead !== ''): ?><p class="col-span-4 max-w-[46ch] text-lead text-white/85 md:col-span-5"><?= e($lead) ?></p><?php endif ?>
      <?php if ($children !== ''): ?><div class="col-span-4 md:col-span-3 md:justify-self-end lg:col-span-5"><?= $children ?></div><?php endif ?>
    </div>
    <?php endif ?>
  </div>
</section>
