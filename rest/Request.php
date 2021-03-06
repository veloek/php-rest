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

class Request {

  private $method;
  private $service;
  private $data;
  private $anonymousData;

  public function Request($phpRequest) {

    $this->method = @$_SERVER['REQUEST_METHOD'] ? $_SERVER['REQUEST_METHOD'] : '';

    $pathInfo = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : (!empty($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : '');

    // Get pathInfo from query param if necessary (fast cgi)
    if ($pathInfo === '' && count($_GET) > 0) {
      $pathInfo = array_shift(@array_keys($_GET));
    }

    $requestPath = explode("/", substr($pathInfo, 1));
    $this->service = @$requestPath[0] ? $requestPath[0] : '';

    $this->anonymousData = self::removeEmptyStrings(array_slice($requestPath, 1));

    if ($this->getMethod() == 'GET') {

      $this->data = $_GET;

    } else { // POST, PUT, DELETE

      if (@preg_match('/application\/json/i', $_SERVER['CONTENT_TYPE'])) {

        $data = json_decode(file_get_contents('php://input'), true);

      } else {

        parse_str(file_get_contents("php://input"), $data);
      }

      if (count($_FILES) > 0) {
        $data = $data ? array_merge($_FILES, $data) : $_FILES;
      }

      $this->data = $data ? array_merge($_GET, $data) : $_GET;
    }

    // Case insensitive to match better
    $this->data = array_change_key_case($this->data, CASE_LOWER);
  }

  public function getMethod() {
    return $this->method;
  }

  public function getService() {
    return $this->service;
  }

  public function getData() {
    return $this->data;
  }

  public function getAnonymousData() {
    return $this->anonymousData;
  }

  public function setAnonymousData($anonymousData) {
    $this->anonymousData = $anonymousData;
  }

  private static function removeEmptyStrings($arr) {
    if (is_array($arr)) {
      foreach ($arr as $key=>$val) {
        if ($val == '') unset($arr[$key]);
      }
    }
    return $arr;
  }

}
