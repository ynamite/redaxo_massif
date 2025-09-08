<?php

namespace Ynamite\Massif\BE;

use rex_string;
use rex_addon;
use rex_config_form;

class ConfigForm extends rex_config_form
{

  private static $addonName = 'viterex';
  protected $addon = null;
  protected $subpage = '';

  public static function getForm(string $package = 'massif', string $subpage)
  {

    self::$addonName = $package;
    $addon = rex_addon::get($package);
    $pages = $addon->getProperty('page');
    $fields = $pages['subpages'][$subpage]['fields'];

    $form = new ConfigForm(self::$addonName);
    $form->addon = $addon;
    $form->subpage = $subpage;

    foreach ($fields as $f) {
      if (isset($f['active']) && $f['active'] === false)
        continue;
      $name = $subpage . '_' . rex_string::normalize($f['name']);
      $type = isset($f['type']) ? $f['type'] : 'text';
      switch ($type) {
        case "rex_media":
          $field = $form->addMediaField($name);
          break;
        case "textarea":
          $field = $form->addTextAreaField($name);
          break;
        case "text":
        default:
          $field = $form->addTextField($name);
          break;
      }
      if ($field) {
        if (isset($f['class']) && $f['class']) {
          $field->setAttribute('class', "form-control " . $f['class']);
        }
        if (isset($f['rows'])) {
          $field->setAttribute('rows', $f['rows']);
        }
        if (isset($f['style'])) {
          $field->setAttribute('style', $f['style']);
        }
        if (isset($f['data'])) {
          foreach ($f['data'] as $k => $v) {
            $field->setAttribute('data-' . $k, $v);
          }
        }
        $label = $f['label'];

        $field->setLabel($label);
        //$field->setNotice('test');
      }
    }

    return $form;
  }

  public function manualSave()
  {
    parent::save();

    return true;
  }
}
