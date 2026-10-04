<?php
declare(strict_types=1);

/*
 * Dev-only: generates the WebP sizes and blur placeholders for the seed
 * images in public/media/images and writes them back into the seed JSON,
 * so a fresh install never has to process images on the server.
 *
 *   php tools/build-media.php
 */
define('PUBLIC_DIR', dirname(__DIR__) . '/public');
require dirname(__DIR__) . '/app/bootstrap.php';

use Adepc\MediaProcessor;

$seed = dirname(__DIR__) . '/app/database/seed/media.json';
$rows = json_decode((string) file_get_contents($seed), true);
foreach ($rows as &$row) {
    if ($row['kind'] !== 'image') {
        continue;
    }
    // Icons/favicons/share image are served as-is; everything else gets sizes.
    $result = MediaProcessor::process(dirname($row['path']), PUBLIC_DIR . '/media/' . $row['path'], false);
    $row['width'] = $result['width'];
    $row['height'] = $result['height'];
    $row['variants'] = $result['variants'];
    $row['blur'] = $result['blur'];
    echo $row['ref'], ' ', $result['width'], 'x', $result['height'], ' → ', $result['variants'], "\n";
}
unset($row);
file_put_contents($seed, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
