<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

class VideoConfig extends MediaConfig
{
  public function __construct(
    // Video-specific properties
    public bool $autoplay = false,
    public bool $muted = false,
    public bool $loop = false,
    public bool $controls = true,
    public bool $playsinline = true,
    public VideoPreload $preload = VideoPreload::METADATA,
    public ?string $poster = null, // Thumbnail image

    // Parent properties with defaults
    ?string $alt = '', // Used as aria-label
    ?string $className = '',
    ?string $wrapperElement = 'div',
    ?string $wrapperClassName = '',
    ?int $width = 0,
    ?int $height = 0,
    ?string $sizes = '100vw',
    LoadingBehavior $loading = LoadingBehavior::LAZY,
  ) {
    parent::__construct(
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

  public function toArray(): array
  {
    return array_merge(parent::toArray(), [
      'autoplay' => $this->autoplay,
      'muted' => $this->muted,
      'loop' => $this->loop,
      'controls' => $this->controls,
      'playsinline' => $this->playsinline,
      'preload' => $this->preload,
      'poster' => $this->poster,
    ]);
  }
}
