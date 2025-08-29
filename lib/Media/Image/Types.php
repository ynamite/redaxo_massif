<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

enum LoadingBehavior: string
{
  case LAZY = 'lazy';
  case EAGER = 'eager';
}

enum DecodingBehavior: string
{
  case ASYNC = 'async';
  case SYNC = 'sync';
  case AUTO = 'auto';
}

enum FetchPriorityBehavior: string
{
  case HIGH = 'high';
  case LOW = 'low';
  case AUTO = 'auto';
}
