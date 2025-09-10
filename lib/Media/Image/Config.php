<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

readonly class ImageConfig
{
  public const BREAKPOINTS = [
    16,
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
    public string $src,
    public ?string $alt = '',
    public ?int $width = 0,
    public ?int $height = 0,
    public ?int $maxWidth = 0,
    public ?string $sizes = '100vw',
    public ?array $breakPoints = ImageConfig::BREAKPOINTS,
    public ?string $className = '',
    public LoadingBehavior $loading = LoadingBehavior::LAZY,
    public DecodingBehavior $decoding = DecodingBehavior::AUTO,
    public FetchPriorityBehavior $fetchPriority = FetchPriorityBehavior::AUTO,
  ) {}
}
