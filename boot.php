<?php

namespace Ynamite\Massif;

use rex;
use rex_addon;
use rex_addon_interface;
use rex_be_page_main;
use rex_clang;
use rex_effect_auto;
use rex_extension;
use rex_extension_point;
use rex_i18n;
use rex_fragment;
use rex_media_manager;
use rex_path;
use rex_plugin;
use rex_sql;
use rex_view;
use rex_yform;
use rex_yform_manager_field;
use rex_yform_manager_table;

use Ynamite\Massif\Usability;

/**
 * MASSIF stuff
 */

/** @var rex_addon_interface $this */

// add own backend css
if (rex::isBackend() && rex::getUser()) {
    rex_view::addCssFile($this->getAssetsUrl('css/style.css'));
    rex_view::addJsFile($this->getAssetsUrl('js/scripts.js'));
}

rex_fragment::addDirectory($this->getPath('fragments'));
rex_fragment::addDirectory(rex_path::addon('project/fragments'));


if (rex_addon::get('media_manager')->isAvailable()) {
    rex_media_manager::addEffect(rex_effect_auto::class);
    rex_extension::register('MEDIA_MANAGER_FILTERSET', 'rex_effect_auto::handle', rex_extension::EARLY);
}

rex_extension::register('PACKAGES_INCLUDED', function (rex_extension_point $ep) {
    if (
        rex_addon::exists('yform') &&
        rex_addon::get('yform')->isAvailable() &&
        rex_plugin::get('yform', 'manager')->isAvailable()
    ) {
        rex_yform::addTemplatePath($this->getPath('ytemplates'));
    }
});

if (rex::isBackend() && rex::getUser() && rex_plugin::get('yform', 'manager')) {

    // $sql = rex_sql::factory();

    // $projects = rex_yform_manager_table::get('rex_yf_project')->query()->find();
    // foreach ($projects as $entry) {
    //     $team_ids = explode(',', $entry->project_team);
    //     foreach ($team_ids as $team_id) {
    //         if (!$team_id) continue;
    //         $query = 'INSERT INTO rex_yf_project_to_team (id_project, id_team) VALUES (' . $entry->getId() . ', ' . $team_id . ')';
    //         $sql->setQuery($query);
    //     }
    // }
    // exit();


    rex_extension::register("YFORM_DATA_LIST", Usability::ep_yform_data_list(...));

    // hide yform from non-admins

    if (!rex::getUser()->isAdmin()) {
        rex_extension::register('PAGES_PREPARED', function ($ep) {
            $pages = $ep->getSubject();
            foreach ($pages as $index => $page) {
                if ($page instanceof rex_be_page_main) {
                    if ($page->getKey() == 'yform') {
                        $page->setHidden(true);
                    }
                }
            }
            $pages = $pages;
            $ep->setSubject($pages);
        });
    }

    // yform tables before addons

    rex_extension::register('PAGES_PREPARED', function (rex_extension_point $ep) {
        $pages = $ep->getSubject();
        $highAddOns = [
            'project',
            'metainfo',
            'massif_settings',
            'yform',
            'media_manager',
        ];
        $highAddOnPages = [];
        $lowAddOnPages = [];
        /* @var $page rex_be_page_main */
        foreach ($pages as $index => $page) {
            if ($page instanceof rex_be_page_main && $page->getBlock() == 'addons') {
                if (in_array($page->getKey(), $highAddOns)) {
                    $page->setBlock('high_addons');
                    $highAddOnPages[$page->getKey()] = $page;
                } else {
                    $page->setBlock('low_addons');
                    $lowAddOnPages[$page->getKey()] = $page;
                }
                unset($pages[$index]);
            }
        }
        $pages = array_merge($pages, $highAddOnPages, $lowAddOnPages);
        $ep->setSubject($pages);
    }, rex_extension::LATE);
    rex_extension::register('OUTPUT_FILTER', function (rex_extension_point $ep) {
        $ep->setSubject(
            str_replace(
                [
                    '[translate:navigation_high_addons]',
                    '[translate:navigation_low_addons]',
                ],
                [
                    rex_i18n::msg('navigation_addons') . ' *',
                    rex_i18n::msg('navigation_addons'),
                ],
                $ep->getSubject()
            )
        );
    });
}

if (rex::isBackend() && rex::getUser()) {
    rex_extension::register('PAGES_PREPARED', function (rex_extension_point $ep) {
        $pages = $ep->getSubject();

        $addonPages = [];
        /** @var rex_be_page_main $page */
        foreach ($pages as $index => $page) {
            if ($page instanceof rex_be_page_main && $page->getBlock() == 'addons') {
                $page->setBlock('z_addons');
                $addonPages[$page->getKey()] = $page;
                unset($pages[$index]);
            }
        }
        $pages = array_merge($pages, $addonPages);
        $ep->setSubject($pages);
    }, rex_extension::LATE);

    rex_extension::register('OUTPUT_FILTER', function (rex_extension_point $ep) {
        $ep->setSubject(
            str_replace(
                '[translate:navigation_z_addons]',
                rex_i18n::msg('navigation_addons'),
                $ep->getSubject()
            )
        );
    });
}


//rex_autoload::addDirectory($this->getPath('lib'));

