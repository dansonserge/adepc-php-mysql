<?php
use Adepc\Img;
use Adepc\Site;

// The logo's emblem on its own white tile, next to the short name set in type.
$emblem = Site::slot('brand.emblem');
?><span class="flex items-center gap-3 md:gap-3.5 "><span class="block size-11 shrink-0 bg-white p-1 md:size-13"><?php if ($emblem): ?><?= Img::tag($emblem, [
    'alt' => Site::L($emblem, 'alt'),
    'sizes' => '(min-width: 48rem) 52px, 44px',
    'loading' => 'eager',
    'class' => 'h-full w-full object-contain',
]) ?><?php endif ?></span><span class="display text-[1.5rem] leading-none tracking-[0.02em] md:text-[1.75rem]"><?= e(setting('site.short_name')) ?></span></span>
