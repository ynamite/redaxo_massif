<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use rex_var;

class RexVars
{
  /**
   * Convert a rex_var string to an array.
   * @param string $value
   * 
   * @return array
   */
  public static function toArray(string $value): array
  {
    $data = rex_var::toArray($value);

    if (!is_array($data) || empty($data)) {
      return [];
    }
    return array_filter($data, function ($item) {
      if (!is_array($item)) {
        return (bool)$item;
      }
      $val = '';
      foreach ($item as $k => $v) {
        if ($v) {
          $val = $v;
        }
      }
      return $val;
    });
  }
}
