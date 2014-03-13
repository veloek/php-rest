<?php
/**
 * phpREST
 * https://github.com/veloek/php-rest
 *
 * Copyright (c) 2012-2013 Vegard Løkken <vegard@loekken.org>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

require_once('HttpStatus.php');

class Response {

  private $content;
  private $contentType;
  private $httpStatus;
  private $charset;

  public function Response($content = '',
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
