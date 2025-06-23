<?php

/**
 * @var rex_fragment $this
 * @psalm-scope-this rex_fragment
 */

$moduleCategories = [
    ['id' => 0, 'name' => 'oft benutzte Module'],
    ['id' => 60, 'name' => 'Module für einmalige Verwendung pro Seite'],
    ['id' => 70, 'name' => 'dynamische Module'],
    ['id' => 90, 'name' => 'Hilfs-Module']
];

/**
 * Discussion Issue #1174
 * Manipulate this fragment to influence the selection of modules on the slice.
 * By default the core fragment is used.
 *
 * @var bool   $block
 * @var string $button_label
 * @var array  $items        array contains all modules
 *             [0]        the index of array
 *             - [id]     the module id
 *             - [key]    the module key
 *             - [title]  the module name
 *             - [href]   the module url
 */

$items = $this->getVar("items");
$newItems = [];

$lastCatWritten = '';
$catIdx = 0;

foreach ($moduleCategories as $catIdx => $cat) {
    $identifier = $cat['id'];
    $nextIdentifier = isset($moduleCategories[$catIdx + 1]) ? $moduleCategories[$catIdx + 1]['id'] : PHP_INT_MAX;

    foreach ($items as $idx => $item) {
        if (!isset($item['title']) || !isset($item['id'])) {
            continue;
        }
        $numericalValue = (int)str_replace('* ', '', substr($item['title'], 0, 4));
        if ($numericalValue >= $identifier && $numericalValue < $nextIdentifier) {
            if ($lastCatWritten != $cat) {
                $newItems[] = ['header' => $cat['name']];
                $lastCatWritten = $cat;
            } else {
                $newItems[] = ['divider' => true];
            }
            $newItems[] = $item;
        }
    }
}

foreach ($newItems as $idx => &$item) {
    if (isset($item['title'])) {
        $item['title'] = '– ' . substr($item['title'], 6);
    }
}

$this->setVar("items", $newItems, false);

//print_r($items);
//die();

$this->subfragment('module_dropdown_with_groups.php');
