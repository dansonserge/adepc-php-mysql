<?php
use Adepc\Site;

/** @var array $story */
?>
<section aria-label="<?= e(t('home.testimonial.eyebrow')) ?>" class="bg-night text-white">
  <div class="grid-12 items-stretch">
    <?= photo(Site::media($story['media_id']), ['sizes' => '(min-width: 1024px) 42vw, (min-width: 768px) 50vw, 100vw', 'class' => 'col-span-4 aspect-[4/5] md:col-span-4 md:aspect-auto md:min-h-[42rem] lg:col-span-5']) ?>
    <figure class="edge col-span-4 flex flex-col justify-center py-(--section-y) md:col-span-4 lg:col-span-7 lg:pl-[6vw]">
      <?= eyebrow(e(t('home.testimonial.eyebrow'))) ?>
      <span aria-hidden="true" class="mt-8 block font-serif text-[8rem] leading-[0.5] text-gold"><?= e(t('home.testimonial.mark')) ?></span>
      <blockquote class="mt-2 font-serif text-[clamp(1.75rem,3.2vw,3.25rem)] leading-[1.15] italic" data-reveal="up"><?= e(L($story, 'quote')) ?></blockquote>
      <figcaption class="mt-10 border-l-2 border-gold pl-4"><span class="display block text-h3"><?= e($story['name']) ?></span><span class="mt-1 block text-small text-fog"><?= e(L($story, 'detail')) ?></span></figcaption>
    </figure>
  </div>
</section>
