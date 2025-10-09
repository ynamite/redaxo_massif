<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use rex_fragment;

class Rex
{
  /**
   * Parse a template file with the given variables.
   * @param string $file The template file to parse.
   * @param array $vars The variables to include in the template.
   * 
   * @return string The parsed template.
   */
  public static function parse(string $file, array $vars = []): string
  {
    $fragment = new rex_fragment();
    foreach ($vars as $key => $value) {
      $fragment->setVar($key, $value, false);
    }

    return $fragment->parse($file . ".php");
  }
}
