<?php /** @var bool $sent @var bool $mailReady */ ?>
<h1 class="display text-h2"><?= e(at('admin.forgot.title')) ?></h1>
<?php if ($sent): ?>
<p class="mt-6 text-body"><?= e(at('admin.forgot.sent')) ?></p>
<?php else: ?>
<p class="mt-4 text-small text-stone"><?= e(at($mailReady ? 'admin.forgot.lead' : 'admin.forgot.noMail')) ?></p>
<form method="post" action="/admin/forgot" class="mt-6 space-y-5">
  <?= csrf_field() ?>
  <div><label for="email" class="caption mb-2 block text-stone"><?= e(at('admin.f.email')) ?></label><input id="email" name="email" type="email" required class="<?= e(\Adepc\Admin\Form::INPUT) ?>"></div>
  <button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.forgot.submit')) ?></span></button>
</form>
<?php endif ?>
<p class="mt-6"><a href="/admin/login" class="text-small underline underline-offset-4"><?= e(at('admin.forgot.back')) ?></a></p>
