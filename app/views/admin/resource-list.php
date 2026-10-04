<?php
use Adepc\Admin\A;

/** @var string $key @var array $res @var array $rows */
$cell = static function (array $row, string $col): string {
    $v = $row[$col] ?? '';
    if (in_array($col, ['published', 'visible'], true)) {
        return $v ? '<span class="text-small font-semibold text-blue">' . e(at('admin.yes')) . '</span>' : '<span class="text-small text-stone">' . e(at('admin.no')) . '</span>';
    }
    if ($col === 'kind') {
        return e(at('admin.o.' . $v));
    }
    if ($col === 'category_id') {
        $c = \Adepc\Db::one('SELECT name_fr, name_en FROM video_categories WHERE id = ?', [$v]);
        return e($c['name_' . A::$locale] ?? '');
    }
    return e((string) $v);
};
$n = count($rows);
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
  <p class="text-small text-stone"><?= e(at('admin.list.count', ['count' => $n])) ?></p>
  <a href="/admin/<?= e($key) ?>/new" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.action.newThing', ['thing' => at($res['singular'])])) ?></span></a>
</div>
<div class="overflow-x-auto bg-white">
  <table class="w-full border-collapse text-left">
    <thead><tr class="caption text-stone"><?php foreach ($res['columns'] as $col): ?><th scope="col" class="border-b border-ink/10 px-4 py-3 font-semibold"><?= e(at('admin.col.' . $col)) ?></th><?php endforeach ?><th class="border-b border-ink/10 px-4 py-3"><span class="sr-only"><?= e(at('admin.col.actions')) ?></span></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $i => $row): ?>
      <tr class="border-b border-ink/10 align-middle">
        <?php foreach ($res['columns'] as $j => $col): ?><td class="px-4 py-3"><?= $j === 0 ? '<a href="/admin/' . e($key) . '/' . (int) $row['id'] . '" class="font-semibold hover:text-blue">' . $cell($row, $col) . '</a>' : $cell($row, $col) ?></td><?php endforeach ?>
        <td class="px-4 py-3">
          <div class="flex items-center justify-end gap-2">
            <?php if ($res['sortable']): ?>
            <form method="post" action="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>/up"><?= csrf_field() ?><button type="submit" class="border border-ink/20 px-2 py-1 text-small disabled:opacity-30" aria-label="<?= e(at('admin.action.moveUp')) ?>"<?= $i === 0 ? ' disabled' : '' ?>>↑</button></form>
            <form method="post" action="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>/down"><?= csrf_field() ?><button type="submit" class="border border-ink/20 px-2 py-1 text-small disabled:opacity-30" aria-label="<?= e(at('admin.action.moveDown')) ?>"<?= $i === $n - 1 ? ' disabled' : '' ?>>↓</button></form>
            <?php endif ?>
            <a href="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>" class="px-2 text-small font-semibold text-blue underline-offset-4 hover:underline"><?= e(at('admin.action.edit')) ?></a>
          </div>
        </td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
