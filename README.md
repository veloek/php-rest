phpREST
=======

A REST implementation for simple setup of RESTful web services.

index.php:

<?php
require_once('rest/RESTServer.php');

/** @Route('service') */
class MyService {
  public function get($name) {
    if ($name !== null) {
      echo 'Hello ' . $name;
    } else {
      echo 'Hello World';
    }
  }
}

$server = new RESTServer('My Awesome Web Services');
$server->addService(new MyService());

$server->handleRequest();
?>

Now, pointing your browser to http://<path-to-file>/example.php/service should give you the message 'Hello World'. Pointing it to http://<path-to-file>/example.php/service/phpREST should give you 'Hello phpREST'.


Annotations
-----------

You might have already noticed the @Route annotation on top of the service class. This is one of the annotations available in phpREST.

To secure your service methods, you have two annotations available. You can choose to use none, one of them or both.

Authentication:
<?
...

/** @Authenticated */
public function post($newItem) {
  ...
}

...
?>

The @Authenticated annotation is a yes/no, true/false kind of test, and you set the state by calling $server->setAuthenticated(true).

Access level:
<?
...

/** @AccessLevel(2) */
public function post($newItem) {
  ...
}

...
?>

The @AccessLevel annotation takes an argument defining the level, and you set the state of the request by calling $server->setAccessLevel(3).

A full example with annotations:

<?php
require_once('rest/RESTServer.php');

session_start();

/** @Route('login') */
class LoginService {

  public function any($username, $password) {
    $response = new Response();

    if ($username == 'root' && $password == 'god') {
      $_SESSION['LOGGED_IN'] = TRUE;
      $_SESSION['ACCESS_LEVEL'] = 100;
    } else if ($username == 'john' && $password == 'doe') {
      $_SESSION['LOGGED_IN'] = TRUE;
      $_SESSION['ACCESS_LEVEL'] = 1;
    } else {
      $response->setHttpStatus(HttpStatus::UNAUTHORIZED);
    }

    return $response;
  }
}

/** @Route('secret') */
class HelloService {

  /** @Authenticated */
  public function get() {
    $response = new Response();

    if (isset($_SESSION['SECRET'])) {
      $response->setContent($_SESSION['SECRET']);
    } else {
      $response->setContent('No secret stored');
    }

    return $response;
  }

  /** @Authenticated @AccessLevel(3) */
  public function post($secret) {
    $_SESSION['SECRET'] = $secret;

    return new Response();
  }
}

$server = new RESTServer('My Awesome Web Services');

if (isset($_SESSION['LOGGED_IN']) &&
    $_SESSION['LOGGED_IN'] === TRUE) {
   $server->setAuthenticated(true);
}

if (isset($_SESSION['ACCESS_LEVEL']) &&
    is_int($_SESSION['ACCESS_LEVEL'])) {
   $server->setAccessLevel($_SESSION['ACCESS_LEVEL']);
}

$server->addService(new LoginService());
$server->addService(new HelloService());

$server->handleRequest();
?>

Here we are using sessions to remember user authentication, but anything can be used as long as setAuthenticated and setAccessLevel are called.


phpREST is using [addendum](http://code.google.com/p/addendum/) to understand annotations.
