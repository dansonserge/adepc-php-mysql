<?php
use Adepc\Admin\Controllers\Menus;
use Adepc\Admin\Form;

/** @var array $items @var array $errors @var array $icons */
$row = static function (string $menu, array $item, string $id) use ($errors, $icons): string {
    $p = 'menu[' . $menu . '][' . $id . ']';
    $o = '<li class="grid items-end gap-3 border border-ink/15 bg-white p-3 md:grid-cols-[auto_10rem_1fr_1fr_8rem_auto]' . (isset($errors[$menu . '.' . $id]) ? ' border-ember' : '') . '" data-list-row>';
    $o .= '<div class="flex gap-1 pb-1"><button type="button" class="border border-ink/20 px-2 py-1 text-small" data-move="up" aria-label="' . e(at('admin.action.moveUp')) . '">↑</button><button type="button" class="border border-ink/20 px-2 py-1 text-small" data-move="down" aria-label="' . e(at('admin.action.moveDown')) . '">↓</button></div>';
    $o .= '<label><span class="mb-1 block text-caption text-stone">' . e(at('admin.f.page')) . '</span><select name="' . e($p) . '[page_key]" class="' . Form::INPUT . ' pr-9\">';
    foreach (Menus::PAGES as $pg) {
        $o .= '<option value="' . $pg . '"' . (($item['page_key'] ?? '') === $pg ? ' selected' : '') . '>' . e(at('admin.page.' . $pg)) . '</option>';
    }
    $o .= '</select></label>';
    foreach (['fr', 'en'] as $l) {
        $o .= '<label><span class="mb-1 block text-caption text-stone">' . e(at('admin.f.label')) . ' · ' . strtoupper($l) . '</span><input name="' . e($p) . '[label_' . $l . ']" value="' . e((string) ($item['label_' . $l] ?? '')) . '" class="' . Form::INPUT . '"></label>';
    }
    $o .= '<label><span class="mb-1 block text-caption text-stone">' . e(at('admin.f.icon')) . '</span><select name="' . e($p) . '[icon]" class="' . Form::INPUT . ' pr-9\"><option value="">' . e(at('admin.o.none')) . '</option>';
    foreach ($icons as $ic) {
        $o .= '<option value="' . e($ic) . '"' . (($item['icon'] ?? '') === $ic ? ' selected' : '') . '>' . e(at('admin.icon.' . $ic)) . '</option>';
    }
    $o .= '</select></label><div class="flex flex-col gap-1 pb-1 text-small">'
        . '<label class="inline-flex items-center gap-2"><input type="checkbox" name="' . e($p) . '[visible]" value="1" class="check h-4 w-4 border-2 border-ink"' . (($item['visible'] ?? 1) ? ' checked' : '') . '>' . e(at('admin.f.visible')) . '</label>'
        . '<label class="inline-flex items-center gap-2"><input type="checkbox" name="' . e($p) . '[highlight]" value="1" class="check h-4 w-4 border-2 border-ink"' . (!empty($item['highlight']) ? ' checked' : '') . '>' . e(at('admin.f.highlight')) . '</label>'
        . '<label class="inline-flex items-center gap-2 text-ember"><input type="checkbox" name="' . e($p) . '[_delete]" value="1" class="check h-4 w-4 border-2 border-ember">' . e(at('admin.action.remove')) . '</label></div>';
    return $o . '</li>';
};
?>
<p class="max-w-[70ch] text-body text-stone"><?= e(at('admin.menus.lead')) ?></p>
<form method="post" action="/admin/menus" class="mt-8 space-y-8">
  <?= csrf_field() ?>
  <?php foreach (Menus::MENUS as $menu): ?>
  <section class="bg-paper" data-list>
    <h2 class="display text-h3"><?= e(at('admin.menu.' . $menu)) ?></h2>
    <p class="mt-1 text-small text-stone"><?= e(at('admin.menu.' . $menu . 'Help')) ?></p>
    <ol class="mt-4 space-y-2" data-list-items><?php foreach ($items[$menu] ?? [] as $item): ?><?= $row($menu, $item, (string) $item['id']) ?><?php endforeach ?></ol>
    <template data-list-template><?= $row($menu, ['visible' => 1], '__NEW__') ?></template>
    <button type="button" class="mt-3 border border-dashed border-ink/40 px-4 py-2 text-small font-semibold" data-list-add><?= e(at('admin.action.addItem')) ?></button>
  </section>
  <?php endforeach ?>
  <div class="sticky bottom-0 -mx-5 border-t border-ink/10 bg-paper/95 px-5 py-4 backdrop-blur md:-mx-8 md:px-8"><button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.action.save')) ?></span></button></div>
</form>
