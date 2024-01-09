<?php

class rex_api_error
{

  public $error;
  public $errorcode;
  public $message;
  public $timestamp;
  private $status;
  private $contenttype;

  function __construct()
  {

    $this->error = false;
    $this->status = 500;
    $this->timestamp = time();
    $this->contenttype = 'application/json';
  }

  public function setErrorCode($code)
  {
    $this->errorcode = $code;
    return $this;
  }

  public function setMessage($message)
  {
    if ($message) {
      $this->message = $message;
      $this->error = true;
    }
    return $this;
  }

  public function setStatus($code)
  {
    $this->status = $code;
    return $this;
  }

  public function getStatus()
  {
    return $this->status;
  }

  public function getContentType()
  {
    return $this->contenttype;
  }
}
