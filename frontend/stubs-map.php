<?php

/**
 * Build the stubs map for MASSIF's frontend assets.
 *
 * Returns an array<source-relative-to-frontend, target-relative-to-base> by
 * walking `frontend/{templates,modules,assets}/`. Target prefixes are derived
 * from viterex's user-configured paths so files land at the right location
 * regardless of structure (modern/classic/theme).
 *
 * Buckets:
 *   - templates/* → src/templates/* (developer-addon convention)
 *   - modules/*   → src/modules/*   (developer-addon convention)
 *   - assets/*    → <viterex.assets_source_dir>/* (e.g., src/assets/*)
 */

use Ynamite\ViteRex\Config as ViterexConfig;

$frontendDir = __DIR__;
$assetsSourceDir = trim(ViterexConfig::get('assets_source_dir'), '/');

$buckets = [
    'templates' => 'src/templates',
    'modules'   => 'src/modules',
    'assets'    => $assetsSourceDir !== '' ? $assetsSourceDir : 'src/assets',
];

$skipBasenames = ['.DS_Store', 'Thumbs.db'];

$map = [];
foreach ($buckets as $sub => $targetPrefix) {
    $bucketDir = $frontendDir . '/' . $sub;
    if (!is_dir($bucketDir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($bucketDir, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        if (in_array($file->getBasename(), $skipBasenames, true)) {
            continue;
        }
        $relative = substr($file->getPathname(), strlen($bucketDir) + 1);
        $map[$sub . '/' . $relative] = '/' . $targetPrefix . '/' . $relative;
    }
}

return $map;
