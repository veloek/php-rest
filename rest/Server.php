<?php
require_once('Response.php');
require_once('Annotations.php');
require_once('Service.php');
require_once('addendum/annotations.php');

/**
 * Server
 * 
 * Usage:
 *  - Create a service class containing get/post/put/delete functions
 *    for your service
 *  - Add the service class to the REST implementation with addServiceClass()
 * 
 * @version 0.15
 * @author Vegard Løkken <vegard@loekken.org>
 * @copyright 2013
 */
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
        

        // Find the service
        $service = NULL;
        foreach ($this->services as &$s) {
          if ($s->getRoute() == $requestedService) {
            $service = $s;
            break;
          }
        }
          
        if ($service !== NULL) {
            
          // Check if the service has this method
          $method = NULL;
          $serviceMethods = $service->getServiceMethods();
          foreach($serviceMethods as &$m) {
            if (strtolower($m->getName()) == $requestedMethod) {
              $method = $m;
              break;
            }
          }
          
          if ($method === NULL) {
            
            // See if the service has an "any"-method we can use instead
            foreach($serviceMethods as &$m) {
              if (strtolower($m->getName()) == 'any') {
                $method = $m;
                break;
              }
            }
          }
          
          if ($method !== NULL) {
            
            if (!$method->requiresAuthentication()
             || ($method->requiresAuthentication() && $this->isAuthenticated())) {
              
              if ($this->getAccessLevel() >= $method->requiredAccessLevel()) {
            
                $result = NULL;
                if ($requestedMethod == 'get') {
                  $data = $_GET;
                } else {
                  if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
                    $data = json_decode(file_get_contents('php://input'), true);
                  } else {
                    parse_str(file_get_contents("php://input"), $data);
                  }
                  if (count($data) == 0) $data = $_GET;
                }
                
                // If there is no payload, use url params (if any)
                if (count($data) == 0) $data = array_slice($requestPath, 1);
                
                $parameters = $method->getParameters();
                if (count($parameters) > 0) {
                  $paramClass = $parameters[0]->getClass();
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
                    
                    // Try to fill in the blanks with unnamed data fields
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

  private function sendResponse() {
    $httpStatus = $this->response->getHttpStatus();
    $httpContentType = $this->response->getContentType();
    $charset = $this->response->getCharset();
    
    $httpStatusHeader = 'HTTP/1.1 ' . $httpStatus . ' ';
    $httpStatusHeader .= HttpStatus::getHttpStatusMessage($httpStatus);

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
           . ' ' . HttpStatus::getHttpStatusMessage($httpStatus) . "\n";
      echo $str;
    }
    
    echo $this->response->getContent();
  }
}

?>
