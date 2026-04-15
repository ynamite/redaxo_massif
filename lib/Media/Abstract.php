<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

use InvalidArgumentException;
use rex_clang;
use rex_media;
use rex_logger;

abstract class Media
{
  static array $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];
  static array $videoExtensions = ['mp4', 'webm', 'ogv'];
  static array $audioExtensions = ['mp3', 'ogg', 'wav'];
  static array $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
  static array $archiveExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];

  protected string $src;
  protected MediaConfig $config;
  protected ?rex_media $rex_media = null;

  /**
   * Factory method to create the appropriate Media subclass with shared options
   */
  public static function factory(
    string $src,
    string $className = '',
    string $wrapperElement = 'div',
    string $wrapperClassName = '',
    int $width = 0,
    int $height = 0,
    string $sizes = '100vw',
    string $loading = 'lazy',
    string $alt = '',
  ): Media|null {
    if ($src == '') {
      return null;
    }

    $rex_media = rex_media::get($src);
    if (!$rex_media) {
      throw new InvalidArgumentException('Media not found for source: ' . $src);
    }

    $extension = strtolower($rex_media->getExtension());

    if (in_array($extension, self::$imageExtensions)) {
      return new Image(
        src: $src,
        className: $className,
        wrapperElement: $wrapperElement,
        wrapperClassName: $wrapperClassName,
        width: $width,
        height: $height,
        sizes: $sizes,
        loading: $loading,
        alt: $alt,
      );
    }

    if (in_array($extension, self::$videoExtensions)) {
      return new Video(
        src: $src,
        className: $className,
        wrapperElement: $wrapperElement,
        wrapperClassName: $wrapperClassName,
        width: $width,
        height: $height,
        loading: $loading,
        alt: $alt,
      );
    }

    throw new InvalidArgumentException('Unsupported media type: ' . $extension);
  }

  /**
   * Initialize common media properties
   */
  protected function initializeMedia(string $src): void
  {
    $this->src = $src;
    $this->rex_media = rex_media::get($src);

    if (!$this->rex_media) {
      rex_logger::logException(new InvalidArgumentException('Media not found for source: ' . $src));
    }
  }

  /**
   * Each media type must implement its own render method
   */
  abstract public function render(): string;

  /**
   * Magic method for string conversion
   */
  public function __toString(): string
  {
    return $this->render();
  }

  /**
   * Get rex_media object
   */
  public function getMedia(): ?rex_media
  {
    return $this->rex_media;
  }

  /**
   * Get class name
   */
  public function getClassName(): string
  {
    return $this->getConfig('className', '');
  }

  /**
   * Set class name
   */
  public function setClassName(string $className): self
  {
    $this->setConfig('className', $className);
    return $this;
  }

  /**
   * Get wrapper element
   */
  public function getWrapperElement(): string
  {
    return $this->getConfig('wrapperElement', 'div');
  }

  /**
   * Set wrapper element
   */
  public function setWrapperElement(string $element): self
  {
    $this->setConfig('wrapperElement', $element);
    return $this;
  }

  /**
   * Get wrapper class name
   */
  public function getWrapperClassName(): string
  {
    return $this->getConfig('wrapperClassName', '');
  }

  /**
   * Set wrapper class name
   */
  public function setWrapperClassName(string $className): self
  {
    $this->setConfig('wrapperClassName', $className);
    return $this;
  }

  /**
   * Set config value
   */
  public function setConfig(string $key, mixed $value): self
  {
    if (property_exists($this->config, $key)) {
      $this->config->{$key} = $value;
    }
    return $this;
  }

  /**
   * Get config value
   */
  public function getConfig(string $key, mixed $default = null): mixed
  {
    return $this->config->{$key} ?? $default;
  }

  /**
   * Get width
   */
  public function getWidth(): int
  {
    return (int)$this->config->width ?: (int)$this->rex_media->getWidth();
  }

  /**
   * Get height
   */
  public function getHeight(): int
  {
    return (int)$this->config->height ?: (int)$this->rex_media->getHeight();
  }

  /**
   * Get absolute URL
   */
  public static function getAbsoluteUrl(string $path): string
  {
    if ($path[0] !== '/') {
      $path = '/' . $path;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . $path;
  }

  /**
   * Get media meta info
   */
  public static function getMeta($file, $field = 'title')
  {
    if ($file = rex_media::get($file)) {
      $title = $file->getValue($field);
      if (rex_clang::getCurrentId() == 2) {
        $title = $file->getValue('med_title_2');
      }
      return $title;
    }
  }
}
