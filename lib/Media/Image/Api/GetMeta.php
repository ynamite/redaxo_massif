<?php

namespace Ynamite\Massif\Media\Api;

use Exception;

use rex_api_function;
use rex_api_exception;
use rex_api_result;
use rex_media;
use rex_response;

class GetMeta extends rex_api_function
{
  private string $filename;
  protected $published = true;

  /**
   * @return rex_api_result
   * @throws rex_api_exception
   * @throws Exception
   */
  public function execute(): rex_api_result
  {

    $this->filename = rex_get('filename', 'string', '');
    if (!$this->filename) {
      throw new rex_api_exception('Filename is required');
    }
    $media = rex_media::get($this->filename);
    if (!$media) {
      throw new rex_api_exception('Media not found: ' . $this->filename);
    }
    $data = [
      'title' => $media->getTitle(),
      'updateDate' => $media->getUpdateDate(),
      'createdate' => $media->getCreateDate(),
      'fileSize' => $media->getSize(),
      'fileType' => $media->getType(),
    ];

    return $this->sendResponse($data);
  }

  /**
   * Sends the response with the given content and status.
   * @param string $content
   * @param string $status
   * 
   * @return rex_api_result
   */
  private function sendResponse(array $json, string $status = rex_response::HTTP_OK): rex_api_result
  {

    rex_response::setStatus($status);
    rex_response::sendJson($json);
    return new rex_api_result(true);
  }
}
