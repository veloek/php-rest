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
ob_start();

require_once('Response.php');
require_once('Request.php');
require_once('Annotations.php');
require_once('Service.php');
require_once('ServiceException.php');
require_once('addendum'.DIRECTORY_SEPARATOR.'annotations.php');

class Server {

  private $serverName;
  private $showIndex;
  private $services;
  private $response;
  private $authenticated;
  private $accessLevel;

  public function Server($serverName='phpREST Services', $showIndex=TRUE) {
    $this->serverName = $serverName;
    $this->showIndex = $showIndex;
    $this->services = array();
    $this->response = new Response();
    $this->authenticated = false;
    $this->accessLevel = 0;
  }

  public function isAuthenticated() {
    return $this->authenticated;
  }

  public function setAuthenticated($value) {
    if (is_bool($value)) {
      $this->authenticated = $value;
    } else {
      throw new Exception('Authenticated value must be of boolean type');
    }
  }

  public function getAccessLevel() {
    return $this->accessLevel;
  }

  public function setAccessLevel($level) {
    if (is_int($level)) {
      $this->accessLevel = $level;
    } else {
      throw new Exception('Access level must be of integer type');
    }
  }

  public function addService(Service $service) {
    if ((count($service->getServiceMethods()) +
        count($service->getSubroutes())) > 0)
      $this->services[] = $service;
  }

  public function handleRequest() {
    $request = new Request($_SERVER);

    // OPTIONS is used by cors for the preflight, so it must be accepted
    if ($request->getMethod() != 'OPTIONS') {

      // See if service is specified
      if ($request->getService()) {
        $service = $this->findService($request);

        if ($service !== NULL) {

          $method = $this->findMethod($service, $request);

          if ($method !== NULL) {

            // Set method's content type
            $this->response->setContentType($method->getContentType());

            // Check for use of annotation @Authenticated
            if (!$method->requiresAuthentication()
             || ($method->requiresAuthentication() && $this->isAuthenticated())) {

              // Check for use of annotation @AccessLevel
              if ($this->getAccessLevel() >= $method->requiredAccessLevel()) {

                $result = NULL;

                // Analyze the service method and invoke it "the best way"(tm)
                $parameters = $method->getParameters();
                if (count($parameters) > 0) {

                  $data = $request->getData();

                  $paramClass = $parameters[0]->getClass();

                  /* If the method takes a special object, we try to create an
                   * object of this kind and fill it with data from the request
                   *
                   * We don't try to fit anonymous arguments to service
                   * methods that uses classes
                   */
                  if ($paramClass !== NULL) {
                    $paramClassName = $paramClass->name;

                    $requestObj = new $paramClassName();
                    $classVars = get_class_vars($paramClassName);

                    if (is_array($data)) {
                      $data = array_change_key_case($data);

                      foreach ($classVars as $attr=>$defaultVal) {
                        if (isset($data[strtolower($attr)])) {
                          $requestObj->{$attr} = $data[strtolower($attr)];
                        }
                      }
                    }

                    // Combine object and anonymous data (for subroutes)
                    $input = array_merge(array($requestObj),
                                         $request->getAnonymousData());

                    try {
                      $result = $method->invokeArgs($service, $input);
                    } catch (Exception $e) {
                      $result = $e;
                    }

                  /* If no object, we try to rearrange the request data to
                   * match paramter names in the method
                   */
                  } else {

                    // Try to make each param come in right order
                    $input = array();
                    foreach($parameters as $param) {
                      if (isset($data[strtolower($param->name)])) {
                        $input[] = $data[strtolower($param->name)];
                      } else {

                        /* Where we don't have arguments available, we put
                         * NULLs so that we can find these holes later and
                         * use anonymous arguments to fill them
                         */
                        $input[] = NULL;
                      }
                    }

                    // Try to fill in the blanks with unnamed request data
                    $data = $request->getAnonymousData();
                    foreach ($input as $key=>&$inputData) {
                      if ($inputData === NULL) {
                        $anonymous = array_shift($data);

                        if ($anonymous !== NULL) {
                          $inputData = $anonymous;
                        } else {

                          /* We are out of anonymous data to use, but perhaps
                           * the parameter is optional with a default value
                           * and we don't have to send in NULL
                           */
                          if ($parameters[$key]->isOptional()) {
                            $inputData = $parameters[$key]->getDefaultValue();
                          }
                        }
                      }
                    }

                    try {
                      $result = $method->invokeArgs($service, $input);
                    } catch (Exception $e) {
                      $result = $e;
                    }
                  }
                } else {
                  try {
                    $result = $method->invoke($service);
                  } catch (Exception $e) {
                    $result = $e;
                  }
                }

                if ($result instanceof Exception) {
                  if ($result instanceof ServiceException) {
                    $this->response->setHttpStatus($result->getCode());
                  } else {
                    $this->response->setHttpStatus(
                      HttpStatus::INTERNAL_SERVER_ERROR);
                  }

                  $this->response->setContent($result->getMessage());
                } else if ($result !== NULL) {
                  $this->response->setContent($result);
                }
              } else {
                $this->response->setHttpStatus(HttpStatus::METHOD_NOT_ALLOWED);
              }
            } else {
              $this->response->setHttpStatus(HttpStatus::UNAUTHORIZED);
            }
          } else {
            $this->response->setHttpStatus(HttpStatus::NOT_IMPLEMENTED);
          }
        } else {
          $this->response->setHttpStatus(HttpStatus::NOT_FOUND);
        }
      } else {
        if ($this->showIndex) {
          $this->response->setContentType('text/html');
          $this->response->setContent($this->createIndex());
        } else {
          $this->response->setHttpStatus(HttpStatus::NOT_FOUND);
        }
      }
    }

    $this->sendResponse();
  }

