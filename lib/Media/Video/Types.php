<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

enum VideoPreload: string
{
  case NONE = 'none';
  case METADATA = 'metadata';
  case AUTO = 'auto';
}
