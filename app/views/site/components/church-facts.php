<?php
use Adepc\Schedule;

/** Service times first and biggest; then where, and how to reach someone.
 * @var array $church @var bool $showPastor */
$c = $church;
?>
<div>
  <dl class="grid grid-cols-2 gap-px bg-current/15">
    <?php foreach ($c['services'] as $s): $p = Schedule::timeParts($s['time']); ?>
    <div class="bg-[var(--facts-bg,var(--color-paper))] py-4 pr-4">
      <dt class="caption text-current/70"><?= e(t('common.' . $s['day'])) ?></dt>
      <dd class="display mt-2 text-h1 tabular-nums"><?= e($p['main']) ?><?php if ($p['suffix'] !== ''): ?><span class="ml-1 text-h3"><?= e($p['suffix']) ?></span><?php endif ?></dd>
      <dd class="mt-1 text-small text-current/70"><?= e(L($s, 'label')) ?></dd>
    </div>
    <?php endforeach ?>
  </dl>
  <dl class="mt-8 grid gap-5 text-body sm:grid-cols-2">
    <div>
      <dt class="caption text-current/70"><?= e(t('common.address')) ?></dt>
      <dd class="mt-1.5"><?= e(nb($c['street'])) ?><br><?= e(t('format.localityRegionPostal', ['locality' => $c['locality'], 'region' => $c['region'], 'postal' => $c['postal_code']])) ?></dd>
    </div>
    <?php if (!empty($c['phone'])): ?>
    <div>
      <dt class="caption text-current/70"><?= e(t('common.phone')) ?></dt>
      <dd class="mt-1.5"><a href="<?= e(Schedule::telHref($c['phone'])) ?>" class="underline-offset-4 hover:underline"><?= e($c['phone']) ?></a></dd>
    </div>
    <?php endif ?>
    <?php if (!empty($c['email'])): ?>
    <div>
      <dt class="caption text-current/70"><?= e(t('common.email')) ?></dt>
      <dd class="mt-1.5 break-all"><a href="mailto:<?= e($c['email']) ?>" class="underline-offset-4 hover:underline"><?= e($c['email']) ?></a></dd>
    </div>
    <?php endif ?>
    <?php if (!empty($showPastor) && L($c, 'pastor') !== ''): ?>
    <div>
      <dt class="caption text-current/70"><?= e(t('common.pastor')) ?></dt>
      <dd class="mt-1.5"><?= e(L($c, 'pastor')) ?></dd>
    </div>
    <?php endif ?>
  </dl>
</div>
