<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use rex_article;
use rex_sql;
use rex_string;

class Article
{
  /**
   * checks if the article is the start article
   * @param int $id The article ID to check.
   * 
   * @return bool True if the article is the start article, false otherwise.
   */
  public static function isStartArticle(int $id = 0): bool
  {
    if ($id === 0) {
      $id = rex_article::getCurrentId();
    }
    return rex_article::getSiteStartArticleId() === $id;
  }

  /**
   * Get the anchor navigation for the current article.
   * 
   * @return string The HTML for the anchor navigation.
   */
  public static function getAnchorNav(): string
  {
    $sql = rex_sql::factory();
    $sql->setQuery('SELECT value1 as name FROM rex_article_slice WHERE module_id = 80 AND article_id = ' . rex_article::getCurrentId() . ' ORDER BY priority');
    $result = $sql->getArray();
    return Rex::parse('anchor-nav', ['data' => $result]);
  }

  /**
   * Normalize the article name by its ID.
   *
   * @param int $id The article ID to normalize.
   * 
   * @return string The normalized article name.
   */
  public static function normalizeArticleNameById(int $id = 0): string
  {

    if ((int) $id == 0)
      return '';

    $article = rex_article::get($id);
    if (!$article) {
      return '';
    }

    return rex_string::normalize($article->getName());
  }

  /**
   * Get the path route for the current article.
   *
   * @return array The path route for the current article.
   */
  public static function getPathRoute(): array
  {
    $path = rex_article::getCurrent()->getPathAsArray();
    if (!in_array(rex_article::getCurrentId(), $path)) {
      $path[] = rex_article::getCurrentId();
    }
    return $path;
  }
}
