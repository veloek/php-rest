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

require_once('Response.php');
require_once('Annotations.php');
require_once('Service.php');
require_once('addendum/annotations.php');

class Server {

  private $services;
  private $response;
  private $authenticated;
  private $accessLevel;
  
  public function __construct() {
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
    }
  }
  
  public function getAccessLevel() {
    return $this->accessLevel;
  }
  
  public function setAccessLevel($level) {
    if (is_int($level)) {
      $this->accessLevel = $level;
    }
  }

  public function addService(Service $service) {
    $this->services[] = $service;
  }

  public function handleRequest() {
    $requestedMethod = strtolower($_SERVER['REQUEST_METHOD']);
    
    // OPTIONS is used by cors for the preflight, so it must be accepted
    if ($requestedMethod != 'options') {
    
      // See if service is specified
      $requestPath = explode("/", substr(@$_SERVER['PATH_INFO'], 1));
      if (count($requestPath) > 0 && $requestPath[0] != '') {

        $requestedService = $requestPath[0];
        
        $service = $this->findService($requestedService);
          
        if ($service !== NULL) {
            
          $method = $this->findMethod($service, $requestedMethod);
          
          if ($method !== NULL) {
            
            // Check for use of annotation @Authenticated
            if (!$method->requiresAuthentication()
             || ($method->requiresAuthentication() && $this->isAuthenticated())) {
              
              // Check for use of annotation @AccessLevel
              if ($this->getAccessLevel() >= $method->requiredAccessLevel()) {
                
                $result = NULL;
                
                // Get data out of the request
                if ($requestedMethod == 'get') {
                  $data = $_GET;
                } else {
                  if (isset($_SERVER['CONTENT_TYPE']) &&
                      $_SERVER['CONTENT_TYPE'] == 'application/json') {
                      
                    $data = json_decode(file_get_contents('php://input'), true);
                  } else {
                    parse_str(file_get_contents("php://input"), $data);
                  }
                  if (count($data) == 0) $data = $_GET;
                }
                
                // If there is no payload, use url params (if any)
                if (count($data) == 0) $data = array_slice($requestPath, 1);
                
                // Analyze the service method and invoke it "the best way"(tm)
                $parameters = $method->getParameters();
                if (count($parameters) > 0) {
                  $paramClass = $parameters[0]->getClass();
                  
                  // If the method takes a special object, we try to create an
                  // object of this kind and fill it with data from the request
                  if ($paramClass !== NULL) {
                    $paramClassName = $paramClass->name;
                    
                    $requestObj = new $paramClassName();
                    $classVars = get_class_vars($paramClassName);
                    foreach ($classVars as $attr=>$defaultVal) {
                      if (isset($data[$attr])) {
                        $requestObj->{$attr} = $data[$attr];
                      }
                    }
                    
                    $result = $method->invoke($service, $requestObj);
                    
                  // If no object, we try to rearrange the request data to
                  // match paramter names in the method
                  } else {
                    
                    // Try to make each param come in right order
                    $input = array();
                    foreach($parameters as $param) {
                      if (isset($data[$param->name])) {
                        $input[] = $data[$param->name];
                        unset($data[$param->name]);
                      } else {
                        $input[] = NULL;
                      }
                    }
                    
                    // Try to fill in the blanks with unnamed request data
                    foreach ($input as $key=>&$inputData) {
                      if ($inputData === NULL) {
                        $anonymous = array_shift($data);
                        
                        if ($anonymous !== NULL) {
                          $inputData = $anonymous;
                        } else {
                          if ($parameters[$key]->isOptional()) {
                            $inputData = $parameters[$key]->getDefaultValue();
                          }
                        }
                      }
                    }
                    
                    $result = $method->invokeArgs($service, $input);
                  }
                } else {
                  $result = $method->invoke($service);
                }
                
                if ($result !== NULL && $result instanceof Response) {
                  $this->response = $result;
                } else {
                  $this->response->setHttpStatus(HttpStatus::INTERNAL_SERVER_ERROR);
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
        $this->response->setHttpStatus(HttpStatus::BAD_REQUEST);
        $this->response->setContent('No service specified');
      }
    }
      
    $this->sendResponse();
  }
  
  // Find the service
  private function findService($service) {
    $found = NULL;
    foreach ($this->services as &$s) {
      if ($s->getRoute() == $service) {
        $found = $s;
        break;
      }
    }
    return $found;
  }
  
  // Check if the service has this method
  private function findMethod(Service $service, $method) {
    $found = NULL;
    $serviceMethods = $service->getServiceMethods();
    foreach($serviceMethods as &$m) {
      if (strtolower($m->getName()) == $method) {
        $found = $m;
        break;
      }
    }
    
    // Last solution
    if ($found === NULL) {
      
      // See if the service has an "any"-method we can use instead
      foreach($serviceMethods as &$m) {
        if (strtolower($m->getName()) == 'any') {
          $found = $m;
          break;
        }
      }
    }
    return $found;
  }

  private function sendResponse() {
    $httpStatus = $this->response->getHttpStatus();
    $httpContentType = $this->response->getContentType();
    $charset = $this->response->getCharset();
    
    $httpStatusHeader = 'HTTP/1.1 ' . $httpStatus . ' ';
    $httpStatusHeader .= HttpStatus::getMessage($httpStatus);

    header($httpStatusHeader);
    header('Content-Type: ' . $httpContentType . ';charset=' . $charset);

    // Enable cors
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Origin, Accept');
    header('Access-Control-Allow-Credentials: true');
    if (array_key_exists('HTTP_ORIGIN', $_SERVER)) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }

    if ($httpStatus !== 200) {
      $str = $httpStatus
           . ' ' . HttpStatus::getMessage($httpStatus) . "\n";
      echo $str;
    }
    
    echo $this->response->getContent();
  }
}

?>
