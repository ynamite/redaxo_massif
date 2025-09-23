<?php

namespace Ynamite\Massif\Redactor;

use FriendsOfRedaxo\MBlock\MBlock;

use rex;
use rex_view;

use Ynamite\Massif\Media;
use Ynamite\MassifSettings;

class Output
{
  private int $sliceId = 0;
  private bool|null $backend = null;
  private bool $exclusiveDetails = false;

  public function __construct(int $sliceId = 0)
  {
    $this->sliceId = $sliceId;
  }

  public function setExclusiveDetails(bool $exclusive = true): void
  {
    $this->exclusiveDetails = $exclusive;
  }

  public function setIsBackend(bool $backend = true): void
  {
    $this->backend = $backend;
  }

  public function parse(string $html): string
  {
    $html = MassifSettings\Utils::replaceStrings($html);
    $imageMaxWidth = rex_view::getJsProperties()['redactor_img_maxWidth'] ?? 1024;

    // replace all images with class "redactor-image" with massif image syntax
    $html = preg_replace_callback(
      '/<img[^>]+class=["\']?redactor-image["\']?[^>]*>/i',
      function ($matches) use ($imageMaxWidth) {
        if (preg_match('/data-filename=["\']?([^"\'>\s]+)["\']?/i', $matches[0], $filenameMatch)) {
          $filename = $filenameMatch[1];
          return Media\Image::get(src: $filename, maxWidth: $imageMaxWidth);
        }
        return $matches[0];
      },
      $html
    );

    if ($this->backend === true || rex::isBackend()) {
      $html = str_replace(['<details>'], ['<details open>'], $html);
      $html = preg_replace('/<details name="([^"]+)">/', '<details open>', $html);
      return $html;
    }

    // Add name attribute to details tag to avoid duplicate IDs
    $html = str_replace(
      ['<details>'],
      ['<details name="detail-' . $this->sliceId . '">'],
      $html
    );

    return $html;
  }

  public function parseDetails(string $value): string
  {
    $html = '';
    if (!$value) {
      return $html;
    }
    $data = MBlock::getOnlineDataArray($value);
    foreach ($data as $index => $item) {
      $summary = isset($item['summary']) ? $this->parse($item['summary']) : '';
      $content = isset($item['content']) ? $this->parse($item['content']) : '';
      if ($summary || $content) {
        $name = $this->exclusiveDetails ? $this->sliceId : $this->sliceId . '-' . ($index + 1);
        $html .= '<details name="detail-' . $name . '">';
        if ($summary) {
          $html .= '<summary>' . $summary . '</summary>';
        }
        if ($content) {
          $html .= '<div class="details-content">' . $content . '</div>';
        }
        $html .= '</details>';
      }
    }

    return $html;
  }
}
