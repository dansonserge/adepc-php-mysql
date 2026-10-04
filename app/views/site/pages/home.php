<?php
/** @var array $churches @var array $events @var ?array $film @var ?array $latest @var array $videos @var ?array $story */
?>
<?= partial('sections/home/hero', ['churches' => $churches]) ?>
<?= partial('sections/home/this-sunday', ['churches' => $churches]) ?>
<?= partial('sections/home/church-band', ['churches' => $churches]) ?>
<?php if ($film): ?><?= partial('sections/home/film', ['film' => $film]) ?><?php endif ?>
<?= partial('sections/home/mission') ?>
<?= partial('sections/home/pillars') ?>
<?= partial('components/pastor-word') ?>
<?= partial('sections/home/events-preview', ['events' => $events, 'churches' => $churches]) ?>
<?= partial('sections/home/stories-strip') ?>
<?= partial('sections/home/watch-preview', ['latest' => $latest, 'videos' => $videos]) ?>
<?php if ($story): ?><?= partial('sections/home/testimonial', ['story' => $story]) ?><?php endif ?>
<?= partial('sections/home/give-block') ?>
