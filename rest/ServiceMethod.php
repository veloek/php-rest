<?php
require_once('addendum/annotations.php');

/**
 * ServiceMethod.php
 * 
 */
class ServiceMethod extends ReflectionAnnotatedMethod {
  private $requiresAuthentication;
  private $requiredAccessLevel;
  
  public function ServiceMethod(&$reflectionMethod) {
    parent::__construct($reflectionMethod->class, $reflectionMethod->name);
    $this->requiresAuthentication = false;
    $this->requiredAccessLevel = 0;
    
    // Annotations
    if ($this->hasAnnotation('Authenticated')) {
      $this->requiresAuthentication = true;
    }
    
    if ($this->hasAnnotation('AccessLevel')) {
      $annotation = $this->getAnnotation('AccessLevel');
      if ($annotation->value) {
        $this->requiredAccessLevel = $annotation->value;
      }
    }
  }

  public function requiresAuthentication() {
    return $this->requiresAuthentication;
  }
  
  public function requiredAccessLevel() {
    return $this->requiredAccessLevel;
  }
}

?>
