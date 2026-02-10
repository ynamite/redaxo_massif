<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

use InvalidArgumentException;

class Video extends Media
{
  public function __construct(
    string $src,
    string $className = '',
    string $wrapperElement = 'div',
    string $wrapperClassName = '',
    int $width = 0,
    int $height = 0,
    string $loading = 'lazy',
    string $alt = '',
    bool $autoplay = true,
    bool $muted = true,
    bool $loop = true,
    bool $controls = false,
    bool $playsinline = true,
    string $preload = 'metadata',
    ?string $poster = null,
  ) {
    $this->initializeMedia($src);

    $_loading = LoadingBehavior::tryFrom($loading) ?? LoadingBehavior::LAZY;
    $_preload = VideoPreload::tryFrom($preload) ?? VideoPreload::METADATA;

    $config = new VideoConfig();
    $config->rex_media = $this->rex_media;
    $config->type = MediaType::VIDEO;
    $config->className = $className;
    $config->wrapperElement = $wrapperElement;
    $config->wrapperClassName = $wrapperClassName;
    $config->width = $width;
    $config->height = $height;
    $config->loading = $_loading;
    $config->alt = $alt;
    $config->autoplay = $autoplay;
    $config->muted = $muted;
    $config->loop = $loop;
    $config->controls = $controls;
    $config->playsinline = $playsinline;
    $config->preload = $_preload;
    $config->poster = $poster;

    $this->config = $config;
  }

  /**
   * Static helper for quick rendering
   */
  public static function get(
    string $src,
    string $className = '',
    string $wrapperElement = 'div',
    string $wrapperClassName = '',
    int $width = 0,
    int $height = 0,
    string $loading = 'lazy',
    string $alt = '',
    bool $autoplay = false,
    bool $muted = false,
    bool $loop = false,
    bool $controls = true,
    bool $playsinline = true,
    string $preload = 'metadata',
    ?string $poster = null,
  ): string {
    if ($src == '') {
      return '';
    }

    $video = new Video(
      src: $src,
      className: $className,
      wrapperElement: $wrapperElement,
      wrapperClassName: $wrapperClassName,
      width: $width,
      height: $height,
      loading: $loading,
      alt: $alt,
      autoplay: $autoplay,
      muted: $muted,
      loop: $loop,
      controls: $controls,
      playsinline: $playsinline,
      preload: $preload,
      poster: $poster,
    );

    return $video->render();
  }

  /**
   * Render video markup
   */
  public function render(): string
  {

    if ($this->src == '' || $this->rex_media == null) return '';

    /** @var VideoConfig $config */
    $config = $this->config;

    $ext = $this->rex_media->getExtension();

    $width = $this->getWidth();
    $height = $this->getHeight();
    $alt = $this->config->alt ?: $this->rex_media->getTitle();

    $style = [];

    $className = [];
    $className[] = $this->config->className ?: '';

    $wrapperClassName = [];
    $wrapperClassName[] = $this->config->wrapperClassName ?: 'relative bg-gray-900';

    $className = array_filter($className);
    $wrapperClassName = array_filter($wrapperClassName);
    $style = array_filter($style);

    $html = '<' . $this->config->wrapperElement . ' class="' . implode(' ', $wrapperClassName) . '">';

    $html .= '<video ';
    if (!empty($className)) $html .= 'class="' . implode(' ', $className) . '" ';
    if (!empty($style)) $html .= 'style="' . implode('; ', $style) . '" ';
    if ($width) $html .= 'width="' . $width . '" ';
    if ($height) $html .= 'height="' . $height . '" ';
    if ($config->controls) $html .= 'controls ';
    if ($config->autoplay) $html .= 'autoplay ';
    if ($config->muted) $html .= 'muted ';
    if ($config->loop) $html .= 'loop ';
    if ($config->playsinline) $html .= 'playsinline ';
    $html .= 'preload="' . $config->preload->value . '" ';
    if ($config->poster) $html .= 'poster="' . $config->poster . '" ';
    if ($alt) $html .= 'aria-label="' . htmlspecialchars($alt) . '" ';
    $html .= '>';

    // Add source element(s)
    $html .= '<source src="' . $this->getPath() . '" type="video/' . $ext . '">';

    // Fallback text
    $html .= 'Your browser does not support the video tag.';
    $html .= '</video>';

    $html .= '</' . $this->config->wrapperElement . '>';

    return $html;
  }

  public function getPath(): string
  {
    $url = $this->rex_media->getUrl();
    $updateDate = $this->rex_media->getUpdateDate();
    if (MediaConfig::$useCDN) {
      $cdnBase = MediaConfig::$cdnBase;
      return $cdnBase . $this->src . '?v=' . $updateDate;
    }
    return $url . '?v=' . $updateDate;
  }


  /**
   * Set autoplay
   */
  public function setAutoplay(bool $autoplay): self
  {
    $this->setConfig('autoplay', $autoplay);
    return $this;
  }

  /**
   * Set muted
   */
  public function setMuted(bool $muted): self
  {
    $this->setConfig('muted', $muted);
    return $this;
  }

  /**
   * Set loop
   */
  public function setLoop(bool $loop): self
  {
    $this->setConfig('loop', $loop);
    return $this;
  }

  /**
   * Set controls
   */
  public function setControls(bool $controls): self
  {
    $this->setConfig('controls', $controls);
    return $this;
  }

  /**
   * Set poster
   */
  public function setPoster(string $poster): self
  {
    $this->setConfig('poster', $poster);
    return $this;
  }
}
