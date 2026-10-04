<?php
/**
 * Mapbox GL, loaded by map.js only when the map nears the viewport and only
 * when a token is configured. @var string $label @var array $pins
 */
$config = [
    'token' => setting('maps.mapbox_token'),
    'style' => setting('maps.mapbox_style'),
    'js' => setting('maps.mapbox_js'),
    'css' => setting('maps.mapbox_css'),
    'pins' => $pins,
];
?>
<div role="region" aria-label="<?= e($label) ?>" class="h-full w-full" data-js-map="<?= e(json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>"></div>
