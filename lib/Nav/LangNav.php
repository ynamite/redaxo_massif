<?php

namespace Ynamite\Massif;

use rex;
use rex_article;
use rex_clang;
use rex_sql;

class LangNav
{

  /**
   * create navigation from redaxo cats
   * @param array $options_user
   * 
   * @return string
   */

  public static function get(array $options_user = []): string
  {

    if (!is_array($options_user))
      $options_user = array();

    $options = array(
      'navId'                    =>        'lang-nav',                // {string}		ID des äussersten DIVs
      'navClass'                =>        'lang-nav vers',                // {string}		Klasse des äussersten DIVs

      'nowrap'                =>        false,                    // {boolean}	true: der äussere DIV wird entfernt

      'showLangCodeOnly'        =>        true,                    // {boolean}	true: es wird nur der zweistellige Sprach-Code ausgegeben, zBsp. 'De', 'Fr' usw.

      'showDescription'        =>        false,                    // {boolean}	true: für die Sprachnavigation wird ein Label ausgegeben, default Label: 'Sprache'
      'description'            =>        'Sprache',                // {string}		das Label welches ausgegeben werden soll, showDescription muss auf true sein

      'addSeparator'            =>        false,                    // {boolean}	true: zwischen jedem Navigationselement wird ein Trennzeichen angezeigt
      'separatorChar'            =>        '',                    // {string}		das gewünschte Trennzeichen
      'separatorTag'            =>        'span',                    // {string}		HTML-Tag welches das Trennzeichen umschliesst
      'separatorTagClass'        =>        '',                        // {string}		CSS-Klasse für das Trennzeichen-Tag

      'addImages'                =>        false,                    // {boolean}	true: jedem Navigationselement wird ein Bild (aus dem Array images) hinzugefügt
      'imagesInline'            =>        false,                    // {boolean}	true: statt eine CSS-Hintergrundbild, wird ein inline Bild eingefügt
      'images'                =>        array(),                // {array}		ein Array mit Bildpfaden, die Bilder werden schlicht der Reihe nach eingefügt
      'imagesPath'            =>        '/theme/img/',            // {string}		Bildpfad
    );

    // Extending defaults array with user options
    $options = array_merge($options, $options_user);

    $counter = 0;

    $out = '';

    foreach (rex_clang::getAll(true) as $lang) {

      $text = ($options['showLangCodeOnly']) ? $lang->getValue('code') : $lang->getValue('name');

      //$out .= '<li class="lang-item li-lang-'.$lang->getValue('code').' li-lang-'.$lang->getValue('id').'">';
      $url = rex_getUrl(rex_article::getCurrentId(), $lang->getValue('id'));
      $urlManager = rex::getProperty('url-manager-data');
      if ($urlManager) {
        $url = rex_sql::factory()->setTable(rex::getTable('url_generator_url'))
          ->setWhere('data_id=:data_id AND clang_id=:clang_id', ['data_id' => $urlManager['data-id'], 'clang_id' => $lang->getValue('id')])->select('url')
          ->getValue('url');
        // $url = rex_getUrl('', $lang->getValue('id'), [$urlManager['profile-ns'] => $urlManager['data-id']]);
        // $url = rex_getUrl(null, $lang->getValue('id'), [$urlManager['profile-ns'] => $urlManager['data-id']]);
        // dump(rex_getUrl(null, 2, [$urlManager['profile-ns'] => $urlManager['data-id']]));
      }

      $out .= '<a href="' . $url;

      $out .= '" title="' . $lang->getValue('name') . '" class="flag-' . $lang->getValue('code') . ' lang-' . $lang->getValue('id');

      if (rex_clang::getCurrentId() == $lang->getValue('id')) {
        $out .= ' active';
      }
      $out .= '"';

      if ($options['addImages'] && !$options['imagesInline'] && isset($options['images'][$counter])) {
        $out .= ' style="background-image: url(' . $options['imagesPath'] . $options['images'][$counter] . ')"';
      }

      $out .= '>';

      if ($options['addImages'] && $options['imagesInline'] && isset($options['images'][$counter])) {
        $out .= '<img src="' . $options['imagesPath'] . $options['images'][$counter] . '" alt="' . $lang->getValue('name') . '" id="lang-img-' . $lang->getValue('id') . '" class="lang-img-' . $lang->getValue('code') . ' lang-img--' . $lang->getValue('id') . '" />';
      }

      $out .= $text . '</a>';
      //$out.= '</li>';

      $counter++;
    }
    //$out = '<ul class="'.$options['navClass'].'-list">' . $out . '</ul>';
    if ($options['showDescription'])
      $out = '<div class="lang-label flag-' . rex_clang::getCurrent()->getValue('code') . ' lang-' . rex_clang::getCurrentId() . '">' . $options['description'] . ' <span class="fa-angle-right fa"></span></div>' . $out;

    if (!$options['nowrap'])
      return '<div class="' . $options['navClass'] . '" id="lang-nav">' . $out . '</div>';
    else
      return $out;
  }
}
