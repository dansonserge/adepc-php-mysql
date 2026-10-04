<?php
use Adepc\Admin\A;
use Adepc\Admin\Controllers\Pages;
use Adepc\Admin\Form;
use Adepc\Admin\Schema;

/** @var string $key @var ?array $row @var array $sections @var array $translations @var array $lists @var array $slots @var array $settings @var array $errors */
$tErrors = $errors['t'] ?? [];
$pErrors = $errors['page'] ?? [];
$lErrors = $errors['lists'] ?? [];
$sErrors = $errors['s'] ?? [];
$posted = $posted ?? null;
?>
<div class="mb-8 flex flex-wrap gap-4 text-small">
  <?php foreach (A::LOCALES as $l): if ($url = Pages::publicUrl($key, $l)): ?><a href="<?= e($url) ?>" target="_blank" rel="noopener" class="font-semibold text-blue underline-offset-4 hover:underline"><?= e(at('admin.pages.viewIn', ['lang' => strtoupper($l)])) ?> ↗</a><?php endif; endforeach ?>
</div>
<form method="post" action="/admin/pages/<?= e($key) ?>" class="space-y-6">
  <?= csrf_field() ?>
  <?php if ($row): ?>
  <details class="bg-white p-5"<?= $pErrors ? ' open' : '' ?>>
    <summary class="cursor-pointer display text-h3"><?= e(at('admin.pages.urlSeo')) ?></summary>
    <div class="mt-5 grid gap-5 md:grid-cols-2">
      <?php if ($key !== 'home'): foreach (A::LOCALES as $l): ?>
      <div><label class="caption mb-2 block text-stone" for="slug-<?= $l ?>"><?= e(at('admin.f.slugIn', ['lang' => strtoupper($l)])) ?></label><input id="slug-<?= $l ?>" name="page[slug_<?= $l ?>]" value="<?= e($posted['page']['slug_' . $l] ?? $row['slug_' . $l]) ?>" class="<?= e(Form::INPUT) ?>"<?= isset($pErrors['slug_' . $l]) ? ' aria-invalid="true"' : '' ?>><?php if (isset($pErrors['slug_' . $l])): ?><p class="mt-1.5 text-small font-semibold text-ember"><?= e(at($pErrors['slug_' . $l])) ?></p><?php endif ?></div>
      <?php endforeach; endif ?>
      <?php foreach (['title', 'description'] as $m): $tk = 'meta.' . $key . '.' . $m; if (!isset($translations[$tk])) continue; foreach (A::LOCALES as $l): ?>
      <div><label class="caption mb-2 block text-stone"><?= e(at('admin.f.meta' . ucfirst($m))) ?> · <?= e(strtoupper($l)) ?></label><textarea name="t[<?= $l ?>][<?= e($tk) ?>]" rows="2" class="<?= e(Form::INPUT) ?>"><?= e($translations[$tk][$l] ?? '') ?></textarea></div>
      <?php endforeach; endforeach ?>
      <label class="inline-flex items-center gap-3 text-body"><input type="checkbox" name="page[in_sitemap]" value="1" class="check h-5 w-5 border-2 border-ink"<?= $row['in_sitemap'] ? ' checked' : '' ?>><?= e(at('admin.f.inSitemap')) ?></label>
      <div class="grid grid-cols-2 gap-3">
        <label><span class="caption mb-2 block text-stone"><?= e(at('admin.f.changefreq')) ?></span><select name="page[changefreq]" class="<?= e(Form::INPUT) ?> pr-9"><?php foreach (['daily', 'weekly', 'monthly', 'yearly'] as $c): ?><option value="<?= $c ?>"<?= $row['changefreq'] === $c ? ' selected' : '' ?>><?= e(at('admin.o.' . $c)) ?></option><?php endforeach ?></select></label>
        <label><span class="caption mb-2 block text-stone"><?= e(at('admin.f.priority')) ?></span><input name="page[priority]" type="number" min="0" max="1" step="0.1" value="<?= e((string) (float) $row['priority']) ?>" class="<?= e(Form::INPUT) ?>"></label>
      </div>
    </div>
  </details>
  <?php endif ?>
  <?php foreach ($sections as $i => $s): $hasErr = array_intersect_key($tErrors, array_flip($s['keys'])) || array_intersect_key($lErrors, array_flip($s['lists'])); ?>
  <details class="bg-white p-5"<?= $hasErr || $i === 0 ? ' open' : '' ?>>
    <summary class="cursor-pointer display text-h3"><span class="caption mr-3 align-middle text-stone tabular-nums"><?= e(pad2($i + 1)) ?></span><?= e(at($s['label'])) ?></summary>
    <?php if ($s['keys']): ?>
    <div class="mt-5 space-y-5">
      <?php foreach ($s['keys'] as $tk): ?>
      <div<?= isset($tErrors[$tk]) ? ' class="border-l-4 border-ember pl-3"' : '' ?>>
        <p class="mb-2 font-mono text-caption text-stone"><?= e($tk) ?></p>
        <div class="grid gap-3 md:grid-cols-2">
          <?php foreach (A::LOCALES as $l): $v = $translations[$tk][$l] ?? ''; ?>
          <label class="block"><span class="mb-1 block text-caption font-semibold tracking-[0.08em] text-stone uppercase"><?= e(strtoupper($l)) ?></span><textarea name="t[<?= $l ?>][<?= e($tk) ?>]" rows="<?= max(1, min(8, (int) ceil(mb_strlen($v) / 60))) ?>" lang="<?= $l ?>" class="<?= e(Form::INPUT) ?>"><?= e($v) ?></textarea></label>
          <?php endforeach ?>
        </div>
        <?php if (isset($tErrors[$tk])): ?><p class="mt-1.5 text-small font-semibold text-ember"><?= e(at($tErrors[$tk])) ?></p><?php endif ?>
      </div>
      <?php endforeach ?>
    </div>
    <?php endif ?>
    <?php foreach ($s['settings'] as $sk): $f = Schema::settingField($sk); if (!$f) continue; ?>
    <div class="mt-5"><?= Form::render($f, static fn ($c) => $posted['s'][$c] ?? $settings[$c] ?? null, 's', $sErrors, '.') ?></div>
    <?php endforeach ?>
    <?php foreach ($s['lists'] as $lk): ?>
    <?= partial_admin('partials/list-editor', ['key' => $lk, 'items' => $lists[$lk] ?? [], 'errors' => $lErrors[$lk] ?? []]) ?>
    <?php endforeach ?>
    <?php if ($s['slots']): ?>
    <div class="mt-6 grid gap-4 lg:grid-cols-2"><?php foreach ($s['slots'] as $sl): ?><?= partial_admin('partials/slot', ['key' => $sl, 'slots' => $slots]) ?><?php endforeach ?></div>
    <?php endif ?>
  </details>
  <?php endforeach ?>
  <div class="sticky bottom-0 -mx-5 border-t border-ink/10 bg-paper/95 px-5 py-4 backdrop-blur md:-mx-8 md:px-8">
    <button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.action.save')) ?></span></button>
  </div>
</form>
