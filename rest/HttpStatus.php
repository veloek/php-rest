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
  
  public static function getMessage($status_code) {
    $ret = '';
    switch ($status_code) {
      case 100: $ret = 'Continue'; break;
      case 101: $ret = 'Switching Protocols'; break;
      case 200: $ret = 'OK'; break;
      case 201: $ret = 'Created'; break;
      case 202: $ret = 'Accepted'; break;
      case 203: $ret = 'Non-Authoritative Information'; break;
      case 204: $ret = 'No Content'; break;
      case 205: $ret = 'Reset Content'; break;
      case 206: $ret = 'Partial Content'; break;
      case 300: $ret = 'Multiple Choices'; break;
      case 301: $ret = 'Moved Permanently'; break;
      case 302: $ret = 'Found'; break;
      case 303: $ret = 'See Other'; break;
      case 304: $ret = 'Not Modified'; break;
      case 305: $ret = 'Use Proxy'; break;
      case 306: $ret = '(Unused)'; break;
      case 307: $ret = 'Temporary Redirect'; break;
      case 400: $ret = 'Bad Request'; break;
      case 401: $ret = 'Unauthorized'; break;
      case 402: $ret = 'Payment Required'; break;
      case 403: $ret = 'Forbidden'; break;
      case 404: $ret = 'Not Found'; break;
      case 405: $ret = 'Method Not Allowed'; break;
      case 406: $ret = 'Not Acceptable'; break;
      case 407: $ret = 'Proxy Authentication Required'; break;
      case 408: $ret = 'Request Timeout'; break;
      case 409: $ret = 'Conflict'; break;
      case 410: $ret = 'Gone'; break;
      case 411: $ret = 'Length Required'; break;
      case 412: $ret = 'Precondition Failed'; break;
      case 413: $ret = 'Request Entity Too Large'; break;
      case 414: $ret = 'Request-URI Too Long'; break;
      case 415: $ret = 'Unsupported Media Type'; break;
      case 416: $ret = 'Requested Range Not Satisfiable'; break;
      case 417: $ret = 'Expectation Failed'; break;
      case 500: $ret = 'Internal Server Error'; break;
      case 501: $ret = 'Not Implemented'; break;
      case 502: $ret = 'Bad Gateway'; break;
      case 503: $ret = 'Service Unavailable'; break;
      case 504: $ret = 'Gateway Timeout'; break;
      case 505: $ret = 'HTTP Version Not Supported'; break;
      default: $ret = 'Unknown'; break;
    }
    return $ret;
  }
}

?>
