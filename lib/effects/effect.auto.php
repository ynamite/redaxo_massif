<?php

declare(strict_types=1);


class rex_effect_auto extends rex_effect_abstract
{
  const SEPARATOR = '__w';
  protected static $sizes = [40, 272, 371, 480, 569, 767, 1163, 1559, 1999, 2499];
  protected static string|null $allowedReferer = null;
  public static $ratioCrop = 4 / 3;

  public function execute()
  {
    $filename = rex_media_manager::getMediaFile();
    [$file, $width, $format] = self::parseFilename($filename);
    $this->media->setMediaPath(rex_path::media($file));
    $this->media->setFormat($format);
  }

  /**
   * @param $sizes array
   */
  public static function setSizes($sizes)
  {
    self::$sizes = $sizes;
  }

  public static function getSizes()
  {
    return self::$sizes;
  }

  public function getName()
  {
    return 'MASSIF Auto-Effekt';
  }

  public static function parseFilename($filename)
  {
    $parts = explode(self::SEPARATOR, $filename);
    $width = (int)$parts[1];
    $filename = $parts[0];
    $format = pathinfo($filename, PATHINFO_EXTENSION);
    return [$filename, $width, $format];
  }

  public static function handle(\rex_extension_point $ep)
  {
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
    if (!in_array($width, self::$sizes)) {
      if (self::$allowedReferer === null) {
        \rex_yrewrite::init();
        self::$allowedReferer = \rex_yrewrite::getCurrentDomain()->getUrl();
      }
      $isInferSize = rex_get('inferSize', 'bool', false);
      if (!$isInferSize) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        if (empty($origin)) {
          rex_logger::factory()->log(
            'debug',
            'rex_effect_auto: Invalid request: no origin or referer found.'
          );
          exit();
        }

        if (self::$allowedReferer !== $origin) {
          rex_logger::factory()->log(
            'debug',
            'rex_effect_auto: Invalid width requested: domain: ' . $origin . ' allowed: ' . self::$allowedReferer
          );
          exit();
        }
      }
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
        } else if (isset($effect['params']['height']) && (int)$effect['params']['height'] > 0) {
          $effect['params']['height'] = ceil((int)$effect['params']['height'] * $width / (int)$effect['params']['width']);
        }
        $effect['params']['width'] = $width;
      }
      $effectsNew[] = $effect;
    }
    if ($width !== self::$sizes[0]) {
      $effectsNew = array_filter($effectsNew, function ($effect) {
        return $effect['effect'] !== 'filter_blur';
      });
    }
    $ep->setSubject($effectsNew);
  }
}
