phpREST
=======

A REST implementation for simple setup of RESTful web services.

<b>A small example</b>:

```php
<?php
require_once('rest/Server.php');

/** @Route('hello') */
class HelloService extends Service {

  public function get($name) {
    $response = new Response();
    
    if ($name !== null) {
      $response->setContent('Hello ' . $name);
    } else {
      $response->setContent('Hello World');
    }
    
    return $response;
  }
  
}

$server = new Server('My Awesome Web Services');
$server->addService(new HelloService());

$server->handleRequest();
?>
```

Now, pointing your browser to ``http://<path-to-file>/hello`` should give you the message ``Hello World``. Pointing it to ``http://<path-to-file>/hello/phpREST`` should give you ``Hello phpREST``.

.htaccess
---------

phpREST depends on having a ``.htaccess`` in the root folder of your web services (the folder where example.php is). This is because we are using slashes in the url while there are noe actual folders, which requires some url rewriting. You need to enable ``mod_rewrite`` in apache (which is easy in some linux distros: ``a2enmod rewrite``) for this to work.
```
# Rewrite rule to enable use of forward slash
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-s
RewriteCond %{REQUEST_FILENAME} !-l 
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/example\.php$
RewriteRule ^(.*)$ example.php/$1

# Set index file
DirectoryIndex example.php

# Remove indexing
Options -Indexes
```
You need to change every ``example.php`` in that file to whatever you call your index file.

Annotations
-----------

You might have already noticed the ``@Route`` annotation on top of the service class in the first example. This is one of the annotations available in phpREST.

To secure your service methods, you have two annotations available. You can choose to use none, one of them or both.

<b>Authentication</b>:
```php
<?
...

/** @Authenticated */
public function post($newItem) {
  ...
}

...
?>
```
The ``@Authenticated`` annotation is a yes/no, true/false kind of test, and you set the state by calling ``$server->setAuthenticated(true)``.

<b>Access level</b>:
```php
<?
...

/** @AccessLevel(2) */
public function post($newItem) {
  ...
}

...
?>
```
The ``@AccessLevel`` annotation takes an argument defining the level, and you set the state of the request by calling ``$server->setAccessLevel(3)``.

A full example with annotations
-------------------------------

```php
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
```
Here we are using sessions to remember user authentication, but anything can be used as long as ``setAuthenticated()`` and ``setAccessLevel()`` are called.

To send data to your functions, you have several choises.

For GET requests, you can either use the familiar way of using ? and &, or you may simply add forward slashes with arguments.

<b>Example</b>:

``http://<path-to-file>/login/root/god`` is equal to ``http://<path-to-file>/login?username=root&password=god`` in our example. The latter may be preferred in some cases, simply because that one lets the client decide the order of the arguments.

Requests with a payload, such as POST, PUT and DELETE, should have the data in the body of the request, but if that is omitted the GET parameters (in form of the classic ? and &, or with forward slashes) are used.

In all cases, the "forward slash way" has the lowest precedence.

All service methods should check for NULL values for their arguments, as that is default if the parameter has no other default value.

phpREST is using [addendum](http://code.google.com/p/addendum/) to understand annotations.
