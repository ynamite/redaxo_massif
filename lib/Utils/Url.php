<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use Exception;

use rex_article;
use rex_fragment;
use rex_path;
use rex_url;
use rex_yform_manager_dataset;
use Url as RedaxoUrl;

class Url
{
  /**
   * Parse a URL and return its components.
   *
   * @param string $url
   * 
   * @return array<string, string>
   */
  public static function parse(string $url = ''): array
  {
    if (!$url)
      return [];

    $parsedUrl = parse_url($url);
    if (!isset($parsedUrl['scheme'])) {
      $url = 'http://' . $url;
    }
    $parsedUrl = parse_url($url);

    return ['url' => $url, 'host' => isset($parsedUrl['host']) ? $parsedUrl['host'] : '', 'path' => isset($parsedUrl['path']) ? $parsedUrl['path'] : ''];
  }

  /**
   * Get custom links from the given buttons.
   *
   * @param array $buttons
   * @param string $align
   * 
   * @return string
   */
  public static function getCustomLinks(array $buttons = [], string $align = ''): string
  {
    $buttonSet = [];
    foreach ($buttons as $button) {
      $buttonSet[] = self::getCustomLink($button['url'], ['label' => $button['label'], 'style' => isset($button['style']) ? $button['style'] : '']);
    }

    $buttonSet = array_filter($buttonSet);

    $fragment = new rex_fragment();
    $fragment->setVar('align', $align, false);
    $fragment->setVar('buttonSet', $buttonSet, false);
    return $fragment->parse('massif-buttons.php');
  }

  /**
   * Get a custom link from the given URL and parameters.
   *
   * @param string $url
   * @param array $params
   * 
   * @return array
   */
  public static function getCustomLink(string $url, array $_params = []): array
  {

    $return = [
      'url' => '',
      'target' => '',
      'type' => '',
      'label' => '',
      'style' => ''
    ];
    // set url
    if (!isset($url) or empty($url)) return [];

    $params = array_merge(['class' => ['cl-link']], $_params);
    $return['target'] = isset($params['target']) ? $params['target'] : '';

    $ytable = explode('://', $url);

    $entry = null;
    if (is_array($ytable) && count($ytable) === 2) {
      $table = str_replace('-', '_', $ytable[0]);
      $id = intval($ytable[1]);
      if ($id && $table) {
        try {
          $entry = rex_yform_manager_dataset::get($id, $table);
        } catch (Exception $e) {
          throw $e;
        }
        if ($entry) {
          $profile = array_shift(RedaxoUrl\Profile::getByTableName($table));
          if ($profile) {
            $return['url'] = rex_getUrl(null, null, [$profile->getNamespace() => $id]);
            $return['type'] = 'ytable';
            // $return['label'] = $params['record_label'];
            $return['data_id'] = $id;
          }
        }
      }
    }
    if (!$entry) {
      if (file_exists(rex_path::media($url)) === true) {
        // media file?
        $return['url'] = rex_url::media($url);
        $return['type'] = 'media';
        $return['target'] .= ' target="_blank" data-no-swup';
      } else {
        // no media, may be an external or internal URL
        $is_url = filter_var($url, FILTER_VALIDATE_URL);
        // probably an interalURL
        if ($is_url === FALSE && is_numeric($url) && $article = rex_article::get($url)) {
          $templateId = $article->getTemplateId();
          $return['url'] = rex_getUrl($url);
          $return['type'] = 'internal';
          if ($templateId == 2) {
            $return['url'] = 'javascript:void(0);';
            $return['target'] .= ' data-a11y-dialog-show="overlay-' . $url . '" data-no-swup';
          } else if (substr($url, 0, 1) === '#') {
            $return['target'] .= ' data-no-swup';
          }
        } else {
          // external URL
          $return['url'] = $url;
          $return['type'] = 'external';
          $return['target'] .= ' target="_blank" data-no-swup';
        }
      }
    }

    if (isset($params['label']))
      $return['label'] = $params['label'];

    if (isset($params['style']))
      $return['style'] = $params['style'];

    $return['class'] = $params['class'];
    return $return;
  }
}
