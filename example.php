<?php
require_once('rest/Server.php');

/**
 * example.php
 *
 * This is an example of how to use the phpREST library.
 *
 * In this example, three annotations are intruduced:
 *   - @Route
 *   - @Authenticated
 *   - @AccessLevel
 *
 * We create two services, one for authentication and one for
 * setting and reading a secret stored in session data.
 */

// In this example I'm using sessions to hold user login information
session_start();

/**
 * Our first service, the login service.
 *
 * This service offers an "any"-method which means any http request
 * can be used (get/post/put/delete) (though in this case only get
 * and post makes sense).
 *
 * We set the route to /login by using the annotation @Route. If we
 * had omitted this setting, we must have used /LoginService to get
 * to the correct service, as phpREST uses the class name as default
 * route.
 *
 * The funcionality is pretty basic. We look for valid user credentials
 * and store some information in session data if login is successful.
 * This session data is later on used to let the server know if the
 * user is authenticated or not and also which access level the user has.
 *
 * @Route('login')
 */
class LoginService extends Service {

  public function any($username, $password) {
    $response = new Response();
    
    if ($username !== NULL && $password !== NULL) {
      if ($username == 'root' && $password == 'god') {
        $_SESSION['LOGGED_IN'] = TRUE;
        $_SESSION['ACCESS_LEVEL'] = 100;
      } else if ($username == 'john' && $password == 'doe') {
        $_SESSION['LOGGED_IN'] = TRUE;
        $_SESSION['ACCESS_LEVEL'] = 1;
      } else {
        $response->setHttpStatus(HttpStatus::UNAUTHORIZED);
        $response->setContent('Wrong username and/or password');
      }
    } else {
      $response->setHttpStatus(HttpStatus::BAD_REQUEST);
      $response->setContent('Both username and password are needed');
    }

    return $response;
  }
}

/**
 * The next service we have is a service that requires an authenticated
 * user. We distinguish between just authenticated and authenticated with
 * access level at 3 or abow in the two methods.
 *
 * A sharp person may notice that the @Authenticated annotation is not
 * really needed when @AccessLevel is set in this particular example.
 *
 * In this service we use some other session stored data to set and access
 * a "secret". Only the root user may access the post method to update the
 * secret, because of the @AccessLevel annotation. Both users can get the
 * secret.
 *
 * @Route('secret')
 */
class SecretService extends Service {

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
  public function post($secret='Default secret') {
    $_SESSION['SECRET'] = $secret;

    return new Response('Secret set to: ' . $secret);
  }
}

// We create an instance of the phpREST implementation
$server = new Server('My Awesome Web Services');

/**
 * Here we set the authenticated state and access level based
 * on the information we stored in session data
 */
if (isset($_SESSION['LOGGED_IN']) &&
    $_SESSION['LOGGED_IN'] === TRUE) {
   $server->setAuthenticated(true);
}
if (isset($_SESSION['ACCESS_LEVEL']) &&
    is_int($_SESSION['ACCESS_LEVEL'])) {
   $server->setAccessLevel($_SESSION['ACCESS_LEVEL']);
}

// We must register our services with phpREST
$server->addService(new LoginService());
$server->addService(new SecretService());

// Finally everything is set up and we can let phpREST handle the request
$server->handleRequest();
?>