  // Find the service
  private function findService($request) {
    $found = NULL;

    foreach ($this->services as &$s) {
      if ($s->getRoute() == $request->getService()) {
        $found = $s;
        break;
      }
    }
    return $found;
  }

  // Check if the service has this method
  private function findMethod(Service $service, $request) {
    $found = NULL;
    $serviceMethods = $service->getServiceMethods();

    // Check if any subroutes are useable
    $subroutes = $service->getSubroutes();
    krsort($subroutes);

    $anonymousData = $request->getAnonymousData();
    $method = $request->getMethod();

    foreach ($subroutes as $subroute=>$subrouteMethods) {
      $routeArr = explode('/', $subroute);

      $match = TRUE;
      $placeHolders = array();
      foreach ($routeArr as $i=>$part) {
        $isPlaceholder = ((substr($part, 0, 1) === '{') &&
                          (substr($part, -1) === '}'));

        if ($isPlaceholder) $placeHolders[] = $i;

        if (@$anonymousData[$i] !== $part &&
            !(isset($anonymousData[$i]) && $isPlaceholder)) {
          $match = FALSE;
          break;
        }
      }

      if ($match) {
        $hasMethod = FALSE;
        foreach ($subroutes[$subroute] as $m) {
          if (in_array(strtolower($method), $m->getHttpMethods()) ||
              in_array('any', $m->getHttpMethods())) {
            $hasMethod = TRUE;
            break;
          }
        }

        if ($hasMethod) {
          $serviceMethods = $subroutes[$subroute];

          $tmpArr = array();
          foreach ($placeHolders as $ph) {
            $tmpArr[] = $anonymousData[$ph];
          }

          $newArr = array_slice($anonymousData, count($routeArr));

          $request->setAnonymousData(array_merge($tmpArr, $newArr));

          break;
        }
      }
    }

    $validMethods = array();
    foreach($serviceMethods as &$m) {
      if (in_array(strtolower($method), $m->getHttpMethods()) ||
          in_array('any', $m->getHttpMethods())) {
        $validMethods[] = $m;
      }
    }

    /* If more than one method for this HTTP method, find the
     * method that matches argument list best */
    if (count($validMethods) > 1) {

      /* If the validMethod takes a custom object and the
       * request has this object, that's a win */
      $data = $request->getData();

      foreach ($validMethods as $key=>$validMethod) {
        $parameters = $validMethod->getParameters();

        if (count($parameters) == 1) {
          $parameterClass = $parameters[0]->getClass();

          if ($parameterClass !== NULL) {
            $className = strtolower($parameterClass->name);

            if (isset($data[$className]) && is_array($data[$className])) {
              $found = $validMethod;
              break;
            } else {

              // No longer a valid method
              unset($validMethods[$key]);
            }
          }
        }
      }

      // Search more?
      if ($found === NULL) {

        /* If the validMethod takes arguments that matches
         * the named arguments of the request, that's also a win */

        // Number of valid methods may have changed
        if (count($validMethods) > 1) {

          $data = $request->getData();
          $anonymousData = $request->getAnonymousData();

          $numMatches = 0;
          $bestFit = NULL;

          foreach ($validMethods as $validMethod) {
            $parameters = $validMethod->getParameters();
            $cnt = 0;

            foreach ($data as $argument=>$value) {
              foreach ($parameters as $reflectionParameter)
              {
                if ($reflectionParameter->name == $argument) $cnt++;
              }
            }

            if ($cnt > $numMatches) {
              $numMatches = $cnt;
              $bestFit = $validMethod;
            }
          }

          /* If we had a method with one or more argument matches, we
           * pick that. If not we use the one with the most fitting
           * number of arguments */
          if ($bestFit !== NULL) {
            $found = $bestFit;
          } else {

            $numberOfArguments = count($data) + count($anonymousData);

            $miss = 1000;
            $bestFit = NULL;

            foreach ($validMethods as $validMethod) {
              $parameters = $validMethod->getParameters();

              $off = abs(count($parameters) - $numberOfArguments);

              if ($off < $miss) {
                $miss = $off;
                $bestFit = $validMethod;
              } else if ($off == $miss && $miss > 0) {

                /* If this validMethod has optional parameters, that
                 * may make this method preferrable */

                foreach ($parameters as $reflectionParameter) {
                  if ($reflectionParameter->isOptional()) $off--;
                }
              }

              if ($off < $miss) {
                $miss = $off;
                $bestFit = $validMethod;
              }
            }

            if ($bestFit !== NULL) {
              $found = $bestFit;
            }
          }
        }

        // If none of the methods were a great match, lets just pick one
        if ($found === NULL) {
          $found = array_shift($validMethods);
        }
      }
    } else if (count($validMethods) > 0) {
      $found = array_shift($validMethods);
    }

    return $found;
  }