// extension point for massif_grid 
/*rex_extension::register('ART_CONTENT', function(rex_extension_point $ep){
	$ep->setSubject($ep->getSubject() . rex::getProperty('massif-grid')->finish());
});
*/
if (rex::isBackend() && rex::getUser()) {
    // check media in use
    rex_extension::register('MEDIA_IS_IN_USE', function (rex_extension_point $ep) {
        $params = $ep->getParams();
        $sql = rex_sql::factory();
        $escapedFilename = $sql->escape($params['filename']);
        $warning = $ep->getSubject();
        if (rex_plugin::get('yform', 'manager') && count(rex_yform_manager_table::getAll()) > 0) {
            $sql->setQuery('SELECT * FROM `' . rex_yform_manager_field::table() . '` LIMIT 0');
            $columns = $sql->getFieldnames();
            $select = in_array('multiple', $columns) ? ', `multiple`' : '';
            $fields = $sql->getArray('SELECT `table_name`, `name`' . $select . ' FROM `' . rex_yform_manager_field::table() . '` WHERE `type_id`="value" AND `type_name` IN("be_media","mediafile","imagelist")');
            $fields = rex_extension::registerPoint(new rex_extension_point('YFORM_MEDIA_IS_IN_USE', $fields));
            $messages = '';
            if (count($fields)) {
                $tables = [];
                foreach ($fields as $field) {
                    //var_dump($field);
                    $tableName = $field['table_name'];
                    $condition = $sql->escapeIdentifier($field['name']) . ' = ' . $escapedFilename;
                    if (isset($field['multiple']) && $field['multiple'] == 1) {
                        $condition = 'FIND_IN_SET(' . $escapedFilename . ', ' . $sql->escapeIdentifier($field['name']) . ')';
                    }
                    $tables[$tableName][] = $condition;
                }
                $messages = '';
                foreach ($tables as $tableName => $conditions) {
                    $items = $sql->getArray('SELECT `id` FROM ' . $tableName . ' WHERE ' . implode(' OR ', $conditions));
                    if (count($items)) {
                        foreach ($items as $item) {
                            $sqlData = rex_sql::factory();
                            $sqlData->setQuery('SELECT `name` FROM `' . rex_yform_manager_table::table() . '` WHERE `table_name` = "' . $tableName . '"');
                            $messages .= '<li><a href="javascript:openPage(\'index.php?page=yform/manager/data_edit&amp;table_name=' . $tableName . '&amp;data_id=' . $item['id'] . '&amp;func=edit\')">' . $sqlData->getValue('name') . ' [id=' . $item['id'] . ']</a></li>';
                        }
                    }
                }
            }
            if ($messages != '') {
                $warning[] = 'Tabelle<br /><ul>' . $messages . '</ul>';
            }
        }


        if (rex_addon::get('metainfo')) {
            $fields = $sql->getArray('SELECT name FROM `rex_metainfo_field` WHERE `type_id` = 6');
            $fields = rex_extension::registerPoint(new rex_extension_point('METAINFO_MEDIA_IS_IN_USE', $fields));
            $messages = '';
            if (count($fields)) {
                foreach ($fields as $field) {
                    $items = $sql->getArray('SELECT `id`, `parent_id`, `name` FROM rex_article WHERE ' . $sql->escapeIdentifier($field['name']) . ' = ' . $escapedFilename);
                    if (count($items)) {
                        foreach ($items as $item) {
                            $messages .= '<li><a href="javascript:openPage(\'index.php?page=content/edit&amp;category_id=' . ($item['parent_id'] ? $item['parent_id'] : $item['id']) . '&amp;article_id=' . $item['id'] . '&amp;clang_id=' . rex_clang::getCurrentId() . '&amp;mode=edit\')">' . $item['name'] . ' [id=' . $item['id'] . ']</a></li>';
                        }
                    }
                }
            }
            if ($messages != '') {
                $warning[] = 'Artikel Metadaten<br /><ul>' . $messages . '</ul>';
            }
        }



        //$escapedFilename = '"' . $escapedFilename . '"';
        $items = $sql->getArray('SELECT `namespace`, `key` FROM `rex_config` WHERE `namespace` = "massif_settings" AND (`value` = "\"' . $params['filename'] . '\"" OR FIND_IN_SET("\"' . $params['filename'] . '\"", `value`))');
        $items = rex_extension::registerPoint(new rex_extension_point('REX_CONFIG_MEDIA_IS_IN_USE', $items));
        //die(var_dump('SELECT `namespace`, `key` FROM `rex_config` WHERE `namespace` = "massif_settings" AND (`value` = "\"'.$params['filename'].'\"" OR FIND_IN_SET("\"'.$params['filename'].'\"", `value`))'));
        $messages = '';
        if (count($items)) {
            foreach ($items as $item) {
                $ns = explode('_', $item['key']);
                $messages .= '<li><a href="javascript:openPage(\'index.php?page=massif_settings/' . $ns[0] . '\')">Feld: ' . $ns[0] . ' ' . $ns[1] . '</a></li>';
            }
        }
        if ($messages != '') {
            $warning[] = 'Website-Einstellungen<br /><ul>' . $messages . '</ul>';
        }



        return $warning;
    });
}
