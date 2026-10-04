<?php /** @var array $keys */ ?>
<p class="max-w-[70ch] text-body text-stone"><?= e(at('admin.icons.lead')) ?></p>
<ul class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
  <?php foreach ($keys as $k): ?>
  <li class="flex flex-col gap-4 bg-white p-5">
    <div class="flex items-center gap-4">
      <span class="flex h-14 w-14 items-center justify-center bg-ink text-white"><?= in_array($k, ['check', 'chevron'], true) ? '<img src="/media/icons/' . e($k) . '.svg?v=' . filemtime(PUBLIC_DIR . '/media/icons/' . $k . '.svg') . '" alt="" class="h-6 w-6">' : icon($k, 'h-6 w-6') ?></span>
      <div><p class="font-semibold"><?= e(at('admin.icon.' . $k)) ?></p><p class="font-mono text-caption text-stone"><?= e(basename(PUBLIC_DIR . '/media/icons/' . $k . '.svg')) ?></p></div>
    </div>
    <form method="post" action="/admin/icons/<?= e($k) ?>" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
      <?= csrf_field() ?>
      <input type="file" name="svg" accept="image/svg+xml,.svg" class="min-w-0 flex-1 text-small">
      <button type="submit" class="border border-ink px-3 py-2 text-small font-semibold"><?= e(at('admin.action.upload')) ?></button>
      <button type="submit" name="reset" value="1" class="text-small underline underline-offset-4"><?= e(at('admin.action.resetDefault')) ?></button>
    </form>
  </li>
  <?php endforeach ?>
</ul>
