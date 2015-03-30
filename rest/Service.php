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

require_once('addendum'.DIRECTORY_SEPARATOR.'annotations.php');
require_once('ServiceMethod.php');

abstract class Service extends ReflectionAnnotatedClass {
  private $route;
  private $serviceMethods;
  private $subroutes;

  public function Service() {
    $className = get_class($this);
    parent::__construct($className);
    $this->route = $className;
    $this->serviceMethods = array();
    $this->subroutes = array();

    // Annotations
    if ($this->hasAnnotation('Route')) {
      $annotation = $this->getAnnotation('Route');
      if ($annotation->value) {
        $this->route = $annotation->value;
      }
    }

    // Service methods
    foreach ($this->getMethods() as $reflectionMethod) {
      $methodName = strtolower($reflectionMethod->name);
      $httpMethods = array();

      if ($methodName == 'get'
       || $methodName == 'post'
       || $methodName == 'put'
       || $methodName == 'delete'
       || $methodName == 'any') {

        $httpMethods[] = $methodName;
      }

      // Check for http method annotations
      if ($reflectionMethod->hasAnnotation('Get')) {
        $httpMethods[] = 'get';
      }
      if ($reflectionMethod->hasAnnotation('Post')) {
        $httpMethods[] = 'post';
      }
      if ($reflectionMethod->hasAnnotation('Put')) {
        $httpMethods[] = 'put';
      }
      if ($reflectionMethod->hasAnnotation('Delete')) {
        $httpMethods[] = 'delete';
      }
      if ($reflectionMethod->hasAnnotation('Any')) {
        $httpMethods[] = 'any';
      }

      $httpMethods = array_unique($httpMethods);

      $subroute = NULL;
      if ($reflectionMethod->hasAnnotation('Subroute')) {
        $subrouteAnnotation = $reflectionMethod->getAnnotation('Subroute');
        if ($subrouteAnnotation->value) {
          $subroute = preg_replace('/{[^}]*}/', '{}', $subrouteAnnotation->value);
        }
      }

      if (count($httpMethods) > 0) {
        if ($subroute) {
          if (!isset($this->subroutes[$subroute]))
            $this->subroutes[$subroute] = array();

          $this->subroutes[$subroute][] = new ServiceMethod($this,
                                                          $reflectionMethod,
                                                          $httpMethods);
        } else {
          $this->serviceMethods[] = new ServiceMethod($this, $reflectionMethod,
                                                      $httpMethods);
        }
      }
    }

  }

  public function getRoute() {
    return $this->route;
  }

  public function getServiceMethods() {
    return $this->serviceMethods;
  }

  public function getSubroutes() {
    return $this->subroutes;
  }
}

?>
