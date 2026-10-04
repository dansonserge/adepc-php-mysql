<?php
use Adepc\Admin\Form;
use Adepc\Admin\Schema;

/** @var string $key @var array $slots */
[$kind, $label, $focusable] = Schema::slots()[$key];
$current = $slots[$key] ?? ['media_id' => null, 'focus' => null];
?>
<div class="border border-ink/10 bg-paper p-4">
  <p class="caption mb-3 text-stone"><?= e(at($label)) ?></p>
  <?= Form::mediaPicker($kind, 'slots[' . e($key) . '][media]', 'slot-' . preg_replace('/\W+/', '-', $key), $current['media_id'] !== null ? (int) $current['media_id'] : null, true) ?>
  <?php if ($focusable): ?>
  <label class="mt-3 block"><span class="caption mb-1 block text-stone"><?= e(at('admin.f.focusOverride')) ?></span><input name="slots[<?= e($key) ?>][focus]" value="<?= e((string) $current['focus']) ?>" placeholder="50% 50%" class="<?= e(Form::INPUT) ?> max-w-48"></label>
  <?php else: ?><input type="hidden" name="slots[<?= e($key) ?>][focus]" value=""><?php endif ?>
</div>
