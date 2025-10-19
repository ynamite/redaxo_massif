<?php

namespace Ynamite\Massif\Form\Api;

use Exception;

use rex_api_function;
use rex_api_exception;
use rex_api_result;
use rex_config;
use rex_clang;
use rex_path;
use rex_request;
use rex_response;
use rex_url;

use Ynamite\Massif\UploadHandler;

class Upload extends rex_api_function
{
  protected $published = true;

  protected $lang;
  protected $tmpDir;
  protected $uploadDir;
  protected $fileTypes;
  protected $minFileSize;
  protected $maxFileSize;

  /**
   * @return rex_api_result
   * @throws rex_api_exception
   * @throws Exception
   */
  public function execute()
  {

    $action = 'add';
    if (rex_request::get('preview_upload', 'string')) {
      $action = 'preview';
    } elseif (rex_request::get('download_application', 'string')) {
      $action = 'download';
    }

    $this->lang = rex_clang::getCurrentId();
    $this->tmpDir = rex_config::get('yform', 'mupload_tmp_folder', '');
    $this->uploadDir = rex_config::get('yform', 'mupload_upload_folder', '');
    $this->fileTypes = rex_config::get('yform', 'mupload_file_types', '/\.(pdf|docx?|jpe?g|png|zip)$/i');
    $this->minFileSize = rex_config::get('yform', 'mupload_min_file_size', 1);
    $this->maxFileSize = rex_config::get('yform', 'mupload_max_file_size', 15e+6);

    rex_response::cleanOutputBuffers();

    switch ($action) {
      case 'preview':
        return $this->handlePreview();
      case 'download':
        return $this->handleDownload();
      case 'add':
      default:
        return $this->handleUpload();
    }
  }

  protected function handlePreview(): null|rex_api_exception
  {
    $file = rex_request::get('file', 'string', '');
    return $this->sendFile($file);
  }

  protected function handleDownload(): null|rex_api_exception
  {
    $file = rex_request::get('file', 'string', '');
    return $this->sendFile($file, 'attachment');
  }

  protected function sendFile(string $file, string $attachment = 'inline'): void
  {
    $filePath = rex_path::data($this->tmpDir . session_id() . '/' . $file);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    if (!file_exists($filePath) || !is_file($filePath)) {
      throw new rex_api_exception('Datei nicht gefunden');
    }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . basename($file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
  }

  protected function handleUpload(): rex_api_result
  {

    $path = rex_path::data($this->tmpDir);
    $url = rex_url::media($this->tmpDir);

    $options = array(
      'delete_type' => 'DELETE',
      'script_url' => \rex_yform_value_mupload::getApiUrl(),
      'user_dirs' => true,
      'upload_dir' => $path,
      'upload_url' => '/' . $url,
      'accept_file_types' => $this->fileTypes,
      'image_file_types' => '',
      'print_response' => true,
      'max_file_size' => $this->maxFileSize,
      'min_file_size' => $this->minFileSize,
      'max_number_of_files' => 10
    );


    $error_messages = [
      'max_number_of_files' => sprogcard('uploader.maxNumberOfFiles', $this->lang),
      'accept_file_types' => sprogcard('uploader.acceptFileTypes', $this->lang),
      'max_file_size' => sprogcard('uploader.maxFileSize', $this->lang),
      'min_file_size' => sprogcard('uploader.minFileSize', $this->lang),
      'post_max_size' => sprogcard('uploader.uploadedBytes', $this->lang)
    ];

    (new UploadHandler($options, true, $error_messages));

    exit();
  }

  /**
   * Sends the response with the given content and status.
   * @param string $content
   * @param string $status
   * 
   * @return rex_api_result
   */
  // private function sendResponse(array $json, string $status = rex_response::HTTP_OK): rex_api_result
  // {

  //   rex_response::setStatus($status);
  //   rex_response::sendJson($json);
  //   exit(new rex_api_result(true));
  // }

  // protected function getParams(): array
  // {
  //   // POST Request (Standard)
  //   if (rex_request::server('REQUEST_METHOD', 'string', '') === 'POST') {
  //     $contentType = rex_request::server('CONTENT_TYPE', 'string', '');

  //     if (str_contains($contentType, 'application/json')) {
  //       $input = json_decode(file_get_contents('php://input'), true);
  //       if (json_last_error() !== JSON_ERROR_NONE) {
  //         throw new rex_api_exception('Ungültiges JSON in Request Body');
  //       }
  //       return $input;
  //     }
  //   }

  //   return [];
  // }
}
