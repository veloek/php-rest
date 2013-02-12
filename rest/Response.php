<?php
require_once('HttpStatus.php');

/**
 * Response.php
 */
class Response {
  
  private $content;
  private $contentType;
  private $httpStatus;
  private $charset;

  public function __construct($content = '',
            $httpStatus = HttpStatus::OK,
            $contentType = 'text/plain',
            $charset = 'utf-8') {
    $this->content = $content;
    $this->contentType = $contentType;
    $this->httpStatus = $httpStatus;
    $this->charset = $charset;
  }

  // Getters and setters
  public function getContent() {
    return $this->content;
  }

  public function setContent($content) {
    $this->content = $content;
  }

  public function getContentType() {
    return $this->contentType;
  }

  public function setContentType($contentType) {
    $this->contentType = $contentType;
  }

  public function getHttpStatus() {
    return $this->httpStatus;
  }

  public function setHttpStatus($httpStatus) {
    $this->httpStatus = $httpStatus;
  }

  public function getCharset() {
    return $this->charset;
  }

  public function setCharset($charset) {
    $this->charset = $charset;
  }

}

?>
