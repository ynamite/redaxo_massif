<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use rex;
use rex_article_slice;
use rex_sql;

class Slice
{
  /**
   * Check if the given slice is the first or last slice.
   *
   * @param int $slice_id The slice ID to check.
   * @param string $firstSliceClass The CSS class for the first slice (default: 'first-row').
   * @param string $lastSliceClass The CSS class for the last slice (default: 'last-row').
   * 
   * @return string The CSS class for the slice.
   */

  public static function isFirstOrLast(int $id = 0, string $firstSliceClass = 'first-row', string $lastSliceClass = 'last-row'): string
  {
    static $sliceCache = [];

    if (!$id) {
      return '';
    }
    if (count($sliceCache) === 0) {
      $slice = rex_article_slice::getArticleSliceById($id);
      $sql = rex_sql::factory();
      $query = '
						SELECT *
            FROM ' . rex::getTable('article_slice') . '
            WHERE article_id=? AND clang_id=? AND revision=? 
            ORDER BY priority ';
      $queryFirst = $query . 'LIMIT 1';
      $queryLast = $query . 'DESC LIMIT 1';
      $first = rex_article_slice::fromSql($sql->setQuery($queryFirst, [$slice->getArticleId(), $slice->getClangId(), $slice->getRevision()]));
      $last = rex_article_slice::fromSql($sql->setQuery($queryLast, [$slice->getArticleId(), $slice->getClangId(), $slice->getRevision()]));
      $sliceCache = ['first' => $first->getId(), 'last' => $last->getId()];
    }
    $out = '';
    if ($sliceCache['first'] == $id) $out .= ' ' . $firstSliceClass;
    if ($sliceCache['last'] == $id) $out .= ' ' . $lastSliceClass;
    return $out;
  }
}
