<?php
use Adepc\Admin\Form;
use Adepc\Site;

/** @var string $key @var array $res @var ?array $row @var array $values @var array $errors */
$get = static fn ($c) => $values[$c] ?? null;
$public = null;
if ($row && $key === 'churches') {
    $public = Site::url('church', 'fr', ['slug' => $row['slug']]);
}
?>
<?php if ($public): ?><p class="mb-6"><a href="<?= e($public) ?>" target="_blank" rel="noopener" class="text-small font-semibold text-blue underline-offset-4 hover:underline"><?= e(at('admin.viewOnSite')) ?> ↗</a></p><?php endif ?>
<form method="post" action="/admin/<?= e($key) ?>/<?= $row ? (int) $row['id'] : 'new' ?>" class="max-w-4xl space-y-6 bg-white p-5 md:p-8">
  <?= csrf_field() ?>
  <?php foreach ($res['fields'] as $f): ?>
  <?php if ($f[1] === 'services'): $services = $values['services'] ?? []; ?>
  <div data-list>
    <p class="caption mb-2 text-stone"><?= e(at($f['label'])) ?></p>
    <?php if (isset($errors['services'])): ?><p class="mb-2 text-small font-semibold text-ember"><?= e(at($errors['services'])) ?></p><?php endif ?>
    <?php $svc = static function (array $s, string $i): string {
        $o = '<li class="grid gap-3 border border-ink/15 p-3 md:grid-cols-[8rem_7rem_1fr_1fr_auto] md:items-end" data-list-row>';
        $o .= '<label><span class="mb-1 block text-caption text-stone">' . e(at('admin.f.day')) . '</span><select name="services[' . $i . '][day]" class="' . Form::INPUT . ' pr-9\">';
        foreach (['sunday', 'friday'] as $d) {
            $o .= '<option value="' . $d . '"' . (($s['day'] ?? '') === $d ? ' selected' : '') . '>' . e(t('common.' . $d)) . '</option>';
        }
        $o .= '</select></label><label><span class="mb-1 block text-caption text-stone">' . e(at('admin.f.time')) . '</span><input type="time" name="services[' . $i . '][time]" value="' . e($s['time'] ?? '') . '" class="' . Form::INPUT . '"></label>';
        foreach (['fr', 'en'] as $l) {
            $o .= '<label><span class="mb-1 block text-caption text-stone">' . e(at('admin.f.label')) . ' · ' . strtoupper($l) . '</span><input name="services[' . $i . '][label_' . $l . ']" value="' . e($s['label_' . $l] ?? '') . '" class="' . Form::INPUT . '"></label>';
        }
        return $o . '<label class="inline-flex items-center gap-2 pb-2 text-small text-ember"><input type="checkbox" name="services[' . $i . '][remove]" value="1" class="check h-5 w-5 border-2 border-ember">' . e(at('admin.action.remove')) . '</label></li>';
    }; ?>
    <ol class="space-y-2" data-list-items><?php foreach ($services as $i => $s): ?><?= $svc($s, (string) $i) ?><?php endforeach ?></ol>
    <template data-list-template><?= $svc([], '__NEW__') ?></template>
    <button type="button" class="mt-3 border border-dashed border-ink/40 px-4 py-2 text-small font-semibold" data-list-add><?= e(at('admin.action.addService')) ?></button>
  </div>
  <?php else: ?>
  <?= Form::render($f, $get, 'f', $errors) ?>
  <?php endif ?>
  <?php endforeach ?>
  <div class="flex flex-wrap items-center gap-4 border-t border-ink/10 pt-6">
    <button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.action.save')) ?></span></button>
    <a href="/admin/<?= e($key) ?>" class="text-small underline underline-offset-4"><?= e(at('admin.action.cancel')) ?></a>
  </div>
</form>
<?php if ($row): ?>
<form method="post" action="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>/delete" class="mt-6 max-w-4xl" data-confirm="<?= e(at('admin.confirm.delete')) ?>">
  <?= csrf_field() ?>
  <button type="submit" class="text-small font-semibold text-ember underline underline-offset-4"><?= e(at('admin.action.delete')) ?></button>
</form>
<?php endif ?>
