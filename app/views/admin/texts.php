<?php
use Adepc\Admin\A;
use Adepc\Admin\Form;

/** @var array $rows @var int $total @var array $groups @var string $group @var string $q @var array $errors @var array $defaults */
?>
<p class="max-w-[70ch] text-body text-stone"><?= e(at('admin.texts.lead')) ?></p>
<form method="get" action="/admin/texts" class="mt-6 flex flex-wrap items-end gap-3">
  <label><span class="caption mb-2 block text-stone"><?= e(at('admin.texts.group')) ?></span><select name="group" class="<?= e(Form::INPUT) ?> pr-9"><option value=""><?= e(at('admin.texts.allGroups')) ?></option><?php foreach ($groups as $g): ?><option value="<?= e($g) ?>"<?= $g === $group ? ' selected' : '' ?>><?= e($g) ?></option><?php endforeach ?></select></label>
  <label class="min-w-64 flex-1"><span class="caption mb-2 block text-stone"><?= e(at('admin.search')) ?></span><input type="search" name="q" value="<?= e($q) ?>" class="<?= e(Form::INPUT) ?>"></label>
  <button class="<?= e(abtn('outline-dark')) ?>"><span><?= e(at('admin.texts.filter')) ?></span></button>
</form>
<p class="mt-4 text-small text-stone"><?= e(at('admin.texts.shown', ['shown' => count($rows), 'total' => $total])) ?></p>
<form method="post" action="/admin/texts" class="mt-4 space-y-3">
  <?= csrf_field() ?><input type="hidden" name="group" value="<?= e($group) ?>"><input type="hidden" name="q" value="<?= e($q) ?>">
  <?php foreach ($rows as $k => $v): $custom = false; foreach (A::LOCALES as $l) { $custom = $custom || (($defaults[$k][$l] ?? null) !== null && $defaults[$k][$l] !== ($v[$l] ?? '')); } ?>
  <div class="bg-white p-4<?= isset($errors[$k]) ? ' border-l-4 border-ember' : '' ?>">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
      <p class="font-mono text-caption text-stone"><?= e($k) ?></p>
      <?php if ($custom): ?><label class="inline-flex items-center gap-2 text-caption text-stone"><input type="checkbox" name="reset[]" value="<?= e($k) ?>" class="check h-4 w-4 border-2 border-ink"><?= e(at('admin.action.resetDefault')) ?></label><?php endif ?>
    </div>
    <div class="grid gap-3 md:grid-cols-2">
      <?php foreach (A::LOCALES as $l): $val = (string) ($v[$l] ?? ''); ?>
      <label class="block"><span class="mb-1 block text-caption font-semibold tracking-[0.08em] text-stone uppercase"><?= e(strtoupper($l)) ?></span><textarea name="t[<?= $l ?>][<?= e($k) ?>]" rows="<?= max(1, min(8, (int) ceil(mb_strlen($val) / 60))) ?>" lang="<?= $l ?>" class="<?= e(Form::INPUT) ?>"><?= e($val) ?></textarea></label>
      <?php endforeach ?>
    </div>
    <?php if (isset($errors[$k])): ?><p class="mt-1.5 text-small font-semibold text-ember"><?= e(at($errors[$k])) ?></p><?php endif ?>
  </div>
  <?php endforeach ?>
  <div class="sticky bottom-0 -mx-5 border-t border-ink/10 bg-paper/95 px-5 py-4 backdrop-blur md:-mx-8 md:px-8"><button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.action.save')) ?></span></button></div>
</form>
