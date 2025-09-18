<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use rex;
use rex_media;

class Html
{

  /**
   * Get the H1 HTML tag with the given title and class.
   *
   * @param string $title
   * @param array $class
   * 
   * @return string
   */
  public static function getH1(string $title = '', array $class = []): string
  {
    if (!$title)
      return '';
    if (count($class) === 0)
      $class[] = 'h1';
    $hasH1 = rex::getProperty('has-h1');
    rex::setProperty('has-h1', true);
    $tag = $hasH1 ? 'h2' : 'h1';
    return '<' . $tag . ' class="' . implode(' ', $class) . '">' . $title . '</' . $tag . '>';
  }

  /**
   * Get the date HTML tag with the given datetime and class.
   *
   * @param string $dateTime
   * @param string $pattern
   * @param string $class
   * @param string $locale
   * 
   * @return string
   */
  public static function getDateTag(string $dateTime = '', string $pattern = 'd.MM.yy', string $class = 'datetime', string $locale = 'de_CH'): string
  {
    if (!$dateTime)
      return '';
    return '<time datetime="' . $dateTime . '" class="' . $class . '">' . Format::date($dateTime, $pattern, $locale) . '</time>';
  }

  /**
   * get HTML download link for a file
   * @param string $file The file to download.
   * 
   * @return string The HTML download link.
   */
  public static function getDownload(string $file = ''): string
  {
    $media = rex_media::get($file);
    $out = '';
    if ($media) {
      $icon = self::parseIcon($media->getExtension(), 'fa');
      //$icon = '<i class="icon icon-download"></i>';
      $label = $media->getTitle() ? $media->getTitle() : $media->getFilename();
      $out .= '<li class="download-item">';
      $out .= '<a href="' . $media->getUrl() . '" title="' . $label . '" target="_blank" class="h-icon download-anchor">' . $icon . '<span class="download-label">' . $label . ' </span></a>';
      // <span class="info">'.rex_formatter::bytes($media->getSize(), [1, '.', "'"]).'</span>
      $out .= '</li>';
    }
    return $out;
  }

  /**
   * get HTML download links for multiple files
   * @param string $files The files to download.
   * 
   * @return string The HTML download links.
   */
  public static function getDownloads(string $files = ''): string
  {
    $filesArray = explode(',', $files);
    $out = [];
    if (count($filesArray)) {
      $out[] = '<ul class="download-list">';
      foreach ($filesArray as $file) {
        $out[] = self::getDownload($file);
      }
      $out[] = '</ul>';
    }
    return implode('', $out);
  }

  /**
   * Get the icon for a file type.
   * @param string $ext The file extension.
   * @param string $_iconSet The icon set to use (default is 'fa').
   * 
   * @return string The HTML for the icon.
   */
  public static function parseIcon(string $ext, string $_iconSet = 'fa'): string
  {
    $iconLib = [
      'fa' => [
        'txt,json,ini' => 'file-text',
        'pdf' => 'file-pdf',
        'csv' => 'file-csv',
        'doc,docx' => 'file-word',
        'xlsx,xls' => 'file-excel',
        'pptx,ppt,ppsx' => 'file-powerpoint',
        'jpg,gif,png' => 'file-image',
        'zip,rar,7zip,sit' => 'file-archive',
        'mp3,m4a,wav,aac' => 'file-sound',
        'mp4,mpg,avi,mkv,webp' => 'file-video',
        'html,php,js,scss,sass,css' => 'file-code',
        'default-icon' => 'file',
        'template' => '<i class="icon far fa-{icon}"></i>'
      ]
    ];
    $iconSet = $iconLib[$_iconSet];
    $search = array_values(array_intersect_key($iconSet, array_flip(preg_grep("/\b$ext\b/", array_keys($iconSet), 0))));
    $icon = $search[0];
    if (!$icon) {
      $icon = $iconSet['default-icon'];
    }
    return str_replace('{icon}', $icon, $iconSet['template']);

    /*
		function preg_grep_keys($pattern, $input, $flags = 0) {
			return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags)));
		}
		*/
  }

  /**
   * Create a data table.
   * @param array $arr The data to include in the table.
   * @param array $options Optional parameters for the table.
   * @return string The HTML for the data table.
   */
  public static function createDataTable(array $arr, array $options = []): string
  {
    if (!is_array($arr))
      return '';
    if (count($arr) == 0)
      return '';

    $classes = ['data-table', 'typo-margin'];
    if (isset($options['class']))
      $classes[] = $options['class'];

    $out = '<dl class="' . implode(' ', $classes) . '">';
    foreach ($arr as $row) {
      $out .= '<dt>' . $row['label'] . '</dt>';
      $out .= '<dd>' . $row['value'] . '</dd>';
    }
    $out .= '</dl>';
    return $out;
  }
}
