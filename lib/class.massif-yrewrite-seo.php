<?php

/**
 * YREWRITE Addon.
 *
 * @author studio@massif.ch
 *
 * @package redaxo\yrewrite
 */

namespace Ynamite\Massif;

use rex_extension;
use rex_extension_point;
use rex_yrewrite;
use rex_yrewrite_seo;

class YrewriteSeo extends rex_yrewrite_seo
{
  public function __construct($article_id = 0, $clang = null)
  {
    parent::__construct($article_id, $clang);
  }

  public function getCanonicalUrl()
  {
    $canonical_url = $this->article->getValue(self::$meta_canonical_url_field);
    if ('' == $canonical_url) {
      $canonical_url = rex_yrewrite::getFullUrlByArticleId($this->article->getId(), $this->article->getClangId());
    } else {
      $canonical_url = trim($canonical_url);
    }
    $canonical_url = rex_extension::registerPoint(new rex_extension_point('YREWRITE_CANONICAL_URL', $canonical_url, ['article' => $this->article]));
    return $canonical_url;
  }

  public function cleanString($str)
  {
    return $str ? str_replace(["\n", "\r"], [' ', ''], $str) : '';
  }
}
