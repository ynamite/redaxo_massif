<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

use media_negotiator\Helper as MediaNegotiatorHelper;
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
    string|null $wrapperElement = null,
    string $wrapperClassName = '',
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
    } else if ($loading === 'lazy') {
      if ($decoding === 'auto') $decoding = 'async';
    }

    $_loading = LoadingBehavior::tryFrom($loading) ?? LoadingBehavior::LAZY;
    $_decoding = DecodingBehavior::tryFrom($decoding) ?? DecodingBehavior::AUTO;
    $_fetchPriority = FetchPriorityBehavior::tryFrom($fetchPriority) ?? FetchPriorityBehavior::AUTO;

    $config = new ImageConfig();
    $config->alt = $alt ?? $config->alt;
    $config->className = $className ?? $config->className;
    $config->width = $width ?? $config->width;
    $config->height = $height ?? $config->height;
    $config->sizes = $sizes ?? $config->sizes;
    $config->maxWidth = $maxWidth ?? $config->maxWidth;
    $config->ratio = $ratio ?? $config->ratio;
    $config->breakPoints = $breakPoints ?? $config->breakPoints;
    $config->wrapperElement = $wrapperElement ?? $config->wrapperElement;
    $config->wrapperClassName = $wrapperClassName ?? $config->wrapperClassName;
    $config->loading = $_loading;
    $config->decoding = $_decoding;
    $config->fetchPriority = $_fetchPriority;

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
    string|null $wrapperElement = null,
    string $wrapperClassName = '',
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
      wrapperElement: $wrapperElement,
      wrapperClassName: $wrapperClassName,
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

    $isLazy = $this->config->loading === LoadingBehavior::LAZY;

    $url = $this->rex_media->getUrl();

    $ext = $this->rex_media->getExtension();
    $isSvg = $ext === 'svg';
    if ($ext === 'gif') {
      $url .= '?' . time();
    }

    $width = $this->getWidth();
    $height = $this->getHeight();
    $alt = $this->config->alt ?: $this->rex_media->getTitle();
    $sizes = $this->config->sizes ?: $this->getSizes($this->config->maxWidth);
    $style = [];
    $wrapperStyle = [];
    $wrapperStyle[] = $this->config->wrapperStyle ?: '--ratio: ' . $width . '/' . $height;
    $className = [];
    $className[] = $this->config->className ?: '';
    $wrapperClassName = [];
    $wrapperClassName[] = $this->config->wrapperClassName ?: 'relative bg-gray-200';

    $focuspoint = array_filter(explode(',', $this->rex_media->getValue('med_focuspoint')));
    if (!empty($focuspoint)) {
      $style[] = 'object-position: ' . $focuspoint[0] . '% ' . $focuspoint[1] . '%';
    }

    $className = array_filter($className);
    $wrapperClassName = array_filter($wrapperClassName);
    $style = array_filter($style);
    $wrapperStyle = array_filter($wrapperStyle);

    $lqipSize = $this->breakPoints[0];
    $lip = self::getPath(size: $lqipSize, ratio: $this->config->ratio);

    $html = '<' . $this->config->wrapperElement . ' class="' . implode(' ', $wrapperClassName) . '" style="' . implode('; ', $wrapperStyle) . '">';
    if (!$isSvg && $isLazy) {
      $html .= '<div class="absolute backdrop-blur-md inset-0 [&.loaded]:opacity-0 overflow-clip transition-opacity duration-300 will-change-[opacity] ' . implode(' ', $className) . '"></div>';
    }

    $html .= '<img alt="' . $alt . '" ';
    if (!in_array($ext, self::EXCLUDE_EXTENSIONS_FROM_RESIZE)) {
      $html .= 'src="' . $lip . '" ';
      $html .= 'srcset="' . $this->getSrcset($this->src) . '" ';
    } else {
      $html .= 'src="' . $url . '" ';
    }
    if ($isLazy) {
      $html .= 'onload="this.previousElementSibling.classList.add(\'loaded\');this.removeAttribute(\'onload\');setTimeout(() => this.previousElementSibling.remove(), 300)" ';
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
    $html .= '</' . $this->config->wrapperElement . '>';

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
      $output[] = '(max-width: ' . ($maxWidths[$i] - 1) . 'px) ' . $cSize;
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
    return $this->getConfig('className', '');
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
    $this->setConfig('className', $className);
  }

  /**
   * Set config value
   *
   * @param string $key
   * @param mixed $value
   * 
   * @return void
   */
  public function setConfig(string $key, mixed $value): void
  {
    if (property_exists($this->config, $key)) {
      $this->config->{$key} = $value;
    }
  }
  /**
   * Get config value
   *
   * @param string $key
   * 
   * @return mixed
   */
  public function getConfig(string $key, mixed $default = null): mixed
  {
    return $this->config->{$key} ?? $default;
  }
  /**
   * Get absolute URL
   *
   * @param string $path
   * 
   * @return string
   */
  public static function getAbsoluteUrl(string $path): string
  {
    // ensure leading slash
    if ($path[0] !== '/') {
      $path = '/' . $path;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . $path;
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
  /**
   * Get negotiated format
   *
   * @return string
   */
  public static function getNegotiatedFormat(): string
  {
    $possible_types = rex_server('HTTP_ACCEPT', 'string', '');
    $types = explode(',', $possible_types);
    return MediaNegotiatorHelper::getOutputFormat($types);
  }
}
