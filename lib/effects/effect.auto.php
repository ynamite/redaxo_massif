<?php

declare(strict_types=1);

use Ynamite\Massif\Media\ImageConfig;

class rex_effect_auto extends rex_effect_abstract
{
  const SEPARATOR = '__w';
  protected static string|null $allowedReferer = null;

  public function execute()
  {
    $filename = rex_media_manager::getMediaFile();
    [$file, $width, $format] = self::parseFilename($filename);
    $this->media->setMediaPath(rex_path::media($file));
    $this->media->setFormat($format);
  }

  public function getName()
  {
    return 'MASSIF Auto-Effekt';
  }

  public static function parseFilename($filename)
  {
    $parts = explode(self::SEPARATOR, $filename);
    $width = $parts[1];
    $filename = $parts[0];
    $format = pathinfo($filename, PATHINFO_EXTENSION);
    return [$filename, $width, $format];
  }

  public static function handle(\rex_extension_point $ep)
  {
    $breakPoints = ImageConfig::BREAKPOINTS;
    $autoSize = rex_get('rex_media_auto_size', 'int', 0);
    $effects = $ep->getSubject(); // and the effects array
    if (!$autoSize) {
      $ep->setSubject($effects);
      return;
    }
    $filename = rex_media_manager::getMediaFile();
    $type = rex_media_manager::getMediaType();
    [$file, $width, $format] = self::parseFilename($filename);
    if (!in_array($type, ['auto', 'auto-sq', 'auto-c'])) {
      return $ep->setSubject($effects);
    }
    list($width, $height) = explode('x', $width);

    if (!in_array($width, $breakPoints)) {
      $width = $breakPoints[1];
    }
    if (count($effects) < 1) {
      $effects = rex_media_manager::create('auto', $filename)->effectsFromType('auto');
      if (count($effects) < 1)
        return;
    }

    $effectsNew = [];
    $effectsNew[] = ['effect' => 'auto', 'params' => []];
    foreach ($effects as $effect) {
      if (isset($effect['params']['width'])) {
        if ($type === 'auto-sq') {
          $effect['params']['height'] = $width;
        } else {
          if ($height) {
            // find nearest breakpoint larger than height
            $height = in_array($height, $breakPoints) ? $height : array_reduce($breakPoints, function ($carry, $item) use ($height) {
              if ($carry === null && $item >= $height) {
                return $item;
              }
              return $carry;
            });
            if ($height === null) {
              $height = $breakPoints[count($breakPoints) - 1];
            }
            $effect['params']['height'] = $height;
          } elseif (
            isset($effect['params']['height']) && (int)$effect['params']['height'] > 0
          ) {
            $effect['params']['height'] = ceil((int)$effect['params']['height'] * $width / (int)$effect['params']['width']);
          }
        }
        $effect['params']['width'] = $width;
      }
      $effectsNew[] = $effect;
    }
    if ($width !== $breakPoints[0]) {
      $effectsNew = array_filter($effectsNew, function ($effect) {
        return $effect['effect'] !== 'filter_blur';
      });
    }
    $ep->setSubject($effectsNew);
  }
}
