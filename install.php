<?php

/**
 * MASSIF addon install hook.
 *
 * Auto-scaffolds the MASSIF frontend (templates, modules, assets, npm deps)
 * into the user's project root iff:
 *   1. viterex_addon is installed and available, AND
 *   2. the frontend has not been scaffolded before (idempotency marker unset).
 *
 * Re-installs are explicit: either via viterex's "Install stubs" button (which
 * fires the VITEREX_INSTALL_STUBS extension point our boot.php subscribes to),
 * or via the dedicated re-install button on this addon's settings page.
 *
 * Failure here MUST NOT block the addon install — the backend tools (image
 * generation, MJML, YForm extensions, etc.) work standalone.
 */

use Ynamite\ViteRex\StubsInstaller;

if (
    rex_addon::get('viterex_addon')->isAvailable()
    && rex_config::get('massif', 'frontend_installed_at') === null
) {
    try {
        $stubsMap = require __DIR__ . '/frontend/stubs-map.php';
        $packageDeps = json_decode((string) file_get_contents(__DIR__ . '/frontend/package-deps.json'), true);

        StubsInstaller::installFromDir(__DIR__ . '/frontend', $stubsMap, false, $packageDeps);
        StubsInstaller::appendRefreshGlobs([
            'src/addons/massif/fragments/**/*.php',
            'src/addons/massif/lib/**/*.php',
        ]);

        rex_config::set('massif', 'frontend_installed_at', time());
    } catch (Throwable $e) {
        rex_logger::logException($e);
    }
}
