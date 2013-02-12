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
 
require_once('addendum/annotations.php');
require_once('ServiceMethod.php');

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
      if (strtolower($reflectionMethod->name) == 'get'
       || strtolower($reflectionMethod->name) == 'post'
       || strtolower($reflectionMethod->name) == 'put'
       || strtolower($reflectionMethod->name) == 'delete'
       || strtolower($reflectionMethod->name) == 'any') {
        
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
