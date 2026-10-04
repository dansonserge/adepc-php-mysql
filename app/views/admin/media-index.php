<?php
use Adepc\Admin\A;
use Adepc\Admin\Controllers\Media;
use Adepc\Admin\Form;

/** @var array $rows @var string $kind @var string $q @var int $limit */
?>
<form method="post" action="/admin/media/upload" enctype="multipart/form-data" class="border-2 border-dashed border-ink/30 bg-white p-6" data-dropzone>
  <?= csrf_field() ?>
  <p class="display text-h3"><?= e(at('admin.media.uploadTitle')) ?></p>
  <p class="mt-2 text-small text-stone"><?= e(at('admin.media.uploadLead', ['size' => Media::human($limit)])) ?></p>
  <div class="mt-4 flex flex-wrap items-center gap-4">
    <input type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" class="text-small" required>
    <button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.media.upload')) ?></span></button>
  </div>
</form>
<div class="mt-8 flex flex-wrap items-center justify-between gap-4">
  <nav class="flex gap-2" aria-label="<?= e(at('admin.media.kind')) ?>">
    <?php foreach (['image', 'video'] as $k): ?><a href="/admin/media?kind=<?= $k ?>" class="border border-ink px-4 py-2 text-nav font-semibold uppercase <?= $k === $kind ? 'bg-ink text-white' : '' ?>"<?= $k === $kind ? ' aria-current="page"' : '' ?>><?= e(at('admin.media.' . $k . 's')) ?></a><?php endforeach ?>
  </nav>
  <form method="get" action="/admin/media" class="flex gap-2"><input type="hidden" name="kind" value="<?= e($kind) ?>"><input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(at('admin.search')) ?>" class="<?= e(Form::INPUT) ?> w-64"><button class="<?= e(abtn('outline-dark')) ?>"><span><?= e(at('admin.search')) ?></span></button></form>
</div>
<ul class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6">
  <?php foreach ($rows as $m): ?>
  <li class="bg-white">
    <a href="/admin/media/<?= (int) $m['id'] ?>" class="group block">
      <div class="aspect-[4/3] overflow-hidden bg-ink/5"><?= Form::thumb($m, 'h-full w-full object-cover transition-transform group-hover:scale-105') ?></div>
      <div class="p-3">
        <p class="truncate text-small font-semibold">#<?= (int) $m['id'] ?> · <?= e($m['label'] ?: basename($m['path'])) ?></p>
        <p class="mt-1 line-clamp-2 text-caption text-stone"><?= e($m['alt_' . A::$locale] ?: ($m['kind'] === 'image' ? at('admin.media.noAlt') : '')) ?></p>
      </div>
    </a>
  </li>
  <?php endforeach ?>
</ul>
