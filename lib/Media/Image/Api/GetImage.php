<?php

namespace Ynamite\Massif\Media\Api;

use Exception;

use rex_api_function;
use rex_api_exception;
use rex_api_result;
use rex_response;

use Ynamite\Massif\Media;

class GetImage extends rex_api_function
{
  private string $src;
  private string $alt = '';
  private string $className = '';
  private string $sizes = '';
  private int $maxWidth = 0;
  private int $width = 0;
  private int $height = 0;
  private array $breakPoints = Media\ImageConfig::BREAKPOINTS;
  private $loading = Media\LoadingBehavior::LAZY;
  private $decoding = Media\DecodingBehavior::AUTO;
  private $fetchPriority = Media\FetchPriorityBehavior::AUTO;

  /**
   * @return rex_api_result
   * @throws rex_api_exception
   * @throws Exception
   */
  public function execute(): rex_api_result
  {

    $this->src = rex_get('src', 'string', '');
    $this->alt = rex_get('alt', 'string', '');
    $this->className = rex_get('className', 'string', '');
    $this->sizes = rex_get('sizes', 'string', '');
    $this->maxWidth = rex_get('maxWidth', 'int', 0);
    $this->width = rex_get('width', 'int', 0);
    $this->height = rex_get('height', 'int', 0);
    $this->breakPoints = rex_get('breakPoints', 'array', Media\ImageConfig::BREAKPOINTS);
    $this->loading = rex_get('loading', 'string', Media\LoadingBehavior::LAZY);
    $this->decoding = rex_get('decoding', 'string', Media\DecodingBehavior::AUTO);
    $this->fetchPriority = rex_get('fetchPriority', 'string', Media\FetchPriorityBehavior::AUTO);

    $html = Media\Image::get(
      src: $this->src,
      alt: $this->alt,
      className: $this->className,
      sizes: $this->sizes,
      maxWidth: $this->maxWidth,
      width: $this->width,
      height: $this->height,
      breakPoints: $this->breakPoints,
      loading: $this->loading,
      decoding: $this->decoding,
      fetchPriority: $this->fetchPriority
    );

    return $this->sendResponse($html);
  }

  /**
   * Sends the response with the given content and status.
   * @param string $content
   * @param string $status
   * 
   * @return rex_api_result
   */
  private function sendResponse(string $content, string $status = rex_response::HTTP_OK): rex_api_result
  {

    rex_response::setStatus($status);
    exit($content);
    return new rex_api_result(true);
  }
}
