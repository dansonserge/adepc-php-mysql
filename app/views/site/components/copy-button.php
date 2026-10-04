<?php
/** @var string $value @var string $class */
?>
<button type="button" class="inline-flex min-h-11 items-center border border-current px-4 text-nav font-semibold uppercase transition-colors hover:bg-ink hover:text-gold <?= e($class ?? '') ?>" data-js-copy="<?= e($value) ?>" data-js-copied="<?= e(t('common.copied')) ?>"><span aria-live="polite"><?= e(t('common.copy')) ?></span></button>
