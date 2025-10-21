<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

use InvalidArgumentException;

use rex_clang;
use rex_media;

class Image
{
  public ?array $breakPoints = [];

  private string $src;
  private ImageConfig $config;
  private rex_media|null $rex_media;

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
   * @return self
   */
  public function __construct(
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
  ) {
    if ($src == '') {
      return '';
    }
    $this->src = $src;
    $this->rex_media = rex_media::get($this->src);

    if ($loading === 'eager') {
      if ($decoding === 'auto') $decoding = 'sync';
      if ($fetchPriority === 'auto') $fetchPriority = 'high';
    }

    $_loading = LoadingBehavior::tryFrom($loading) ?? LoadingBehavior::LAZY;
    $_decoding = DecodingBehavior::tryFrom($decoding) ?? DecodingBehavior::AUTO;
    $_fetchPriority = FetchPriorityBehavior::tryFrom($fetchPriority) ?? FetchPriorityBehavior::AUTO;
    $config = new ImageConfig(
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

    $this->config = $config;
    return $this;
  }

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
    if ($src == '') {
      return '';
    }

    $image = new Image(
      src: $src,
      alt: $alt,
      className: $className,
      sizes: $sizes,
      maxWidth: $maxWidth,
      ratio: $ratio,
      width: $width,
      height: $height,
      breakPoints: $breakPoints,
      loading: $loading,
      decoding: $decoding,
      fetchPriority: $fetchPriority
    );

    return $image->render();
  }

  /**
   *	render image markup
   * @param ImageConfig $config
   *
   * @return string
   */

  public function render(): string
  {
    $html = '';
    if ($this->src == '' || $this->rex_media == null) return $html;

    $this->breakPoints = array_values(array_intersect($this->config->breakPoints, ImageConfig::BREAKPOINTS));
    if (empty($this->breakPoints)) {
      throw new InvalidArgumentException('Invalid breakpoints');
    }

    $url = $this->rex_media->getUrl();

    $ext = $this->rex_media->getExtension();
    if ($ext === 'gif') {
      $url .= '?' . time();
    }

    $width = $this->getWidth();
    $height = $this->getHeight();
    $alt = $this->config->alt ?: $this->rex_media->getTitle();
    $sizes = $this->config->sizes ?: $this->getSizes($this->config->maxWidth);
    $style = [];
    $className = [];
    $className[] = $this->config->className ?: '';

    $focuspoint = array_filter(explode(',', $this->rex_media->getValue('med_focuspoint')));
    if (!empty($focuspoint)) {
      $style[] = 'object-position: ' . $focuspoint[0] . '% ' . $focuspoint[1] . '%';
    }

    $className = array_filter($className);
    $style = array_filter($style);

    $html = '<img alt="' . $alt . '" ';
    if (!in_array($ext, self::EXCLUDE_EXTENSIONS_FROM_RESIZE)) {
      $lip = $this->breakPoints[0];
      $html .= 'srcset="' . $this->getSrcset($this->src) . '" ';
      $html .= 'src="' . self::getPath(size: $lip, ratio: $this->config->ratio) . '" ';
    } else {
      $html .= 'src="' . $url . '" ';
    }
    if ($width) $html .= 'width="' . $width . '" ';
    if ($height) $html .= 'height="' . $height . '" ';
    if ($sizes) $html .= 'sizes="' . $sizes . '" ';
    if (!empty($className)) $html .= 'class="' . implode(' ', $className) . '" ';
    if (!empty($style)) $html .= 'style="' . implode('; ', $style) . '" ';
    $html .= 'loading="' . $this->config->loading->value . '" ';
    $html .= 'decoding="' . $this->config->decoding->value . '" ';
    $html .= 'fetchpriority="' . $this->config->fetchPriority->value . '" ';
    $html .= ' />';

    return $html;
  }

  /**
   *	to string
   *
   * @return string
   */
  public function __toString(): string
  {
    return $this->render();
  }
  public function getMedia(): rex_media
  {
    return $this->rex_media;
  }
  /**
   * Get image path
   *
   * @param string $src
   * @param int $size
   * 
   * @return string
   */

  public function getPath(int $size, float $ratio = 0): string
  {
    if (!in_array($size, ImageConfig::BREAKPOINTS))
      $size = ImageConfig::BREAKPOINTS[0];
    if ($ratio > 0) {
      $size .= 'x' . (int)round($size * $ratio);
    }

    return self::MANAGER_PATH . 'auto/' . $size . '/' . $this->src . '?v=' . $this->rex_media->getUpdateDate();
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
      $srcset[] = self::getPath(size: $size, ratio: $this->config->ratio) . ' ' . $size . 'w';
      if ($this->config->maxWidth > 0 && $size >= $this->config->maxWidth * 2) break;
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
   * Get image width
   *
   * @return int
   */
  public function getWidth(): int
  {
    return $this->config->width ?: $this->rex_media->getWidth();
  }
  /**
   * Get image height
   *
   * @return int
   */
  public function getHeight(): int
  {
    return $this->config->height ?: $this->rex_media->getHeight();
  }
  /**
   * Get class name
   *
   * @return string
   */
  public function getClassName(): string
  {
    return $this->config->className;
  }
  /**
   * Set class name
   *
   * @param string $className
   * 
   * @return void
   */
  public function setClassName(string $className): void
  {
    $this->config->className = $className;
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
