<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use hyphenator;

class Strings
{
  /**
   * Trim the text to the specified length and add ellipses if necessary.
   *
   * @param string $input
   * @param int $length
   * @param bool $ellipses
   * @param bool $strip_html
   * 
   * @return string
   */
  public static function trimText(string $input, int $length = 160, bool $ellipses = true, bool $strip_html = false): string
  {
    //strip tags, if desired
    if ($strip_html) {
      $input = strip_tags(stripslashes($input));
    }

    $totalCharacterLength = mb_strlen(str_replace(["\r", "\n", "\t", "&ndash;", "&rsquo;", "&#39;", "&quot;", "&nbsp;"], '', html_entity_decode(strip_tags($input))));

    //no need to trim, already shorter than trim length
    if ($totalCharacterLength <= $length) {
      return $input;
    }

    //find last space within length
    $last_space = strrpos(substr($input, 0, $length), ' ');
    $trimmed_text = substr($input, 0, $last_space);

    if (class_exists('hyphenator') && 1 == 2)
      $trimmed_text = hyphenator::hyphenate($trimmed_text);

    //add ellipses (...)
    if ($ellipses) {
      $trimmed_text .= ' &hellip;';
    }

    return $trimmed_text;
  }
}
