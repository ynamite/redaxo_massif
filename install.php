<?php

/**
 * MASSIF addon install hook.
 *
 * When viterex_addon is available, registers this addon's own fragments/ and
 * lib/ directories with viterex's live-reload globs, so editing backend PHP
 * triggers a Vite reload during development.
 *
 * Gated on viterex_addon — the addon works standalone without it. Failure
 * here is logged and never blocks addon activation.
 */

use Ynamite\ViteRex\StubsInstaller;

if (rex_addon::get('viterex_addon')->isAvailable()) {
    try {
        StubsInstaller::appendRefreshGlobs([
            'src/addons/massif/fragments/**/*.php',
            'src/addons/massif/lib/**/*.php',
        ]);
    } catch (Throwable $e) {
        rex_logger::logException($e);
    }
}
