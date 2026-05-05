<?php

use Ynamite\ViteRex\StubsInstaller;

if (!rex_addon::get('viterex_addon')->isAvailable()) {
    echo rex_view::warning(rex_i18n::msg('massif_frontend_viterex_required'));
    return;
}

$csrf = rex_csrf_token::factory('massif_frontend_install');
$installedAt = rex_config::get('massif', 'frontend_installed_at');
$message = '';

if (rex_post('install', 'bool')) {
    if (!$csrf->isValid()) {
        $message = rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    } else {
        $overwrite = (bool) rex_post('overwrite', 'bool');
        try {
            $stubsMap = require rex_path::addon('massif', 'frontend/stubs-map.php');
            $deps = json_decode((string) file_get_contents(rex_path::addon('massif', 'frontend/package-deps.json')), true);

            $result = StubsInstaller::installFromDir(
                rex_path::addon('massif', 'frontend'),
                $stubsMap,
                $overwrite,
                $deps,
            );
            StubsInstaller::appendRefreshGlobs([
                'src/addons/massif/fragments/**/*.php',
                'src/addons/massif/lib/**/*.php',
            ]);

            rex_config::set('massif', 'frontend_installed_at', time());
            $installedAt = rex_config::get('massif', 'frontend_installed_at');

            $summary = sprintf(
                '%d %s, %d %s, %d %s, %d %s',
                count($result['written']),
                rex_i18n::msg('massif_frontend_summary_written'),
                count($result['skipped']),
                rex_i18n::msg('massif_frontend_summary_skipped'),
                count($result['backedUp']),
                rex_i18n::msg('massif_frontend_summary_backed_up'),
                $result['packageDepsMerged'] ?? 0,
                rex_i18n::msg('massif_frontend_summary_deps_merged'),
            );
            $message = rex_view::success(rex_i18n::msg('massif_frontend_install_success') . ' — ' . $summary);
        } catch (Throwable $e) {
            $message = rex_view::error(rex_i18n::msg('massif_frontend_install_error') . ' — ' . rex_escape($e->getMessage()));
        }
    }
}

$status = $installedAt
    ? rex_i18n::msg('massif_frontend_installed_at', date('Y-m-d H:i:s', (int) $installedAt))
    : rex_i18n::msg('massif_frontend_not_installed');

$body = '<p>' . $status . '</p>';
$body .= '<dl class="dl-horizontal">';
$body .= '<dt><label for="massif-overwrite">' . rex_i18n::msg('massif_frontend_overwrite_label') . '</label></dt>';
$body .= '<dd><input type="checkbox" id="massif-overwrite" name="overwrite" value="1" /> <small>' . rex_i18n::msg('massif_frontend_overwrite_hint') . '</small></dd>';
$body .= '</dl>';

$buttons = '<button class="btn btn-save" type="submit" name="install" value="1">'
    . rex_i18n::msg('massif_frontend_install_button')
    . '</button>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('massif_frontend_title'));
$fragment->setVar('body', $body, false);
$fragment->setVar('buttons', $buttons, false);

if ($message !== '') {
    echo $message;
}

echo '<form action="' . rex_url::currentBackendPage() . '" method="post">';
echo $csrf->getHiddenField();
echo $fragment->parse('core/page/section.php');
echo '</form>';
