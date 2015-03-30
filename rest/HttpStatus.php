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

class HttpStatus {
  const CONT = 100;
  const SWITCHING_PROTOCOLS = 101;
  const OK = 200;
  const CREATED = 201;
  const ACCEPTED = 202;
  const NON_AUTHORITATIVE_INFORMATION = 203;
  const NO_CONTENT = 204;
  const RESET_CONTENT = 205;
  const PARTIAL_CONTENT = 206;
  const MULTIPLE_CHOICES = 300;
  const MOVED_PERMANENTLY = 301;
  const FOUND = 302;
  const SEE_OTHER = 303;
  const NOT_MODIFIED = 304;
  const USE_PROXY = 305;
  const UNUSED = 306;
  const TEMPORARY_REDIRECT = 307;
  const BAD_REQUEST = 400;
  const UNAUTHORIZED = 401;
  const PAYMENT_REQUIRED = 402;
  const FORBIDDEN = 403;
  const NOT_FOUND = 404;
  const METHOD_NOT_ALLOWED = 405;
  const NOT_ACCEPTABLE = 406;
  const PROXY_AUTHENTICATION_REQUIRED = 407;
  const REQUEST_TIMEOUT = 408;
  const CONFLICT = 409;
  const GONE = 410;
  const LENGTH_REQUIRED = 411;
  const PRECONDITION_FAILED = 412;
  const REQUEST_ENTITY_TOO_LARGE = 413;
  const REQUEST_URI_TOO_LONG = 414;
  const UNSUPPORTED_MEDIA_TYPE = 415;
  const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
  const EXPECTATION_FAILED = 417;
  const INTERNAL_SERVER_ERROR = 500;
  const NOT_IMPLEMENTED = 501;
  const BAD_GATEWAY = 502;
  const SERVICE_UNAVAILABLE = 503;
  const GATEWAY_TIMEOUT = 504;
  const HTTP_VERSION_NOT_SUPPORTED = 505;

  public static function getMessage($statusCode) {
    switch ($statusCode) {
      case self::CONT: return 'Continue';
      case self::SWITCHING_PROTOCOLS: return 'Switching Protocols';
      case self::OK: return 'OK';
      case self::CREATED: return 'Created';
      case self::ACCEPTED: return 'Accepted';
      case self::NON_AUTHORITATIVE_INFORMATION: return 'Non-Authoritative Information';
      case self::NO_CONTENT: return 'No Content';
      case self::RESET_CONTENT: return 'Reset Content';
      case self::PARTIAL_CONTENT: return 'Partial Content';
      case self::MULTIPLE_CHOICES: return 'Multiple Choices';
      case self::MOVED_PERMANENTLY: return 'Moved Permanently';
      case self::FOUND: return 'Found';
      case self::SEE_OTHER: return 'See Other';
      case self::NOT_MODIFIED: return 'Not Modified';
      case self::USE_PROXY: return 'Use Proxy';
      case self::UNUSED: return '(Unused)';
      case self::TEMPORARY_REDIRECT: return 'Temporary Redirect';
      case self::BAD_REQUEST: return 'Bad Request';
      case self::UNAUTHORIZED: return 'Unauthorized';
      case self::PAYMENT_REQUIRED: return 'Payment Required';
      case self::FORBIDDEN: return 'Forbidden';
      case self::NOT_FOUND: return 'Not Found';
      case self::METHOD_NOT_ALLOWED: return 'Method Not Allowed';
      case self::NOT_ACCEPTABLE: return 'Not Acceptable';
      case self::PROXY_AUTHENTICATION_REQUIRED: return 'Proxy Authentication Required';
      case self::REQUEST_TIMEOUT: return 'Request Timeout';
      case self::CONFLICT: return 'Conflict';
      case self::GONE: return 'Gone';
      case self::LENGTH_REQUIRED: return 'Length Required';
      case self::PRECONDITION_FAILED: return 'Precondition Failed';
      case self::REQUEST_ENTITY_TOO_LARGE: return 'Request Entity Too Large';
      case self::REQUEST_URI_TOO_LONG: return 'Request-URI Too Long';
      case self::UNSUPPORTED_MEDIA_TYPE: return 'Unsupported Media Type';
      case self::REQUESTED_RANGE_NOT_SATISFIABLE: return 'Requested Range Not Satisfiable';
      case self::EXPECTATION_FAILED: return 'Expectation Failed';
      case self::INTERNAL_SERVER_ERROR: return 'Internal Server Error';
      case self::NOT_IMPLEMENTED: return 'Not Implemented';
      case self::BAD_GATEWAY: return 'Bad Gateway';
      case self::SERVICE_UNAVAILABLE: return 'Service Unavailable';
      case self::GATEWAY_TIMEOUT: return 'Gateway Timeout';
      case self::HTTP_VERSION_NOT_SUPPORTED: return 'HTTP Version Not Supported';
      default: return 'Unknown';
    }
  }
}
