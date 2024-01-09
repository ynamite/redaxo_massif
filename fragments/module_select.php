<?php

/**
 * @var rex_fragment $this
 * @psalm-scope-this rex_fragment
 */

$MODULE_CATEGORIES = [
    '* 00' => 'oft benutzte Module',
    '* 50' => 'Module für einmalige Verwendung pro Seite',
    '* 70' => 'Dynamische Module',
    '* 99' => 'Hilfs-Module'
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

$moduleCategories = $MODULE_CATEGORIES;

$lastCatWritten = '';
$catIdx = 0;

foreach ($items as $idx => $item) {
    $first2Digits = substr($item['title'], 0, 4);
    $nothingFound = true;

    foreach ($moduleCategories as $catIdentifier => $cat) {
        if ($first2Digits == $catIdentifier && $cat != $lastCatWritten) {
            $nothingFound = false;

            if ($lastCatWritten != "") {
                $items = array_merge(
                    array_slice($items, 0, $idx + $catIdx),
                    array(['divider' => true]),
                    array(['header' => $cat]),
                    array_slice($items, $idx + $catIdx)
                );
                $catIdx += 2;
            } else {
                $items = array_merge(
                    array_slice($items, 0, $idx + $catIdx),
                    array(['header' => $cat]),
                    array_slice($items, $idx + $catIdx)
                );
                $catIdx += 1;
            }

            $lastCatWritten = $cat;
        }
    }

    if ($nothingFound) {
        // TODO
    }
}

$this->setVar("items", $items, false);

//print_r($items);
//die();

$this->subfragment('module_dropdown_with_groups.php');
