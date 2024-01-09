<?php

class rex_api_response
{

  public $results;
  public $timestamp;
  public $success = true;
  private $status;
  private $contenttype;

  function __construct()
  {

    $this->timestamp = time();
    $this->status = 200;
    $this->contenttype = 'application/json';
  }

  public function setErrorCode($code)
  {
    $this->errorcode = $code;
    return $this;
  }

  public function setData($data)
  {
    $this->results = $data;
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

  public function setContentType($type)
  {
    $this->contenttype = $type;
    return $this;
  }

  public function getContentType()
  {
    return $this->contenttype;
  }
}
