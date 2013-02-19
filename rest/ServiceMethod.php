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

class ServiceMethod extends ReflectionAnnotatedMethod {
  private $reflectionClass;
  private $requiresAuthentication;
  private $requiredAccessLevel;
  private $contentType;
  
  public function ServiceMethod(&$reflectionClass, &$reflectionMethod) {
    parent::__construct($reflectionMethod->class, $reflectionMethod->name);
    
    $this->reflectionClass = $reflectionClass;
    $this->requiresAuthentication = false;
    $this->requiredAccessLevel = 0;
    $this->contentType = 'text/plain';
    
    // Annotations
    if ($this->hasAnnotation('Authenticated')) {
      $this->requiresAuthentication = true;
    } else {
      
      // Check if service class has global settings
      if ($this->reflectionClass->hasAnnotation('Authenticated')) {
        $this->requiresAuthentication = true;
      }
    }
    
    if ($this->hasAnnotation('AccessLevel')) {
      $annotation = $this->getAnnotation('AccessLevel');
      $value = intval($annotation->value);
      if ($value) {
        $this->requiredAccessLevel = $value;
      }
    } else {
      
      // Check if service class has global settings
      if ($this->reflectionClass->hasAnnotation('AccessLevel')) {
        $annotation = $this->reflectionClass->getAnnotation('AccessLevel');
        $value = intval($annotation->value);
        if ($value) {
          $this->requiredAccessLevel = $value;
        }
      }
    }
    
    if ($this->hasAnnotation('ContentType')) {
      $annotation = $this->getAnnotation('ContentType');
      $value = $annotation->value;
      if ($value) {
        $this->contentType = $value;
      }
    } else {
      
      // Check if service class has global settings
      if ($this->reflectionClass->hasAnnotation('ContentType')) {
        $annotation = $this->reflectionClass->getAnnotation('ContentType');
        $value = $annotation->value;
        if ($value) {
          $this->contentType = $value;
        }
      }
    }
  }

  public function requiresAuthentication() {
    return $this->requiresAuthentication;
  }
  
  public function requiredAccessLevel() {
    return $this->requiredAccessLevel;
  }
  
  public function getContentType() {
    return $this->contentType;
  }
}

?>
