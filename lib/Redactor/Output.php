<?php

namespace Ynamite\Massif\Redactor;

use FriendsOfRedaxo\MBlock\MBlock;

use rex;
use rex_media;
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

    if ($this->backend === true || rex::isBackend()) {
      $html = str_replace(['<details>'], ['<details open>'], $html);
      $html = preg_replace('/<details name="([^"]+)">/', '<details open>', $html);
      return $html;
    } else {
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
      // replace anchors pointing to pdf media with styled link
      $html = preg_replace_callback(
        '/<a[^>]+href=["\']?([^"\'>\s]+\.pdf)["\']?[^>]*>(.*?)<\/a>/is',
        function ($matches) {
          $href = $matches[1];
          $text = strip_tags($matches[2]);
          $media = rex_media::get(basename($href));
          if ($media) {
            return '<p class="print-pdf">
            <a href="' . $href . '" target="_blank" class="icon-link">
              <i class="text-accent iconify fa-solid--file-pdf"></i>
              <span><span class="label">' . $text . ' PDF speichern</span></span>
            </a>
            </p>';
          }

          return $matches[0];
        },
        $html
      );
      // parse URLs starting with www. or http(s):// that include an actual URL, that are not already wrapped in an anchor tag (add target="_blank" and rel="noopener")
      $html = preg_replace_callback(
        '/(?<!href=["\'])(https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:\/[^\s<]*)?|www\.[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:\/[^\s<]*)?)(?![^<]*<\/a>)/i',
        function ($matches) {
          $url = $matches[1];
          $href = preg_match('/^https?:\/\//i', $url) ? $url : 'http://' . $url;
          return '<a href="' . $href . '" target="_blank" rel="noopener">' . $url . '</a>';
        },
        $html
      );
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
