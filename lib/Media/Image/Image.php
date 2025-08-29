<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

use InvalidArgumentException;

use rex_clang;
use rex_media;
use rex_url;

class Image
{

  private const MANAGER_PATH = 'image/';
  private const EXCLUDE_EXTENSIONS_FROM_RESIZE = ['svg', 'gif'];
  public ?array $breakPoints = [];

  /**
   * Get image markup
   * @param string $src
   * @param string $alt
   * @param string $sizes
   * @param int $width
   * @param int $height
   * @param LoadingBehavior $loading
   * @param DecodingBehavior $decoding
   * @param FetchPriorityBehavior $fetchPriority
   * 
   * @return string
   */
  public static function get(
    string $src,
    string $alt = '',
    string $sizes = '',
    int $width = 0,
    int $height = 0,
    array $breakPoints = ImageConfig::BREAKPOINTS,
    $loading = LoadingBehavior::LAZY,
    $decoding = DecodingBehavior::AUTO,
    $fetchPriority = FetchPriorityBehavior::AUTO
  ): string {
    $config = new ImageConfig(
      src: $src,
      alt: $alt,
      width: $width,
      height: $height,
      sizes: $sizes,
      breakPoints: $breakPoints,
      loading: $loading,
      decoding: $decoding,
      fetchPriority: $fetchPriority
    );

    $image = new Image();

    return $image->render($config);
  }

  /**
   *	render image markup
   * @param ImageConfig $config
   *
   * @return string
   */

  public function render(ImageConfig $config): string
  {
    $html = '';
    if ($config->src == '') return $html;

    $rex_media = rex_media::get($config->src);
    if (!$rex_media) return $html;

    $this->breakPoints = array_values(array_intersect($config->breakPoints, ImageConfig::BREAKPOINTS));
    if (empty($this->breakPoints)) {
      throw new InvalidArgumentException('Invalid breakpoints');
    }

    $url = $rex_media->getUrl();

    $ext = $rex_media->getExtension();
    if ($ext === 'gif') {
      $url .= '?' . time();
    }

    $width = $config->width ?: $rex_media->getWidth();
    $height = $config->height ?: $rex_media->getHeight();
    $alt = $config->alt ?: $rex_media->getTitle();
    $sizes = $config->sizes ?: $this->getSizes();
    $style = [];
    $className = [];
    $className[] = $config->className ?: '';

    $focuspoint = array_filter(explode(',', $rex_media->getValue('med_focuspoint')));
    if (!empty($focuspoint)) {
      $style[] = 'object-position: ' . $focuspoint[0] . '% ' . $focuspoint[1] . '%';
    }

    $className = array_filter($className);
    $style = array_filter($style);

    $html = '<img alt="' . $alt . '" ';
    if (!in_array($ext, self::EXCLUDE_EXTENSIONS_FROM_RESIZE)) {
      $lip = $this->breakPoints[0];
      $html .= 'srcset="' . $this->getSrcset($config->src) . '" ';
      $html .= 'src="' . self::getPath($config->src, $lip) . '" ';
    } else {
      $html .= 'src="' . $url . '" ';
    }
    if ($width) $html .= 'width="' . $width . '" ';
    if ($height) $html .= 'height="' . $height . '" ';
    if ($sizes) $html .= 'sizes="' . $sizes . '" ';
    if (!empty($className)) $html .= 'class="' . implode(' ', $className) . '" ';
    if (!empty($style)) $html .= 'style="' . implode('; ', $style) . '" ';
    $html .= 'loading="' . $config->loading->value . '" ';
    $html .= 'decoding="' . $config->decoding->value . '" ';
    $html .= 'fetchpriority="' . $config->fetchPriority->value . '" ';
    $html .= ' />';

    return $html;
  }


  /**
   * Get image path
   *
   * @param string $src
   * @param int $size
   * 
   * @return string
   */

  public static function getPath(string $src, int $size): string
  {
    if (!in_array($size, ImageConfig::BREAKPOINTS))
      $size = ImageConfig::BREAKPOINTS[0];

    $url = self::MANAGER_PATH . 'auto/' . $size . '/' . $src;
    return rex_url::frontend() . $url;
  }

  /**
   * Get image srcset
   *
   * @param string $src
   * 
   * @return string
   */
  public function getSrcset(string $src): string
  {
    $srcset = [];

    $sizes = $this->breakPoints;
    array_shift($sizes);

    foreach ($sizes as $key => $size) {
      $srcset[] = self::getPath($src, $size) . ' ' . $size . 'w';
    }

    return implode(', ', $srcset);
  }

  /**
   * Get image sizes
   *
   * @return string
   */
  public function getSizes()
  {
    $output = [];
    $sizes = $this->breakPoints;
    array_shift($sizes);
    $maxSize = array_pop($sizes);
    $maxWidths = $sizes;
    array_shift($maxWidths);
    $maxWidths[] = $maxSize;
    for ($i = 0; $i < count($sizes); $i++) {
      $output[] = '(max-width: ' . $maxWidths[$i] . 'px) ' . $sizes[$i] . 'px';
    }
    $output[] = $maxSize . 'px';
    return implode(', ', $output);
  }
  /**
   *	get image meta info
   * @param string $file
   * @param string $field

   * @return string|null
   */

  public static function getMeta($file, $field = 'title')
  {

    if ($file = rex_media::get($file)) {
      $title = $file->getValue($field);
      if (rex_clang::getCurrentId() == 2)
        $title = $file->getValue('med_title_2');

      return $title;
    }
  }
}
