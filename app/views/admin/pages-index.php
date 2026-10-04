<?php
use Adepc\Admin\Controllers\Pages;

/** @var array $pages */
?>
<p class="max-w-[70ch] text-body text-stone"><?= e(at('admin.pages.lead')) ?></p>
<ul class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
  <?php foreach ($pages as $key => $p): ?>
  <li class="flex flex-col justify-between bg-white p-5">
    <a href="/admin/pages/<?= e($key) ?>" class="display text-h3 hover:text-blue"><?= e(at($p['label'])) ?></a>
    <p class="mt-2 text-small text-stone"><?= e(at('admin.pages.sections', ['count' => count($p['sections'])])) ?></p>
    <?php if ($url = Pages::publicUrl($key, 'fr')): ?><a href="<?= e($url) ?>" target="_blank" rel="noopener" class="mt-4 text-small text-blue underline-offset-4 hover:underline"><?= e($url) ?></a><?php endif ?>
  </li>
  <?php endforeach ?>
</ul>
