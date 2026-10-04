<?php /** @var bool $valid @var string $token @var ?string $error */ ?>
<h1 class="display text-h2"><?= e(at('admin.reset.title')) ?></h1>
<?php if (!$valid): ?>
<p class="mt-6 text-body"><?= e(at('admin.reset.invalid')) ?></p>
<p class="mt-6"><a href="/admin/forgot" class="text-small underline underline-offset-4"><?= e(at('admin.login.forgot')) ?></a></p>
<?php else: ?>
<form method="post" action="/admin/reset" class="mt-6 space-y-5">
  <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
  <div><label for="password" class="caption mb-2 block text-stone"><?= e(at('admin.f.newPassword')) ?></label><input id="password" name="password" type="password" autocomplete="new-password" required minlength="10" class="<?= e(\Adepc\Admin\Form::INPUT) ?>"><p class="mt-1.5 text-small text-stone"><?= e(at('admin.h.password')) ?></p></div>
  <?php if ($error): ?><p role="alert" class="text-small font-semibold text-ember"><?= e(at($error)) ?></p><?php endif ?>
  <button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.reset.submit')) ?></span></button>
</form>
<?php endif ?>
