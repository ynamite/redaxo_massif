<?php

namespace Ynamite\Massif\BE;

use rex_addon;
use rex_addon_interface;
use rex_extension;
use rex_extension_point;
use rex_config_form;
use rex_string;

class ConfigForm extends rex_config_form
{

  private static $addonName = 'viterex';
  protected $addon = null;
  protected $subpage = '';

  public static function getForm(string $package = 'massif', string $subpage)
  {

    self::$addonName = $package;
    /** @var rex_addon_interface $addon */
    $addon = rex_addon::get($package);
    $pages = $addon->getProperty('page');
    $fields = $pages['subpages'][$subpage]['fields'] ?? [];
    $fields = rex_extension::registerPoint(new rex_extension_point('MASSIF_CONFIG_FORM_FIELDS', $fields, [
      'subpage' => $subpage,
      'package' => $package,
    ]));

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
        $notice = $f['notice'] ?? '';
        if ($notice) {
          $label .= '<br><small class="help-block rex-note" style="display:inline-block; font-weight: normal; margin: 0;">' . $notice . '</small>';
        }
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
