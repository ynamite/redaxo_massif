<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

class ImageConfig extends MediaConfig
{
  public const BREAKPOINTS = [
    8,
    280,
    320,
    480,
    640,
    750,
    828,
    960,
    1080,
    1280,
    1668,
    1920,
    2048,
    2560,
    3200,
    3840,
    4480,
    5120,
    6016
  ];

  public function __construct(
    // Image-specific properties
    public ?float $ratio = 0,
    public ?int $maxWidth = 0,
    public ?array $breakPoints = self::BREAKPOINTS,
    public DecodingBehavior $decoding = DecodingBehavior::AUTO,
    public FetchPriorityBehavior $fetchPriority = FetchPriorityBehavior::AUTO,

    // Parent properties with defaults
    ?string $alt = '',
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
      'ratio' => $this->ratio,
      'maxWidth' => $this->maxWidth,
      'breakPoints' => $this->breakPoints,
      'decoding' => $this->decoding,
      'fetchPriority' => $this->fetchPriority,
    ]);
  }
}
