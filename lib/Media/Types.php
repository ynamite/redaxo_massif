<?php

declare(strict_types=1);

namespace Ynamite\Massif\Media;

enum MediaType: string
{
  case VIDEO = 'video';
  case AUDIO = 'audio';
  case IMAGE = 'image';
  case DOCUMENT = 'document';
}
