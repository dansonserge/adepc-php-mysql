<?php
use Adepc\Admin\Form;

/** @var array $rows @var array $errors */
?>
<p class="max-w-[70ch] text-body text-stone"><?= e(at('admin.languages.lead')) ?></p>
<form method="post" action="/admin/languages" class="mt-8 max-w-4xl space-y-6">
  <?= csrf_field() ?>
  <?php foreach ($rows as $r): ?>
  <fieldset class="grid gap-4 bg-white p-5 md:grid-cols-2">
    <legend class="display text-h3"><?= e($r['code']) ?></legend>
    <?php foreach (['name', 'short_label', 'hreflang', 'og_locale'] as $col): ?>
    <label><span class="caption mb-2 block text-stone"><?= e(at('admin.f.locale_' . $col)) ?></span><input name="l[<?= e($r['code']) ?>][<?= $col ?>]" value="<?= e($r[$col]) ?>" class="<?= e(Form::INPUT) ?>"<?= isset($errors[$r['code'] . '.' . $col]) ? ' aria-invalid="true"' : '' ?>></label>
    <?php endforeach ?>
    <label class="inline-flex items-center gap-3 text-body"><input type="radio" name="default" value="<?= e($r['code']) ?>"<?= $r['is_default'] ? ' checked' : '' ?>><?= e(at('admin.f.defaultLanguage')) ?></label>
  </fieldset>
  <?php endforeach ?>
  <button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.action.save')) ?></span></button>
</form>
