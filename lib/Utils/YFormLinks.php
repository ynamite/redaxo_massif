<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use rex_addon;
use rex_extension_point;
use rex_yform_manager_dataset;
use rex_yform_manager_table;
use Url\Profile;

/**
 * Resolves TinyMCE `link_yform` markers (href="rex-yf-foo://1", table name with `_` → `-`)
 * to real URLs: the dataset model's getUrl() first, then the url-addon profile of the table.
 * Unresolvable markers (unknown table, deleted dataset, no url source) are left as-is.
 */
class YFormLinks
{
  public static function replace(string $html): string
  {
    return preg_replace_callback('@(?<=href=["\'])([a-z0-9-]+)://(\d+)@i', static function (array $m): string {
      $table = str_replace('-', '_', $m[1]);
      if (null === rex_yform_manager_table::get($table) || null === $dataset = rex_yform_manager_dataset::get((int) $m[2], $table)) {
        return $m[0];
      }
      if (method_exists($dataset, 'getUrl')) {
        return rex_escape($dataset->getUrl() ?? $m[0]);
      }
      if (rex_addon::get('url')->isAvailable() && $profile = current(Profile::getByTableName($table))) {
        return rex_escape(rex_getUrl('', '', [$profile->getNamespace() => $dataset->getId()]));
      }
      return $m[0];
    }, $html);
  }

  public static function ep(rex_extension_point $ep): string
  {
    return self::replace($ep->getSubject());
  }
}
