<?php
use Adepc\Admin\A;
use Adepc\Admin\Form;

/** @var array $user @var array $errors */
$err = static fn ($k) => isset($errors[$k]) ? '<p class="mt-1.5 text-small font-semibold text-ember">' . e(at($errors[$k])) . '</p>' : '';
?>
<form method="post" action="/admin/account" class="max-w-2xl space-y-5 bg-white p-5 md:p-8">
  <?= csrf_field() ?>
  <div><label class="caption mb-2 block text-stone" for="a-name"><?= e(at('admin.f.name')) ?></label><input id="a-name" name="name" value="<?= e($user['name']) ?>" required class="<?= e(Form::INPUT) ?>"><?= $err('name') ?></div>
  <div><p class="caption mb-2 text-stone"><?= e(at('admin.f.email')) ?></p><p><?= e($user['email']) ?></p></div>
  <div><label class="caption mb-2 block text-stone" for="a-lang"><?= e(at('admin.f.uiLanguage')) ?></label><select id="a-lang" name="ui_locale" class="<?= e(Form::INPUT) ?> pr-9"><?php foreach (A::LOCALES as $l): ?><option value="<?= $l ?>"<?= $user['ui_locale'] === $l ? ' selected' : '' ?>><?= e(strtoupper($l)) ?></option><?php endforeach ?></select></div>
  <fieldset class="space-y-4 border-t border-ink/10 pt-5">
    <legend class="display text-h3"><?= e(at('admin.account.password')) ?></legend>
    <div><label class="caption mb-2 block text-stone" for="a-current"><?= e(at('admin.f.currentPassword')) ?></label><input id="a-current" name="current" type="password" autocomplete="current-password" class="<?= e(Form::INPUT) ?>"><?= $err('current') ?></div>
    <div><label class="caption mb-2 block text-stone" for="a-new"><?= e(at('admin.f.newPassword')) ?></label><input id="a-new" name="password" type="password" autocomplete="new-password" class="<?= e(Form::INPUT) ?>"><p class="mt-1.5 text-small text-stone"><?= e(at('admin.h.password')) ?></p><?= $err('password') ?></div>
  </fieldset>
  <button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.action.save')) ?></span></button>
</form>
