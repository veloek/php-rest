<?php
require_once('rest'.DIRECTORY_SEPARATOR.'Server.php');

/**
 * example.php
 *
 * This is an example of how to use the phpREST library.
 *
 * We create two services, one for authentication and one for
 * setting and getting tasks stored in sessions.
 */

session_start();

/**
 * Our first service, the authentication service.
 *
 * This service offers a GET method for reading login information, a POST
 * method for authenticating and a DELETE method for logging out.
 *
 * We set the route to /auth by using the annotation @Route. If we
 * had omitted this setting, we must have used /AuthenticationService to get
 * to the correct service, as phpREST uses the class name as default
 * route.
 *
 * The funcionality is pretty basic. We look for valid user credentials
 * and store some information in session data if login is successful.
 * This session data is later on used to let the server know if the
 * user is authenticated or not and also which access level the user has.
 *
 * @Route('auth')
 */
class AuthenticationService extends Service {

  /**
   * This service method demonstrates a method with a custom name.
   * If the name is not get, post, put, delete or any, we must use
   * annotation(s) to specify the http method.
   *
   * Example paths:
   * /auth (with POST-data: {"username": "john", password": "doe"})
   * /auth/john (with POST-data: {"password": "doe"})
   *
   * @Post
   */
  public function login($username, $password) {
    
    // Throw a bad request if username or password is missing
    if ($username === NULL || $password === NULL) {
      throw new ServiceException(HttpStatus::BAD_REQUEST, 
        'Both username and password are needed');
    }
    
    // We have two sets of username/password we check against
    if (($username == 'root' && $password == 'god')
     || ($username == 'john' && $password == 'doe')) {
      
      // Login OK, now store some information
      $_SESSION['USERNAME'] = $username;
      
      // Root has higher access level than john
      if ($username == 'root') {
        $_SESSION['ACCESS_LEVEL'] = 3;
      } else {
        $_SESSION['ACCESS_LEVEL'] = 1;
      }
      
      /* Give a response stating that the login was successful by
       * calling the same method that a GET request would have reached.
       */
      return $this->getUserInformation();
    } else {
      
      // Throw an unauthorized if the credentials are wrong
      throw new ServiceException(HttpStatus::UNAUTHORIZED,
        'Wrong username and/or password');
    }
  }
  
  /**
   * Here we have the "get user information" service that only returns
   * data with no input.
   *
   * If no user is logged in, we throw a 401 Unauthorized error.
   * 
   * @Get
   */
  public function getUserInformation() {
    
    // Check if user is logged in
    if (isset($_SESSION['USERNAME'])) {
      return 'Logged in as "' . $_SESSION['USERNAME'] . '" '
           . 'with access level ' . $_SESSION['ACCESS_LEVEL'];
    } else {
      throw new ServiceException(HttpStatus::UNAUTHORIZED,
        'Not logged in');
    }
  }
  
  /**
   * This DELETE method doesn't take any input, it only removes
   * login information
   *
   * @Delete
   */
  public function logout() {
    if (isset($_SESSION['USERNAME'])) unset($_SESSION['USERNAME']);
    
    if (isset($_SESSION['ACCESS_LEVEL'])) unset($_SESSION['ACCESS_LEVEL']);
  }
  
}

/**
 * The next service we have is a service that requires an authenticated
 * user for some of the methods. We distinguish between just authenticated
 * and authenticated with higher access level for some methods.
 *
 * A sharp reader may notice that the @Authenticated annotation is not
 * really needed when @AccessLevel is set in this particular example, since
 * @AccessLevel is never set without @Authenticated also being set.
 *
 * In this service we use some other session stored data to set and access
 * tasks. Only the root user may access the delete and put methods to because
 * of the @AccessLevel annotation. Everyone may get the tasks.
 *
 * @Route('tasks')
 *
 * The annotation below are set as global for all service methods in this
 * service class. If the methods have a matching annotation, their annotation
 * have higher precedence than the globally set one and will be used instead.
 *
 * @ContentType('application/json')
 */
class TasksService extends Service {

