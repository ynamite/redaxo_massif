<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

use media_negotiator\Helper as MediaNegotiatorHelper;
use InvalidArgumentException;
use rex_file;
use rex_path;

/**
 * @property ImageConfig $config
 */
class Image extends Media
{
  public ?array $breakPoints = [];
  private const EXCLUDE_EXTENSIONS_FROM_RESIZE = ['svg', 'gif'];
  private const MANAGER_PATH = '/image/';

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

    $this->initializeMedia($src);

    // Handle loading/decoding/fetchPriority logic
    if ($loading === 'eager') {
      if ($decoding === 'auto') $decoding = 'sync';
      if ($fetchPriority === 'auto') $fetchPriority = 'high';
    } else if ($loading === 'lazy') {
      if ($decoding === 'auto') $decoding = 'async';
      if ($fetchPriority === 'auto') $fetchPriority = 'low';
    }

    $_loading = LoadingBehavior::tryFrom($loading) ?? LoadingBehavior::LAZY;
    $_decoding = DecodingBehavior::tryFrom($decoding) ?? DecodingBehavior::AUTO;
    $_fetchPriority = FetchPriorityBehavior::tryFrom($fetchPriority) ?? FetchPriorityBehavior::AUTO;

    // Create ImageConfig with proper parent constructor call
    $config = new ImageConfig(
      // ImageConfig-specific params
      ratio: $ratio,
      maxWidth: $maxWidth,
      breakPoints: $breakPoints,
      decoding: $_decoding,
      fetchPriority: $_fetchPriority,
      // MediaConfig params (from parent)
      alt: $alt,
      className: $className,
      wrapperElement: $wrapperElement ?? 'div',
      wrapperClassName: $wrapperClassName,
      width: $width,
      height: $height,
      sizes: $sizes,
      loading: $_loading,
    );

    // Set properties that aren't constructor params
    $config->rex_media = $this->rex_media;
    $config->type = MediaType::IMAGE;

