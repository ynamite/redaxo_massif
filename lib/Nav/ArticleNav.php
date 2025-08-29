<?php

namespace Ynamite\Massif;

use rex_article;
use rex_category;

class ArticleNav
{

  /**
   * create nav from articles
   *
   * @param int $parent_id
   * @param array $options_user
   * 
   * @return string
   */

  public static function get(int $parent_id = 0, array $options_user = []): string
  {
    $options['ignoreOnline'] = 1;
    $options['addStartArticle'] = 0;
    $options['include'] = array();
    $options['exclude'] = array(2);
    $options['list'] = false;
    $options['class'] = '';
    $options = array_merge($options, $options_user);
    $items = ($parent_id == 0) ? rex_article::getRootArticles($options['ignoreOnline']) : rex_category::get($parent_id)->getArticles($options['ignoreOnline']);
    if (count($items) == 0) return '';
    $out = '<nav class="article-nav';
    if ($options['class']) {
      $out .= ' ' . $options['class'];
    }
    $out .= '">';

    if ($options['list']) {
      $out .= '<ul>';
    }
    if ($options['addStartArticle']) {
      $start_article = rex_article::getSiteStartArticle();
      if ($start_article) {
        $item_out = '<a href="' . $start_article->getUrl() . '" title="' . $start_article->getName() . '" class="article-nav-item';
        if ($start_article->getId() == rex_article::getCurrentId()) {
          $item_out .= ' active';
        }
        $item_out .= '">' . $start_article->getName() . '</a>';

        if ($options['list']) {
          $out .= '<li>' . $item_out . '</li>';
        }
      }
    }
    foreach ($items as $item) {
      if ($item->getTemplateId() === 2) continue;
      if (!in_array($item->getId(), $options['exclude']) && !$item->isStartArticle() && (count($options['include']) < 1 || in_array($item->getId(), $options['include']))) {
        $item_out = '<a href="' . $item->getUrl() . '" title="' . $item->getName() . '" class="article-nav-item';
        if ($item->getId() == rex_article::getCurrentId()) {
          $item_out .= ' active';
        }
        $item_out .= '"';
        $attr = 'target="_self"';
        if ($item->getValue('yrewrite_url_type') === 'REDIRECTION_EXTERNAL')
          $attr = 'target="_blank" rel="nofollow noopener noreferrer"';


        $item_out .= $attr . '>' . $item->getName() . '</a>';
        if ($options['list']) {
          $out .= '<li>' . $item_out . '</li>';
        } else {
          $out .= $item_out;
        }
      }
    }
    if ($options['list']) {
      $out .= '</ul>';
    }
    $out .= '</nav>';

    return $out;
  }
}
