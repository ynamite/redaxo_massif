<?php

namespace Ynamite\Massif\Redactor;

use rex_addon;
use rex_extension_point;

class Extension
{
  public static function register(rex_extension_point $ep)
  {
    $pluginDirs = [...$ep->getSubject(), rex_addon::get('massif')->getAssetsPath('redactor')];
    $ep->setSubject($pluginDirs);
  }
}