    $this->config = $config;
  }

  /**
   * Static helper for quick rendering
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
   * Render image markup
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

    $html = '<' . $this->config->wrapperElement . ' class="' . implode(' ', $wrapperClassName) . '">';
    if (!$isSvg && $isLazy) {
      $html .= '<div class="absolute inset-0 bg-cover bg-center [&.loaded]:opacity-0 transition-opacity duration-300 will-change-auto [background-image:var(--lqip)]" style="--lqip: url(&quot;' . \htmlspecialchars($this->getLqip()) . '&quot;)"><div class="spinner"></div></div>';
    }

    $html .= '<img alt="' . $alt . '" ';
    if (!in_array($ext, self::EXCLUDE_EXTENSIONS_FROM_RESIZE)) {
      $html .= 'src="' . $this->getLqip() . '" ';
      $html .= 'srcset="' . $this->getSrcset() . '" ';
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
   * Get image path
   */
  public function getPath(int $width, float $ratio = 0): string
  {
    if (!in_array($width, ImageConfig::BREAKPOINTS)) {
      $width = ImageConfig::BREAKPOINTS[0];
    }
    $size = (string)$width;
    $height = 0;
    if ($ratio > 0) {
      $height = (int)round($size * $ratio);
      $size .= 'x' . $height;
    }
    $updateDate = $this->rex_media->getUpdateDate();
    if (ImageConfig::$useCDN) {
      $cdnBase = ImageConfig::$cdnBase;
      $cdnParamWidth = ImageConfig::$paramWidth;
      $cdnParamHeight = ImageConfig::$paramHeight;
      $cdnParamQuality = ImageConfig::$paramQuality;
      $cdnParamQualityValue = ImageConfig::$paramQualityValue;
      $cdnParams = 'tr:e-sharpen,' . $cdnParamWidth . $width . ',' . $cdnParamQuality;
      if ($width == ImageConfig::BREAKPOINTS[0]) {
        $cdnParams .= '16,bl-3';
      } else {
        $cdnParams .= $cdnParamQualityValue;
      }
      if ($height) {
        $cdnParams .= ',' . $cdnParamHeight . $height;
      }
      return $cdnBase . $cdnParams . '/' . $this->src . '?v=' . $updateDate;
    }
    return self::MANAGER_PATH . 'auto/' . $size . '/' . $this->src . '?v=' . $updateDate;
  }

  /**
   * Get low quality image placeholder
   */
  public function getLqip(): string
  {
    $lqipSize = $this->breakPoints[0];
    if (ImageConfig::$useCDN) {
      return $this->getPath(width: $lqipSize, ratio: $this->config->ratio);
    }
    $data = null;
    $negotiatedFormat = self::getNegotiatedFormat();
    $imagePath = $this->getPath(width: $lqipSize, ratio: $this->config->ratio);
    if ($negotiatedFormat) {
      $cachePath = rex_path::cache('addons/media_manager/' . $negotiatedFormat . '-auto/' . $this->src . '__w' . $lqipSize);
      if (is_file($cachePath)) {
        $cacheHeaderPath = $cachePath . '.header';
        $cache = rex_file::getCache($cacheHeaderPath, null);
        if ($cache) {
          $mediapath = $cache['media_path'];
          $cachetime = filemtime($cachePath);
          $filetime = filemtime($mediapath);
          if ($filetime <= $cachetime) {
            $data = base64_encode(rex_file::get($cachePath));
          }
        }
      }
      if (!$data) {
        $url = self::getAbsoluteUrl($imagePath);
        $context = stream_context_create([
          'http' => [
            'method' => 'GET',
            'header' =>   "Accept: image/avif,image/webp,image/*;q=0.8,*/*;q=0.5\r\n" .
              "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
              . "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
              "Cache-Control: no-cache\r\n",
            'timeout' => 2,
            'ignore_errors' => true,
          ],
          'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
          ]
        ]);
        $data = @file_get_contents($url, false, $context);
        if (!$data) {
          return $imagePath;
        }
        $data = base64_encode($data);
      }
      if ($data) {
        $ratio = $lqipSize / $this->getWidth();
        $height = (int)round($this->getHeight() * $ratio);
        return "data:image/svg+xml;utf8,<?xml version='1.0'?><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$lqipSize} {$height}'><filter id='b'><feGaussianBlur stdDeviation='2'/></filter><image filter='url(%23b)' href='data:image/{$negotiatedFormat};base64,{$data}' width='{$lqipSize}' height='{$height}' /></svg>";
      }
    }
    return $imagePath;
  }

  /**
   * Get image srcset
   */
  public function getSrcset(): string
  {
    $srcset = [];

    $sizes = $this->breakPoints;
    array_shift($sizes);

    foreach ($sizes as $key => $size) {
      $srcset[] = $this->getPath(width: $size, ratio: $this->config->ratio) . ' ' . $size . 'w';
      if ($this->config->maxWidth > 0 && $size >= $this->config->maxWidth * 2) break;
    }

    return implode(', ', $srcset);
  }

  /**
   * Get image sizes
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
    return implode(', ', $output);
  }

  /**
   * Get image width
   */
  public function getWidth(): int
  {
    return (int)$this->config->width ?: (int)$this->rex_media->getWidth();
  }

  /**
   * Get image height
   */
  public function getHeight(): int
  {
    return (int)$this->config->height ?: (int)$this->rex_media->getHeight();
  }

  /**
   * Get negotiated format
   */
  public static function getNegotiatedFormat(): string
  {
    $possible_types = rex_server('HTTP_ACCEPT', 'string', '');
    $types = explode(',', $possible_types);
    return MediaNegotiatorHelper::getOutputFormat($types);
  }

  public static function getNearestHeight(int $height = 0): int
  {
    $breakPoints = ImageConfig::BREAKPOINTS;
    if ($height) {
      $height = in_array($height, $breakPoints) ? $height : array_reduce($breakPoints, function ($carry, $item) use ($height) {
        if ($carry === null && $item >= $height) {
          return $item;
        }
        return $carry;
      });
      if ($height === null) {
        $height = $breakPoints[count($breakPoints) - 1];
      }
    }
    return $height;
  }
}
