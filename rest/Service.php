<?php
require_once('addendum/annotations.php');
require_once('ServiceMethod.php');

/**
 * Service.php
 * 
 */
abstract class Service extends ReflectionAnnotatedClass {
  private $route;
  private $serviceMethods;
  
  public function Service() {
    $className = get_class($this);
    parent::__construct($className);
    $this->route = $className;
    $this->serviceMethods = array();
    
    // Annotations
    if ($this->hasAnnotation('Route')) {
      $annotation = $this->getAnnotation('Route');
      if ($annotation->value) {
        $this->route = $annotation->value;
      }
    }
    
    // Service methods
    foreach ($this->getMethods() as $reflectionMethod) {
      if ($reflectionMethod->name == 'get'
       || $reflectionMethod->name == 'post'
       || $reflectionMethod->name == 'put'
       || $reflectionMethod->name == 'any') {
        
        $this->serviceMethods[] = new ServiceMethod($reflectionMethod);
      }
    }
  }
  
  public function getRoute() {
    return $this->route;
  }
  
  public function getServiceMethods() {
    return $this->serviceMethods;
  }
}

?>