  /**
   * Gets one task by id or the whole list of tasks if no id is supplied
   *
   * Example paths:
   * /tasks/1
   * /tasks?id=1
   */
  public function get($id) {
    if (isset($_SESSION['TASKS']) && is_array($_SESSION['TASKS'])) {
      
      // If id is present and valid, return only that task
      if ($id !== NULL) {
        if (isset($_SESSION['TASKS'][$id])) {
          
          // Return task object in json format
          return json_encode($_SESSION['TASKS'][$id]); 
        } else {
          
          // Invalid input id calls for an error message
          throw new ServiceException(HttpStatus::BAD_REQUEST,
            'Invalid task id');
        }
      } else {
        
        // No id specified, return all tasks
        $tasks = array();
        foreach ($_SESSION['TASKS'] as $task) {
          $tasks[] = $task;
        }
        
        // Return an object which contains the array of tasks
        $ret = new stdClass;
        $ret->tasks = $tasks;
        return json_encode($ret);
      }
    } else {
      
      // If we have no session data with tasks, we just return a not found
      throw new ServiceException(HttpStatus::NOT_FOUND,
        'No tasks stored');
    }
  }

  /**
   * Stores a new task
   *
   * Example paths:
   * /tasks/Do%20homework
   * /tasks?task=Do%20homework // GET params also works with POST requests!
   * /tasks (with POST-data: {"task": "Do%20homework"})
   *
   * @Authenticated
   */
  public function post($task='No content') {
    
    // If no tasks are stored, we initialize the storage array
    if (!isset($_SESSION['TASKS'])) {
      $_SESSION['TASKS'] = array();
    }
    
    // Find new id to use
    $newId = isset($_SESSION['LAST_ID']) ? $_SESSION['LAST_ID'] + 1 : 0;
    
    $taskObject = new stdClass();
    
    $taskObject->id = $newId;
    $taskObject->createdBy = $_SESSION['USERNAME'];
    $taskObject->content = $task;
    
    // Add task object to storage
    $_SESSION['TASKS'][] = $taskObject;
    
    // Store last used id
    $_SESSION['LAST_ID'] = $newId;
    
    // Return newly added task
    return json_encode($taskObject); // Return in json format
  }
  
  /**
   * Updates a currently stored task
   * 
   * Example paths:
   * /tasks/1/Updated%20task
   * /tasks/1?task=Updated%20task
   * /tasks?id=1&task=Updated%20task
   * /tasks/1 (with PUT-data: {"task": "Updated task"})
   * /tasks (with PUT-data: {"id": 1, "task": "Updated task"})
   * 
   * @Authenticated
   */
  public function put($id, $task) {
    
    // If no tasks are stored, we don't have anything to update
    if (!isset($_SESSION['TASKS'])) {
      throw new ServiceException(HttpStatus::NOT_FOUND,
        'No tasks stored yet');
    }
    
    // We must have a valid id to update the task
    if ($id !== NULL) {
      if (isset($_SESSION['TASKS'][$id])) {
        
        // Update task if input is given
        if ($task !== NULL) {
          $_SESSION['TASKS'][$id]->content = $task;
        }
        
        // Return updated task
        return json_encode($_SESSION['TASKS'][$id]);
      } else {
        throw new ServiceException(HttpStatus::BAD_REQUEST,
          'Invalid task id');
      }
    } else {
      throw new ServiceException(HttpStatus::BAD_REQUEST,
        'Missing task id');
    }
  }
  
  /**
   * Deletes a currently stored task
   *
   * Example paths:
   * /tasks/1
   * /tasks?id=1
   * /tasks (with DELETE-data: {"id": 1})
   * 
   * @Authenticated
   * @AccessLevel(3) // Only root user may delete tasks
   */
  public function delete($id) {
    
    // If no tasks are stored, we don't have anything to delete
    if (!isset($_SESSION['TASKS'])) {
      throw new ServiceException(HttpStatus::NOT_FOUND,
        'No tasks stored yet');
    }
    
    // We must have a valid id to delete the task
    if ($id !== NULL) {
      if (isset($_SESSION['TASKS'][$id])) {
        
        // Remove the task from session data
        unset($_SESSION['TASKS'][$id]);
      } else {
        throw new ServiceException(HttpStatus::BAD_REQUEST,
          'Invalid task id');
      }
    } else {
      throw new ServiceException(HttpStatus::BAD_REQUEST,
        'Missing task id');
    }
  }
}

// We create an instance of the phpREST server implementation
$server = new Server('Tasks Web Services');

/**
 * Here we set the authenticated state and access level based
 * on the information we stored in session data in the login method
 */
if (isset($_SESSION['USERNAME'])) {
   $server->setAuthenticated(true);
}
if (isset($_SESSION['ACCESS_LEVEL'])) {
   $server->setAccessLevel($_SESSION['ACCESS_LEVEL']);
}

// We must register our services with phpREST
$server->addService(new AuthenticationService());
$server->addService(new TasksService());

// Finally everything is set up and we can let phpREST handle the rest (getit?)
$server->handleRequest();

?>
