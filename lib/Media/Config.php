<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

use rex_media;

class MediaConfig
{
  static bool $useCDN = false;
  static string $cdnBase = '';
  static string $paramWidth = 'w-';
  static string $paramHeight = 'h-';
  static string $paramQuality = 'q-';
  static string $paramQualityValue = '96';

  public function __construct(
    public rex_media|null $rex_media = null,
    public MediaType $type = MediaType::IMAGE,
    public ?string $className = '',
    public ?string $wrapperElement = 'div',
    public ?string $wrapperClassName = '',
    public ?int $width = 0,
    public ?int $height = 0,
    public ?string $sizes = '100vw',
    public LoadingBehavior $loading = LoadingBehavior::LAZY,
    public ?string $alt = '', // Also used as aria-label for videos
  ) {}

  public function toArray(): array
  {
    return [
      'rex_media' => $this->rex_media,
      'type' => $this->type,
      'className' => $this->className,
      'wrapperElement' => $this->wrapperElement,
      'wrapperClassName' => $this->wrapperClassName,
      'width' => $this->width,
      'height' => $this->height,
      'sizes' => $this->sizes,
      'loading' => $this->loading,
      'alt' => $this->alt,
    ];
  }
}
