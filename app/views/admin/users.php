<?php /** @var array $rows */ ?>
<div class="mb-6 flex justify-end"><a href="/admin/users/new" class="<?= e(abtn('ink')) ?>"><span><?= e(at('admin.users.new')) ?></span></a></div>
<div class="overflow-x-auto bg-white">
  <table class="w-full border-collapse text-left">
    <thead><tr class="caption text-stone"><th class="border-b border-ink/10 px-4 py-3"><?= e(at('admin.f.name')) ?></th><th class="border-b border-ink/10 px-4 py-3"><?= e(at('admin.f.email')) ?></th><th class="border-b border-ink/10 px-4 py-3"><?= e(at('admin.f.role')) ?></th><th class="border-b border-ink/10 px-4 py-3"><?= e(at('admin.users.lastLogin')) ?></th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?><tr class="border-b border-ink/10"><td class="px-4 py-3"><a href="/admin/users/<?= (int) $r['id'] ?>" class="font-semibold hover:text-blue"><?= e($r['name']) ?></a></td><td class="px-4 py-3"><?= e($r['email']) ?></td><td class="px-4 py-3"><?= e(at('admin.role.' . $r['role'])) ?></td><td class="px-4 py-3 text-small text-stone"><?= e((string) $r['last_login_at']) ?></td></tr><?php endforeach ?></tbody>
  </table>
</div>
