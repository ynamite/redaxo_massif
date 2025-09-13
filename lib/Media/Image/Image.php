<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

use InvalidArgumentException;

use rex_clang;
use rex_media;

class Image
{
  public ?array $breakPoints = [];

  private ImageConfig $config;
  private const EXCLUDE_EXTENSIONS_FROM_RESIZE = ['svg', 'gif'];
  private const MANAGER_PATH = '/image/';

  /**
   * Get image markup
   * @param string $src
   * @param string $alt
   * @param string $sizes
   * @param int $maxWidth
   * @param float $ratio
   * @param string $className
   * @param int $width
   * @param int $height
   * @param string $loading
   * @param string $decoding
   * @param string $fetchPriority
   *
   * @return string
   */
  public static function get(
    string $src,
    string $alt = '',
    string $className = '',
    string $sizes = '',
    int $maxWidth = 0,
    float $ratio = 0,
    int $width = 0,
    int $height = 0,
    array $breakPoints = ImageConfig::BREAKPOINTS,
    $loading = 'lazy',
    $decoding = 'auto',
    $fetchPriority = 'auto'
  ): string {
    $_loading = LoadingBehavior::tryFrom($loading) ?? LoadingBehavior::LAZY;
    $_decoding = DecodingBehavior::tryFrom($decoding) ?? DecodingBehavior::AUTO;
    $_fetchPriority = FetchPriorityBehavior::tryFrom($fetchPriority) ?? FetchPriorityBehavior::AUTO;
    $config = new ImageConfig(
      src: $src,
      alt: $alt,
      className: $className,
      width: $width,
      height: $height,
      sizes: $sizes,
      maxWidth: $maxWidth,
      ratio: $ratio,
      breakPoints: $breakPoints,
      loading: $_loading,
      decoding: $_decoding,
      fetchPriority: $_fetchPriority
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

    $this->config = $config;

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
    $sizes = $config->sizes ?: $this->getSizes($config->maxWidth);
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
      $html .= 'src="' . self::getPath(src: $config->src, size: $lip, ratio: $config->ratio) . '" ';
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

  public function getPath(string $src, int $size, float $ratio = 0): string
  {
    if (!in_array($size, ImageConfig::BREAKPOINTS))
      $size = ImageConfig::BREAKPOINTS[0];
    if ($ratio > 0) {
      $size .= 'x' . (int)round($size * $ratio);
    }

    return self::MANAGER_PATH . 'auto/' . $size . '/' . $src;
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
      if ($this->config->maxWidth > 0 && $size >= $this->config->maxWidth * 2) break;
      $srcset[] = self::getPath(src: $src, size: $size, ratio: $this->config->ratio) . ' ' . $size . 'w';
    }

    return implode(', ', $srcset);
  }

  /**
   * Get image sizes
   * @param int $maxWidth
   *
   * @return string
   */
  public function getSizes(int $maxWidth = 0): string
  {
    $output = [];
    $sizes = $this->breakPoints;
    array_shift($sizes);
    $maxSize = end($sizes);
    $maxWidths = $sizes;
    array_shift($maxWidths);
    $maxWidths[] = $maxSize;
    for ($i = 0; $i < count($sizes); $i++) {
      $cSize = $i === 0 ? '100vw' : $sizes[$i] . 'px';
      $output[] = '(max-width: ' . $maxWidths[$i] . 'px) ' . $cSize;
      if ($maxWidth > 0 && $maxWidth < $maxWidths[$i]) {
        $output[] = $maxWidths[$i] . 'px';
        break;
      }
    }
    // $output[] = $maxSize . 'px';
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
