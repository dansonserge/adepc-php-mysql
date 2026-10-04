<?php
use Adepc\Admin\Form;

/** @var array $row @var array $usage @var array $errors @var array $churches */
$isImage = $row['kind'] === 'image';
$err = static fn ($k) => isset($errors[$k]) ? '<p class="mt-1.5 text-small font-semibold text-ember">' . e(at($errors[$k])) . '</p>' : '';
?>
<div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_28rem]">
  <div>
    <?php if ($isImage): ?>
    <div class="relative inline-block max-w-full cursor-crosshair bg-ink/5" data-focus-picker>
      <img src="<?= e(Form::thumbUrl($row) ? str_replace('/256.webp', '/' . min(1200, (int) $row['width']) . '.webp', Form::thumbUrl($row)) : '/media/' . $row['path']) ?>" alt="" class="block max-h-[70svh] w-auto max-w-full">
      <span class="pointer-events-none absolute h-6 w-6 -translate-x-1/2 -translate-y-1/2 border-2 border-white bg-gold/70 shadow" data-focus-dot></span>
    </div>
    <p class="mt-2 text-small text-stone"><?= e(at('admin.media.focusHelp')) ?></p>
    <p class="mt-1 text-caption text-stone"><?= e(at('admin.media.dimensions', ['w' => (int) $row['width'], 'h' => (int) $row['height'], 'mime' => $row['mime']])) ?></p>
    <?php else: ?>
    <video src="/media/<?= e($row['path']) ?>" controls class="max-h-[70svh] w-full bg-black"></video>
    <?php endif ?>
    <section class="mt-8 bg-white p-5">
      <h2 class="display text-h3"><?= e(at('admin.media.usage')) ?></h2>
      <?php if ($usage): ?><ul class="mt-3 space-y-1 text-small"><?php foreach ($usage as $u): ?><li><a href="<?= e($u['link']) ?>" class="underline-offset-4 hover:underline"><?= e($u['label']) ?></a></li><?php endforeach ?></ul>
      <?php else: ?><p class="mt-3 text-small text-stone"><?= e(at('admin.media.unused')) ?></p><?php endif ?>
    </section>
  </div>
  <div class="space-y-6">
    <form method="post" action="/admin/media/<?= (int) $row['id'] ?>" class="space-y-5 bg-white p-5">
      <?= csrf_field() ?>
      <div><label class="caption mb-2 block text-stone" for="m-label"><?= e(at('admin.f.label')) ?></label><input id="m-label" name="f[label]" value="<?= e((string) $row['label']) ?>" class="<?= e(Form::INPUT) ?>"></div>
      <?php if ($isImage): ?>
      <?php foreach (['fr', 'en'] as $l): ?>
      <div><label class="caption mb-2 block text-stone" for="m-alt-<?= $l ?>"><?= e(at('admin.f.alt')) ?> · <?= strtoupper($l) ?></label><textarea id="m-alt-<?= $l ?>" name="f[alt_<?= $l ?>]" rows="3" class="<?= e(Form::INPUT) ?>"<?= isset($errors['alt_' . $l]) ? ' aria-invalid="true"' : '' ?>><?= e((string) $row['alt_' . $l]) ?></textarea><?= $err('alt_' . $l) ?></div>
      <?php endforeach ?>
      <p class="text-small text-stone"><?= e(at('admin.h.alt')) ?></p>
      <div><label class="caption mb-2 block text-stone" for="m-focus"><?= e(at('admin.f.focus')) ?></label><input id="m-focus" name="f[focus]" value="<?= e((string) $row['focus']) ?>" placeholder="50% 50%" class="<?= e(Form::INPUT) ?>" data-focus-input></div>
      <div><label class="caption mb-2 block text-stone" for="m-church"><?= e(at('admin.f.church')) ?></label><select id="m-church" name="f[church_id]" class="<?= e(Form::INPUT) ?> pr-9"><option value=""><?= e(at('admin.o.none')) ?></option><?php foreach ($churches as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $row['church_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach ?></select></div>
      <div><label class="caption mb-2 block text-stone" for="m-tags"><?= e(at('admin.f.tags')) ?></label><input id="m-tags" name="f[tags]" value="<?= e((string) $row['tags']) ?>" class="<?= e(Form::INPUT) ?>"></div>
      <label class="flex items-center gap-3 text-body"><input type="checkbox" name="f[in_gallery]" value="1" class="check h-5 w-5 border-2 border-ink"<?= $row['in_gallery'] ? ' checked' : '' ?>><?= e(at('admin.f.inGallery')) ?></label>
      <label class="flex items-center gap-3 text-body"><input type="checkbox" name="f[in_church_moments]" value="1" class="check h-5 w-5 border-2 border-ink"<?= $row['in_church_moments'] ? ' checked' : '' ?>><?= e(at('admin.f.inMoments')) ?></label>
      <?php endif ?>
      <button type="submit" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.action.save')) ?></span></button>
    </form>
    <form method="post" action="/admin/media/<?= (int) $row['id'] ?>/replace" enctype="multipart/form-data" class="space-y-3 bg-white p-5">
      <?= csrf_field() ?>
      <p class="caption text-stone"><?= e(at('admin.media.replace')) ?></p>
      <p class="text-small text-stone"><?= e(at('admin.media.replaceHelp')) ?></p>
      <input type="file" name="file" required accept="<?= $isImage ? 'image/jpeg,image/png,image/webp' : 'video/mp4,video/webm' ?>" class="text-small">
      <button type="submit" class="<?= e(abtn('outline-dark')) ?>"><span><?= e(at('admin.media.replaceSubmit')) ?></span></button>
    </form>
    <form method="post" action="/admin/media/<?= (int) $row['id'] ?>/delete" data-confirm="<?= e(at('admin.confirm.delete')) ?>">
      <?= csrf_field() ?>
      <button type="submit" class="text-small font-semibold text-ember underline underline-offset-4 disabled:opacity-40"<?= $usage ? ' disabled title="' . e(at('admin.flash.mediaInUse')) . '"' : '' ?>><?= e(at('admin.action.delete')) ?></button>
    </form>
  </div>
</div>
