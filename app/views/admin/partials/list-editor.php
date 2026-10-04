<?php
use Adepc\Admin\Form;
use Adepc\Admin\Schema;

/** @var string $key @var array $items @var array $errors */
$def = Schema::lists()[$key];
$errors = $errors ?? [];
$row = static function (array $item, string $id) use ($key, $def, $errors): string {
    $prefix = 'lists[' . $key . '][' . $id . ']';
    $get = static fn ($c) => $item[$c] ?? null;
    $html = '<li class="border border-ink/15 bg-white p-4" data-list-row>';
    $html .= '<div class="mb-3 flex flex-wrap items-center justify-between gap-3"><div class="flex items-center gap-2">'
        . '<button type="button" class="border border-ink/20 px-2 py-1 text-small" data-move="up" aria-label="' . e(at('admin.action.moveUp')) . '">↑</button>'
        . '<button type="button" class="border border-ink/20 px-2 py-1 text-small" data-move="down" aria-label="' . e(at('admin.action.moveDown')) . '">↓</button></div>'
        . '<div class="flex items-center gap-5"><label class="inline-flex items-center gap-2 text-small"><input type="checkbox" name="' . e($prefix) . '[visible]" value="1" class="check h-5 w-5 border-2 border-ink"' . (($item['visible'] ?? 1) ? ' checked' : '') . '>' . e(at('admin.f.visible')) . '</label>'
        . '<label class="inline-flex items-center gap-2 text-small text-ember"><input type="checkbox" name="' . e($prefix) . '[_delete]" value="1" class="check h-5 w-5 border-2 border-ember">' . e(at('admin.action.remove')) . '</label></div></div>';
    $html .= '<div class="grid gap-4">';
    foreach ($def['fields'] as $f) {
        $html .= Form::render($f, $get, $prefix, $errors[$id] ?? []);
    }
    return $html . '</div></li>';
};
?>
<div class="mt-6" data-list>
  <p class="caption mb-3 text-stone"><?= e(at($def['label'])) ?></p>
  <ol class="space-y-3" data-list-items>
    <?php foreach ($items as $item): ?><?= $row($item, (string) $item['id']) ?><?php endforeach ?>
  </ol>
  <template data-list-template><?= $row(['visible' => 1], '__NEW__') ?></template>
  <button type="button" class="mt-3 border border-dashed border-ink/40 px-4 py-2 text-small font-semibold" data-list-add><?= e(at('admin.action.addItem')) ?></button>
</div>
