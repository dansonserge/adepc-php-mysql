<?php
use Adepc\Admin\Form;

/** @var array $groups @var string $group @var array $values @var array $errors */
?>
<nav class="flex flex-wrap gap-2" aria-label="<?= e(at('admin.nav.settings')) ?>">
  <?php foreach ($groups as $g => $def): ?><a href="/admin/settings/<?= e($g) ?>" class="border border-ink px-4 py-2 text-nav font-semibold uppercase <?= $g === $group ? 'bg-ink text-white' : 'hover:bg-ink/5' ?>"<?= $g === $group ? ' aria-current="page"' : '' ?>><?= e(at($def['label'])) ?></a><?php endforeach ?>
</nav>
<form method="post" action="/admin/settings/<?= e($group) ?>" class="mt-8 max-w-4xl space-y-6 bg-white p-5 md:p-8">
  <?= csrf_field() ?>
  <?php foreach ($groups[$group]['fields'] as $f): ?>
  <?= Form::render($f, static fn ($c) => $values[$c] ?? null, 's', $errors, '.') ?>
  <?php endforeach ?>
  <div class="border-t border-ink/10 pt-6"><button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.action.save')) ?></span></button></div>
</form>
<?php if ($group === 'email'): ?>
<form method="post" action="/admin/settings/test-email" class="mt-6 max-w-4xl bg-white p-5 md:p-8">
  <?= csrf_field() ?>
  <p class="text-small text-stone"><?= e(at('admin.mail.testLead')) ?></p>
  <button type="submit" class="mt-4 <?= e(abtn('outline-dark')) ?>"><span><?= e(at('admin.mail.testSubmit')) ?></span></button>
</form>
<?php endif ?>
