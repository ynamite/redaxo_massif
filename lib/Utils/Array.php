<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

class Arrays
{
  /**
   * Sort an array horizontally into a specified number of columns.
   * @param array $data The input array to be sorted.
   * @param int $numCols The number of columns to sort into.
   * 
   * @return array The sorted array.
   */
  public static function sortEntriesHorizontally(array $data, int $numCols = 3)
  {

    $temp = [];
    $numEntries = count($data);
    $numRows = round($numEntries / $numCols);
    $q = 0;
    for ($i = 0; $i < $numRows; $i++) {
      for ($r = 0; $r < $numCols; $r++) {
        if ($data[$q])
          $temp[$r][$i] = $data[$q];
        $q++;
      }
    }
    foreach ($temp as $row) {
      foreach ($row as $col) {
        $new[] = $col;
      }
    }
    unset($temp);
    return $new;
  }
}
