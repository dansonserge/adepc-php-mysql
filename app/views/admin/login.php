<?php /** @var ?string $error @var string $email */ ?>
<h1 class="display text-h2"><?= e(at('admin.login.title')) ?></h1>
<form method="post" action="/admin/login" class="mt-6 space-y-5">
  <?= csrf_field() ?>
  <div><label for="email" class="caption mb-2 block text-stone"><?= e(at('admin.f.email')) ?></label><input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>" class="<?= e(\Adepc\Admin\Form::INPUT) ?>"></div>
  <div><label for="password" class="caption mb-2 block text-stone"><?= e(at('admin.f.password')) ?></label><input id="password" name="password" type="password" autocomplete="current-password" required class="<?= e(\Adepc\Admin\Form::INPUT) ?>"></div>
  <?php if ($error): ?><p role="alert" class="text-small font-semibold text-ember"><?= e(at($error)) ?></p><?php endif ?>
  <div class="flex flex-wrap items-center justify-between gap-4">
    <button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.login.submit')) ?></span></button>
    <a href="/admin/forgot" class="text-small underline underline-offset-4"><?= e(at('admin.login.forgot')) ?></a>
  </div>
</form>
