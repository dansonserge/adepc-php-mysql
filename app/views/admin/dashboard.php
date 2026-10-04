<?php
use Adepc\Admin\A;

/** @var array $defaults @var array $hidden @var array $confirm @var array $notes */
$item = static fn (array $i) => '<li class="flex items-start justify-between gap-4 border-b border-ink/10 py-3"><span>' . e($i['label']) . '</span><a href="' . e($i['link']) . '" class="shrink-0 text-small font-semibold text-blue underline-offset-4 hover:underline">' . e(at('admin.dash.fix')) . '</a></li>';
?>
<div class="grid gap-10 xl:grid-cols-2">
  <section class="border-t-4 border-gold bg-white p-6">
    <h2 class="display text-h3"><?= e(at('admin.dash.defaultsTitle')) ?></h2>
    <p class="mt-2 text-small text-stone"><?= e(at('admin.dash.defaultsLead')) ?></p>
    <?php if ($defaults): ?><ul class="mt-4 border-t border-ink/10"><?= implode('', array_map($item, $defaults)) ?></ul><?php else: ?><p class="mt-4 font-semibold"><?= e(at('admin.dash.none')) ?></p><?php endif ?>
  </section>
  <section class="border-t-4 border-blue bg-white p-6">
    <h2 class="display text-h3"><?= e(at('admin.dash.hiddenTitle')) ?></h2>
    <p class="mt-2 text-small text-stone"><?= e(at('admin.dash.hiddenLead')) ?></p>
    <?php if ($hidden): ?><ul class="mt-4 border-t border-ink/10"><?= implode('', array_map($item, $hidden)) ?></ul><?php else: ?><p class="mt-4 font-semibold"><?= e(at('admin.dash.none')) ?></p><?php endif ?>
  </section>
  <?php if ($confirm): ?>
  <section class="border-t-4 border-ember bg-white p-6">
    <h2 class="display text-h3"><?= e(at('admin.dash.confirmTitle')) ?></h2>
    <ul class="mt-4 border-t border-ink/10"><?php foreach ($confirm as $s): ?><li class="border-b border-ink/10 py-3"><a href="/admin/stories/<?= (int) $s['id'] ?>" class="font-semibold underline-offset-4 hover:underline"><?= e($s['name']) ?></a><p class="mt-1 text-small text-stone"><?= e($s['confirm_note']) ?></p></li><?php endforeach ?></ul>
  </section>
  <?php endif ?>
  <section class="bg-white p-6">
    <h2 class="display text-h3"><?= e(at('admin.dash.notesTitle')) ?></h2>
    <p class="mt-2 text-small text-stone"><?= e(at('admin.dash.notesLead')) ?></p>
    <ul class="mt-4 space-y-3"><?php foreach ($notes as $n): ?><li class="border-l-2 <?= $n['kind'] === 'banner' ? 'border-blue' : 'border-stone' ?> pl-3 text-small"><span class="caption mr-2 text-stone"><?= e(at('admin.dash.note.' . $n['kind'])) ?></span><?= e($n['what_' . A::$locale]) ?><?php if ($n['why_' . A::$locale]): ?><span class="mt-1 block text-stone"><?= e($n['why_' . A::$locale]) ?></span><?php endif ?></li><?php endforeach ?></ul>
  </section>
</div>
