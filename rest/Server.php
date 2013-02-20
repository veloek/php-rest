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
require_once('Request.php');
require_once('Annotations.php');
require_once('Service.php');
require_once('ServiceException.php');
require_once('addendum'.DIRECTORY_SEPARATOR.'annotations.php');

class Server {

  private $serverName;
  private $services;
  private $response;
  private $authenticated;
  private $accessLevel;
  
  public function Server($serverName='phpREST Services') {
    $this->serverName = $serverName;
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
    if (count($service->getServiceMethods()) > 0)
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
                    foreach ($classVars as $attr=>$defaultVal) {
                      if (isset($data[strtolower($attr)])) {
                        $requestObj->{$attr} = $data[strtolower($attr)];
                      }
                    }
                    
                    try {
                      $result = $method->invoke($service, $requestObj);
                    } catch (ServiceException $e) {
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
                    } catch (ServiceException $e) {
                      $result = $e;
                    }
                  }
                } else {
                  try {
                    $result = $method->invoke($service);
                  } catch (ServiceException $e) {
                    $result = $e;
                  }
                }
                
                if ($result instanceof Exception) {
                  if ($result instanceof ServiceException) {
                    $this->response->setHttpStatus($result->getCode());
                    $this->response->setContent($result->getMessage());
                  } else {
                    $this->response->setHttpStatus(
                      HttpStatus::INTERNAL_SERVER_ERROR);
                    $this->response->setContent($result->getMessage());
                  }
                } else {
                  $this->response->setContentType($method->getContentType());
                  
                  if ($result !== NULL) {
                    $this->response->setContent($result);
                  }
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
        $this->response->setContentType('text/html');
        $this->response->setContent($this->createIndex());
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
    
    $method = $request->getMethod();
    
    $validMethods = array();
    foreach($serviceMethods as &$m) {
      if (in_array(strtolower($method), $m->getHttpMethods())) {
        $validMethods[] = $m;
      }
    }
    
    // If no method match, look for any-methods
    if (count($validMethods) == 0) {
      foreach($serviceMethods as &$m) {
        if (in_array('any', $m->getHttpMethods())) {
          $validMethods[] = $m;
        }
      }
    }
    
    if (count($validMethods) > 0) {
      if (count($validMethods) > 1) {
        
        // Find the method that matches argument list best
        
        /* If the validMethod takes a custom object and the
         * request has this object, that's a win */
        $data = $request->getData();
        
        foreach ($validMethods as $validMethod) {
          $parameters = $validMethod->getParameters();
          
          if ((count($parameters) == 1 && $parameters[0]->getClass() !== NULL)
           && (isset($data[$parameters[0]->getClass()]))) {
            $found = $validMethod;
            break;
          }
        }
        
        // Search more?
        if ($found === NULL) {
          
          /* If the validMethod takes a number of arguments that matches
           * the named arguments of the request, that's also a win */
          $data = $request->getData();
          
          foreach ($validMethods as $validMethod) {
            $parameters = $validMethod->getParameters();
            
            if (count($parameters) == count($data)) {
              $found = $validMethod;
              break;
            }
          }
        }
        
        // Nothing yet?
        if ($found === NULL) {
          
          /* If the validMethod takes a number of arguments that matches
           * the named arguments of the request combined with the anonymous
           * arguments, that's better than nothing */
          $data = $request->getData();
          $anonymousData = $request->getAnonymousData();
        
          foreach ($validMethods as $validMethod) {
            $parameters = $validMethod->getParameters();
            
            if (count($parameters) == (count($data) + count($anonymousData))) {
              $found = $validMethod;
              break;
            }
          }
        }
        
        /* No good matches? Well, then we just take the first match and hope
         * for the best */
        if ($found === NULL) {
          $found = $validMethods[0];
        }
        
      } else {
        $found = $validMethods[0];
      }
    }
    
    return $found;
  }
  
  // When no route is specified, the output from this function is shown
  private function createIndex() {
    $ret = '
    <style>
    *{font-family: Helvetica, Arial}
    li{margin:10px 0}
    .method{width:50px;border-radius:5px;border:solid black 1px;margin:0 3px;padding: 2px;font-size: 0.9em;}
    .get{background:#4DAB58}
    .post{background:#CC9900}
    .put{background:#3060F0}
    .delete{background:#C03000}
    .any{background:#F6358A}
    </style>
    <div style="width:500px;margin:0 auto">
    <br>
    <h2 style="text-decoration:underline;">
    Welcome to <em>' . $this->serverName . '</em>
    </h2>
    <p>The following services are available:</p>
    <ul>
    ';
    
    // For each service; print out it's service methods
    foreach ($this->services as $service) {
      $ret .= '<li>/<a href="'.$service->getRoute().'">' . $service->getRoute() . '</a> ';
      
      $serviceMethods = $service->getServiceMethods();
      $methodNames = array();
      foreach ($serviceMethods as $method) {
        $methodNames[] = strtolower($method->getName());
      }
      sort($methodNames);
      $methodNames = array_unique($methodNames);
      
      foreach ($methodNames as $method) {
        $ret .= '<span class="method '.$method.'">'
              . strtoupper($method)
              . '</span>';
      }
      $ret = substr($ret, 0, -2); // Remove last ,
      
      $ret .= '</li>';
    }
    
    $ret .= '
    </ul>
    <br>
    <p style="font-size:0.8em;"><em>This REST server is powered by
    <a href="https://github.com/veloek/php-rest">phpREST</a>
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
