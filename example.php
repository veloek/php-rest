<?php
require_once('rest/Server.php');

/**
 * example.php
 *
 * This is an example of how to use the phpREST library.
 *
 * In this example, these annotations are intruduced:
 *   - @Route
 *   - @Authenticated
 *   - @AccessLevel
 *   - @ContentType
 *
 * We create two services, one for authentication and one for
 * setting and reading secrets stored in session data.
 */

/* In this example we're using sessions to hold user login information and
 * the secrets
 */ 
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
 * The functionality is pretty basic. We look for valid user credentials
 * and store some information in session data if login is successful.
 * This session data is later on used to let the server know if the
 * user is authenticated or not and also which access level the user has.
 *
 * @Route('login')
 */
class LoginService extends Service {

  public function any($username, $password) {
    
    // Throw a bad request if username or password is missing
    if ($username === NULL || $password === NULL) {
      throw new ServiceException(HttpStatus::BAD_REQUEST, 
        'Both username and password are needed');
    }
    
    // We have two sets of username/password we check against
    if (($username == 'root' && $password == 'god')
     || ($username == 'john' && $password == 'doe')) {
      
      // Login OK, now store some information
      $_SESSION['LOGGED_IN'] = TRUE;
    
      // Root has higher access level than the rest
      if ($username == 'root') {
        $_SESSION['ACCESS_LEVEL'] = 100;
      } else {
        $_SESSION['ACCESS_LEVEL'] = 1;
      }
      
      // Give a response stating that the login was successful
      return 'Logged in as "' . $username
           . '" with access level ' . $_SESSION['ACCESS_LEVEL'];
    } else {
      
      // Throw an unauthorized if the credentials are wrong
      throw new ServiceException(HttpStatus::UNAUTHORIZED,
        'Wrong username and/or password');
    }
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
 * "secrets". Only the root user may access the post and put methods to
 * update secrets, because of the @AccessLevel annotation. Both users can
 * get the secrets.
 *
 * @Route('secrets')
 *
 * The two annotations below are set as global for all service methods
 * in this service class. If the methods have matching annotations, their
 * annotations are preferred over the globally set ones.
 *
 * @Authenticated
 * @ContentType('application/json')
 */
class SecretService extends Service {

  /**
   * Gets one secret by id or the whole list of secrets
   *
   * Example paths:
   * /secrets/0
   * /secrets?id=0
   *
   */
  public function get($id) {
    if (isset($_SESSION['SECRETS']) && is_array($_SESSION['SECRETS'])) {
      
      // If id is present and valid, return only that secret
      if ($id !== NULL) {
        if (is_numeric($id) && $id >= 0 && $id < count($_SESSION['SECRETS'])) {
          
          $id = intval($id);
          
          $ret = new stdClass;
          $ret->id = $id;
          $ret->secret = $_SESSION['SECRETS'][$id];
          
          return json_encode($ret);
        } else {
          throw new ServiceException(HttpStatus::BAD_REQUEST,
            'Invalid secret id');
        }
      } else {
        
        // Return all secrets
        $secrets = array();
        foreach ($_SESSION['SECRETS'] as $id=>$secret) {
          $obj = new stdClass;
          $obj->id = $id;
          $obj->secret = $secret;
          
          $secrets[] = $obj;
        }
        
        $ret = new stdClass;
        $ret->secrets = $secrets;
        return json_encode($ret);
      }
    } else {
      throw new ServiceException(HttpStatus::NOT_FOUND,
        'No secrets stored');
    }
  }

  /**
   * Stores a new secret
   *
   * Example paths:
   * /secrets/SuperSecret
   * /secrets?secret=SuperSecret
   * /secrets (with POST-data: {"secret": "SuperSecret"})
   *
   * @AccessLevel(3)
   * 
   */
  public function post($secret='Default secret') {
    
    // If no secrets are stored, we initialize the storage array
    if (!isset($_SESSION['SECRETS'])) {
      $_SESSION['SECRETS'] = array();
    }
    
    // Add secret to storage
    $_SESSION['SECRETS'][] = $secret;
    
    // Return newly added secret
    $ret = new stdClass;
    $ret->id = count($_SESSION['SECRETS']) - 1;
    $ret->secret = $secret;

    return json_encode($ret);
  }
  
  /**
   * Updates a currently stored secret
   * 
   * Example paths:
   * /secrets/0/UpdatedSecret
   * /secrets/0?secret=UpdatedSecret
   * /secrets?id=0&secret=UpdatedSecret
   * /secrets/0 (with POST-data: {"secret": "UpdatedSecret"})
   * /secrets (with POST-data: {"id": 0, "secret": "UpdatedSecret"})
   * 
   * @AccessLevel(3)
   * 
   */
  public function put($id, $secret='Default secret') {
    if (!isset($_SESSION['SECRETS'])) {
      throw new ServiceException(HttpStatus::NOT_FOUND,
        'No secrets stored');
    }
    
    // We must have a valid id to update the secret
    if ($id !== NULL) {
      if (is_numeric($id) && $id >= 0 && $id < count($_SESSION['SECRETS'])) {
        $id = intval($id);
        
        // Update storage
        $_SESSION['SECRETS'][$id] = $secret;
        
        // Return updated secret
        $ret = new stdClass;
        $ret->id = $id;
        $ret->secret = $secret;
        
        return json_encode($ret);
      } else {
        throw new ServiceException(HttpStatus::BAD_REQUEST,
          'Invalid secret id');
      }
    } else {
      throw new ServiceException(HttpStatus::BAD_REQUEST,
        'Missing secret id');
    }
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
