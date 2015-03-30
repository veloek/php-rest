<?php

/**
 * A service to demonstrate the picking algorithm that's
 * being used when we have more than one method of same
 * HTTP method in the same service class
 *
 * @Route('crowded')
 */
class CrowdedService extends Service {

  /** @Get */
  public function oneArgument($name) {
    return "Hey " . $name;
  }

  /** @Get */
  public function oneArgumentAgain($color) {
    return "Your favourite color: " . $color;
  }

  /** @Get */
  public function twoArguments($person1, $person2) {
    return "Wattapp, " . $person1 . " " . $person2 . "?";
  }

  /** @Get */
  public function threeArguments($friend1, $friend2, $friend3) {
    return "Glad to have met you, " . $friend1 . ", " . $friend2
            . " and " . $friend3;
  }

  /** @Get */
  public function fiveArgumentsFourRequired($arg1, $arg2,$arg3, $arg4,
                                            $arg5="Optional") {
    return "The fantastic four (five): \n" . $arg1 . "\n" . $arg2 . "\n"
            . $arg3 . "\n" . $arg4 . "\n" . $arg5 . "\n";
  }

  /** @Post */
  public function oneSpecialArgument(SpecialArgument $special) {
    return "You are special " . $special->arg1;
  }

  /** @Post */
  public function oneSpecialArgumentAgain(AnotherSpecialArgument $special) {
    return "You are also special " . $special->arg1;
  }

}

class SpecialArgument {
  public $arg1;
}

class AnotherSpecialArgument {
  public $arg1;
}

// We must register our service with phpREST
$server->addService(new CrowdedService());