  // When no route is specified, the output from this function is shown
  private function createIndex() {
    $ret = '
    <html>
      <head>
        <style>
          * {font-family: Helvetica, Arial}
          th {background-color:black}
          tr:nth-child(odd) td {background-color: #ddd;}
          td {text-align:center;}
        </style>
      </head>
      <body>
        <div style="width:500px;margin:0 auto">
          <br>
          <h2 style="text-decoration:underline;">
            Welcome to <em>' . $this->serverName . '</em>
          </h2>
          <p>The following services are available:</p>
          <table cellpadding="5" cellspacing="0" border="1">
            <tr>
              <th style="color: white">Service name</th>
              <th style="color: white">Route</th>
              <th style="background:#4DAB58">GET</th>
              <th style="background:#CC9900">POST</th>
              <th style="background:#3060F0">PUT</th>
              <th style="background:#C03000">DELETE</th>
              <th style="background:#F6358A">ANY</th>
            </tr>
    ';

    // For each service; print out it's service methods
    foreach ($this->services as $service) {
      $serviceMethods = $service->getServiceMethods();
      $httpMethods = array();
      foreach ($serviceMethods as $method) {
        foreach ($method->getHttpMethods() as $httpMethod) {
          $httpMethods[] = strtolower($httpMethod);
        }
      }
      sort($httpMethods);
      $httpMethods = array_unique($httpMethods);

      $ret .= '
            <tr>
              <td style="text-align:left;">' . $service->getName() . '</td>
              <td style="text-align:left;">
              <a href="' . $service->getRoute() . '">
              /' . $service->getRoute() . '
              </a>
              </td>
              <td>' . (in_array('get', $httpMethods) ? 'X' : '-') . '</td>
              <td>' . (in_array('post', $httpMethods) ? 'X' : '-') . '</td>
              <td>' . (in_array('put', $httpMethods) ? 'X' : '-') . '</td>
              <td>' . (in_array('delete', $httpMethods) ? 'X' : '-') . '</td>
              <td>' . (in_array('any', $httpMethods) ? 'X' : '-') . '</td>
            </tr>
      ';
    }

    $version = @file_get_contents('VERSION', TRUE);
    $ret .= '
    </table>
    <br>
    <p style="font-size:0.8em;"><em>This REST server is powered by
    <a href="https://github.com/veloek/php-rest">phpREST</a>
    ' . $version . '
    </em>
    </p>
    </div>
    ';

    return $ret;
  }

  private function sendResponse() {
    $httpStatus = $this->response->getHttpStatus();
    $httpContentType = $this->response->getContentType();
    $charset = $this->response->getCharset();
    $content = $this->response->getContent();

    $httpStatusHeader = 'HTTP/1.1 ' . $httpStatus . ' ';
    $httpStatusHeader .= HttpStatus::getMessage($httpStatus);

    header($httpStatusHeader);
    header('Content-Type: ' . $httpContentType . ';charset=' . $charset);

    // Enable cors
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Origin, Accept, Authorization');
    header('Access-Control-Allow-Credentials: true');
    if (array_key_exists('HTTP_ORIGIN', $_SERVER)) {
      header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }

    if ($httpStatus !== 200) {

      if (!$content) $content = HttpStatus::getMessage($httpStatus);

      // If content type is json, make the message json friendly
      if ($httpContentType === 'application/json') {
        echo json_encode((object)array(
            'status' => $httpStatus,
            'error' => $content
        ));
      } else {
        echo $httpStatus . ' ' . $content;
      }
    } else {
      echo $content;
    }

    ob_flush();
  }
}
