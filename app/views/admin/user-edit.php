<?php
use Adepc\Admin\A;
use Adepc\Admin\Form;

/** @var ?array $row @var array $values @var array $errors */
$err = static fn ($k) => isset($errors[$k]) ? '<p class="mt-1.5 text-small font-semibold text-ember">' . e(at($errors[$k])) . '</p>' : '';
?>
<form method="post" action="/admin/users/<?= $row ? (int) $row['id'] : 'new' ?>" class="max-w-2xl space-y-5 bg-white p-5 md:p-8">
  <?= csrf_field() ?>
  <div><label class="caption mb-2 block text-stone" for="u-name"><?= e(at('admin.f.name')) ?></label><input id="u-name" name="name" value="<?= e($values['name']) ?>" required class="<?= e(Form::INPUT) ?>"><?= $err('name') ?></div>
  <div><label class="caption mb-2 block text-stone" for="u-email"><?= e(at('admin.f.email')) ?></label><input id="u-email" name="email" type="email" value="<?= e($values['email']) ?>" required class="<?= e(Form::INPUT) ?>"><?= $err('email') ?></div>
  <div><label class="caption mb-2 block text-stone" for="u-role"><?= e(at('admin.f.role')) ?></label><select id="u-role" name="role" class="<?= e(Form::INPUT) ?> pr-9"><?php foreach (['editor', 'admin'] as $r): ?><option value="<?= $r ?>"<?= $values['role'] === $r ? ' selected' : '' ?>><?= e(at('admin.role.' . $r)) ?></option><?php endforeach ?></select><p class="mt-1.5 text-small text-stone"><?= e(at('admin.h.role')) ?></p><?= $err('role') ?></div>
  <div><label class="caption mb-2 block text-stone" for="u-lang"><?= e(at('admin.f.uiLanguage')) ?></label><select id="u-lang" name="ui_locale" class="<?= e(Form::INPUT) ?> pr-9"><?php foreach (A::LOCALES as $l): ?><option value="<?= $l ?>"<?= $values['ui_locale'] === $l ? ' selected' : '' ?>><?= e(strtoupper($l)) ?></option><?php endforeach ?></select></div>
  <div><label class="caption mb-2 block text-stone" for="u-pass"><?= e(at($row ? 'admin.f.newPasswordOptional' : 'admin.f.password')) ?></label><input id="u-pass" name="password" type="password" autocomplete="new-password" class="<?= e(Form::INPUT) ?>"<?= $row ? '' : ' required' ?>><p class="mt-1.5 text-small text-stone"><?= e(at('admin.h.password')) ?></p><?= $err('password') ?></div>
  <div class="flex flex-wrap items-center gap-4 border-t border-ink/10 pt-6"><button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.action.save')) ?></span></button><a href="/admin/users" class="text-small underline underline-offset-4"><?= e(at('admin.action.cancel')) ?></a></div>
</form>
<?php if ($row): ?>
<form method="post" action="/admin/users/<?= (int) $row['id'] ?>" class="mt-6" data-confirm="<?= e(at('admin.confirm.delete')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button type="submit" class="text-small font-semibold text-ember underline underline-offset-4"><?= e(at('admin.action.delete')) ?></button></form>
<?php endif ?>
