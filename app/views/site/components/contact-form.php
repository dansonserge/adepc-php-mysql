<?php
use Adepc\Front\Contact;
use Adepc\Site;

/** @var array $churches @var string $defaultChurch @var string $defaultPurpose @var array $state */
$purposes = Site::d()['purposes'];
$keys = array_column($purposes, 'pkey');
$v = $state['values'] ?? [];
$purpose = $v['purpose'] ?? (in_array($defaultPurpose, $keys, true) ? $defaultPurpose : ($keys[0] ?? ''));
$church = $v['church'] ?? $defaultChurch;
$isPrayer = (bool) (array_column($purposes, 'is_prayer', 'pkey')[$purpose] ?? false);
$err = $state['errors'] ?? [];
$field = 'mt-2 block w-full border-0 border-b-2 border-ink bg-transparent px-0 py-3 text-lead placeholder:text-stone/70 focus:border-blue focus:outline-none focus-visible:outline-none aria-[invalid=true]:border-ember';
$select = $field . ' cursor-pointer bg-[length:12px] bg-[right_0.25rem_center] bg-no-repeat';
$chevron = 'background-image:url("/media/icons/chevron.svg")';
$prayerKeys = array_values(array_map(static fn ($p) => $p['pkey'], array_filter($purposes, static fn ($p) => (int) $p['is_prayer'] === 1)));
$errorP = static fn (string $f) => isset($err[$f]) ? '<p id="cf-' . $f . '-err" class="mt-2 text-small font-semibold text-ember">' . e(t('contact.form.' . $err[$f])) . '</p>' : '';
if (($state['status'] ?? '') === 'sent'): ?>
<p role="status" class="border-l-4 border-gold py-2 pl-5 text-h3 leading-snug font-semibold"><?= e(t('contact.form.success')) ?></p>
<?php return; endif ?>
<form method="post" action="<?= e(url('contact')) ?>" novalidate class="space-y-10" data-js-contact="<?= e(json_encode(['prayer' => $prayerKeys, 'placeholder' => t('contact.form.messagePlaceholder'), 'sending' => t('contact.form.sending'), 'submit' => t('contact.form.submit'), 'success' => t('contact.form.success')], JSON_UNESCAPED_UNICODE)) ?>">
  <div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden"><label><?= e(t('contact.form.honeypot')) ?><input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
  <input type="hidden" name="startedAt" value="<?= e(Contact::stamp()) ?>">
  <div class="grid gap-10 md:grid-cols-2">
    <div>
      <label for="cf-name" class="caption"><?= e(t('contact.form.name')) ?></label>
      <input id="cf-name" name="name" autocomplete="name" required="" aria-invalid="<?= isset($err['name']) ? 'true' : 'false' ?>"<?= attrs(['aria-describedby' => isset($err['name']) ? 'cf-name-err' : null, 'value' => $v['name'] ?? null]) ?> class="<?= e($field) ?>">
      <?= $errorP('name') ?>
    </div>
    <div>
      <label for="cf-email" class="caption"><?= e(t('contact.form.email')) ?></label>
      <input id="cf-email" name="email" type="email" autocomplete="email" required="" aria-invalid="<?= isset($err['email']) ? 'true' : 'false' ?>"<?= attrs(['aria-describedby' => isset($err['email']) ? 'cf-email-err' : null, 'value' => $v['email'] ?? null]) ?> class="<?= e($field) ?>">
      <?= $errorP('email') ?>
    </div>
  </div>
  <div class="grid gap-10 md:grid-cols-2">
    <div>
      <label for="cf-purpose" class="caption"><?= e(t('contact.form.purpose')) ?></label>
      <select id="cf-purpose" name="purpose" class="<?= e($select) ?>" style="<?= e($chevron) ?>">
        <?php foreach ($purposes as $p): ?><option value="<?= e($p['pkey']) ?>"<?= $p['pkey'] === $purpose ? ' selected=""' : '' ?>><?= e(L($p, 'label')) ?></option><?php endforeach ?>
      </select>
    </div>
    <div>
      <label for="cf-church" class="caption"><?= e(t('contact.form.church')) ?></label>
      <select id="cf-church" name="church" class="<?= e($select) ?>" style="<?= e($chevron) ?>">
        <option value=""<?= $church === '' ? ' selected=""' : '' ?>><?= e(t('contact.form.churchAny')) ?></option>
        <?php foreach ($churches as $c): ?><option value="<?= e($c['slug']) ?>"<?= $c['slug'] === $church ? ' selected=""' : '' ?>><?= e($c['name']) ?></option><?php endforeach ?>
      </select>
    </div>
  </div>
  <div>
    <label for="cf-message" class="caption"><?= e(t('contact.form.message')) ?></label>
    <textarea id="cf-message" name="message" rows="6" required=""<?= attrs(['placeholder' => $isPrayer ? t('contact.form.messagePlaceholder') : null]) ?> aria-invalid="<?= isset($err['message']) ? 'true' : 'false' ?>"<?= attrs(['aria-describedby' => isset($err['message']) ? 'cf-message-err' : null]) ?> class="<?= e($field) ?> resize-y"><?= e($v['message'] ?? '') ?></textarea>
    <?= $errorP('message') ?>
  </div>
  <label class="flex cursor-pointer items-start gap-4 text-body"<?= $isPrayer ? '' : ' hidden=""' ?> data-js-confidential><input type="checkbox" name="confidential" class="check mt-1 h-5 w-5 shrink-0 cursor-pointer border-2 border-ink"<?= !empty($v['confidential']) ? ' checked=""' : '' ?>><?= e(t('contact.form.confidential')) ?></label>
  <div aria-live="polite" data-js-contact-status><?php if (($state['status'] ?? '') === 'fallback'): ?><p class="mb-6 text-body"><?= e(t('contact.form.fallback', ['email' => setting('org.email')])) ?></p><?php elseif (($state['status'] ?? '') === 'error'): ?><p role="alert" class="mb-6 text-body font-semibold text-ember"><?= e(t('contact.form.error', ['email' => setting('org.email')])) ?></p><?php endif ?></div>
  <button type="submit" class="<?= e(button_class('ink', 'disabled:opacity-60')) ?>"><span><?= e(t('contact.form.submit')) ?></span><?= arrow() ?></button>
</form>
<?php if (($state['status'] ?? '') === 'fallback' && !empty($state['mailto'])): ?><a hidden="" href="<?= e($state['mailto']) ?>" data-js-mailto></a><?php endif ?>
