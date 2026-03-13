<?php

namespace Ynamite\Massif;

use rex_article;
use rex_media;

class NavHelper
{
  public static function getLinkAttributes(int $id, bool $asArray = false): array|string
  {
    $article = rex_article::get($id);
    $data = [
      'href' => $article ? $article->getUrl() : '#',
      'target' => '',
      'rel' => '',
      'swup' => true,
    ];
    if ($article->getValue('yrewrite_url_type') === 'REDIRECTION_MEDIA') {
      $data['swup'] = false;
      $data['target'] = '_blank';
      $media = rex_media::get($article->getValue('yrewrite_redirection'));
      if ($media)
        $data['href'] = $media->getUrl();
    } else if ($article->getValue('yrewrite_url_type') === 'REDIRECTION_EXTERNAL') {
      $data['swup'] = false;
      $data['target'] = '_blank';
      $data['rel'] = 'nofollow noopener noreferrer';
    }
    if (!$asArray) {
      $attr = '';
      foreach ($data as $key => $val) {
        if ($val) {
          $attr .= ' ' . $key . '="' . $val . '"';
        }
      }
      return $attr;
    }
    return $data;
  }
}
