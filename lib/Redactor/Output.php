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
    }
    // parse URLs starting with www. or http(s):// that include an actual URL, that are not already wrapped in an anchor tag (add target="_blank" and rel="noopener")
    // Split on <a> tags to avoid processing content inside them
    $parts = preg_split('/(<a\b[^>]*>.*?<\/a>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

    foreach ($parts as $i => $part) {
      // Only process parts that are NOT anchor tags (even indices)
      if ($i % 2 === 0) {
        $parts[$i] = preg_replace_callback(
          '/(https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:\/[^\s<]*)?|www\.[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:\/[^\s<]*)?)/i',
          function ($matches) {
            $url = $matches[1];
            $href = preg_match('/^https?:\/\//i', $url) ? $url : 'http://' . $url;
            return '<a href="' . $href . '" target="_blank" rel="noopener">' . $url . '</a>';
          },
          $part
        );
      }
    }

    $html = implode('', $parts);
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
          return '<span class="download-inline download-file">
            <a href="' . $href . '" target="_blank" class="icon-link"><i class="text-accent iconify fa-solid--file-pdf"></i><span><span class="label">' . $text . '</span></span></a></span>';
        } else {
          return '';
        }

        return $matches[0];
      },
      $html
    );
    // replace anchors pointing to docx and dotx media with styled link
    $html = preg_replace_callback(
      '/<a[^>]+href=["\']?([^"\'>\s]+\.(docx|dotx))["\']?[^>]*>(.*?)<\/a>/is',
      function ($matches) {
        $href = $matches[1];
        $text = strip_tags($matches[3]);
        $media = rex_media::get(basename($href));
        if ($media) {
          return '<span class="download-inline download-file">
            <a href="' . $href . '" target="_blank" class="icon-link"><i class="text-accent iconify fa-solid--file-word"></i><span><span class="label">' . $text . '</span></span></a></span>';
        } else {
          return '';
        }

        return $matches[0];
      },
      $html
    );


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
