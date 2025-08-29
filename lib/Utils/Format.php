<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use IntlDateFormatter;
use NumberFormatter;
use rex_clang;

class Format
{
  /**
   * format a range of values with a glue string
   * @param string $from The starting value of the range.
   * @param string $to The ending value of the range.
   * @param array $opts Optional parameters for formatting.
   * 
   * @return string The formatted range string.
   */

  public static function range(string $from = '', string $to = '', array $opts = []): string
  {
    $defs = ['glue' => ' bis ', 'addGlueOnEmptyFrom' => true];
    $opts = array_merge_recursive($defs, $opts);
    $glue = $opts['glue'];
    $val = '';
    if ($to) {
      if ($from) {
        $val = $from . $glue . $to;
      } else {
        if ($opts['addGlueOnEmptyFrom']) {
          $val = $glue . $to;
        } else {
          $val = $to;
        }
      }
    } else {
      $val = $from;
    }
    return $val;
  }

  /**
   * Format a price value.
   * @param float $value The price value to format.
   * @param string $locale The locale to use for formatting (default is 'de_CH').
   * 
   * @return string The formatted price string.
   */
  public static function price(float $value = 0, string $locale = 'de_CH'): string
  {
    $fmt = numfmt_create($locale, NumberFormatter::CURRENCY);
    return str_replace('.00', '.-', numfmt_format_currency($fmt, $value, "CHF"));
  }

  /**
   * Format a date value.
   * @param string $date The date value to format.
   * @param string $pattern The pattern to use for formatting (default is 'd. MMMM y').
   * @param string $locale The locale to use for formatting (default is 'de_CH').
   * 
   * @return string The formatted date string.
   */
  public static function date(string $date = '', string $pattern = 'd. MMMM y', string $locale = ''): string
  {
    $time = strtotime($date);

    if (!$time) {
      return '';
    }

    if (!$locale) $locale = rex_clang::getCurrent()->getValue('locale');
    if (!$locale) $locale = 'de_CH';
    $fmt = datefmt_create($locale, IntlDateFormatter::LONG, IntlDateFormatter::LONG, date_default_timezone_get());
    $fmt->setPattern($pattern);
    return datefmt_format($fmt, $time);
  }

  /**
   * Format a phone number.
   * @param string $phoneNumber The phone number to format.
   * 
   * @return string The formatted phone number.
   */
  public static function phone(string $phoneNumber = ''): string
  {
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

    if (strlen($phoneNumber) > 10) {
      $countryCode = substr($phoneNumber, 0, strlen($phoneNumber) - 10);
      $areaCode = substr($phoneNumber, -10, 3);
      $nextThree = substr($phoneNumber, -7, 3);
      $lastFour = substr($phoneNumber, -4, 4);

      $phoneNumber = '+' . $countryCode . $areaCode . $nextThree . $lastFour;
    } else if (strlen($phoneNumber) == 10) {
      $areaCode = substr($phoneNumber, 0, 3);
      $nextThree = substr($phoneNumber, 3, 3);
      $lastFour = substr($phoneNumber, 6, 4);

      $phoneNumber = $areaCode . $nextThree . $lastFour;
    } else if (strlen($phoneNumber) == 7) {
      $nextThree = substr($phoneNumber, 0, 3);
      $lastFour = substr($phoneNumber, 3, 4);

      $phoneNumber = $nextThree . $lastFour;
    }

    return $phoneNumber;
  }

  /**
   * Format a string to title case.
   * @param string $string The string to format.
   * 
   * @return string The formatted string.
   */
  public static function case(string $string): string
  {
    $word_splitters = array(' ', '-', "O'", "L'", "D'", 'St.', 'Mc');
    $lowercase_exceptions = array('the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'and', "l'", "d'");
    $uppercase_exceptions = array('III', 'IV', 'VI', 'VII', 'VIII', 'IX');

    $string = strtolower($string);
    foreach ($word_splitters as $delimiter) {
      $words = explode($delimiter, $string);
      $newwords = array();
      foreach ($words as $word) {
        if (in_array(strtoupper($word), $uppercase_exceptions))
          $word = strtoupper($word);
        else if (!in_array($word, $lowercase_exceptions))
          $word = ucfirst($word);

        $newwords[] = $word;
      }

      if (in_array(strtolower($delimiter), $lowercase_exceptions))
        $delimiter = strtolower($delimiter);

      $string = join($delimiter, $newwords);
    }
    return $string;
  }
}
