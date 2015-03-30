<?php

/**
 * A service to demonstrate subroutes in php-rest
 *
 * @Route('subroutes')
 */
class SubroutesService extends Service {

  /** @Get @Subroute('greet') */
  public function greet($name) {
    return "Hello $name";
  }

  /** @Get @Subroute('{from}/greets') */
  public function greetFrom($from, $to) {
    return "$from says hello to $to";
  }

}

// We must register our service with phpREST
$server->addService(new SubroutesService());
