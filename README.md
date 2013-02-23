phpREST
=======

A REST server implementation for simple setup of RESTful web services.

<b>Demo</b><br>
To demonstrate usage of the included example service, I've created a simple frontend with HTML and JavaScript doing ajax calls to the service. You can test it [here](http://veloek.github.com/php-rest/).

<b>CORS</b><br>
The REST server implementation tries to support [cors](http://www.html5rocks.com/en/tutorials/cors/) the best way it can, by adding proper headers to allow use of credentials and preflight requests.

<b>The simplest of examples</b>

```php
<?php
require_once('rest/Server.php');

/** @Route('hello') */
class HelloService extends Service {

  public function get($name) {
    if ($name !== NULL) {
      return 'Hello ' . $name;
    } else {
      return 'Hello World';
    }
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

phpREST depends on having a ``.htaccess`` in the root folder of your web services (the folder where example.php is). This is because we are using slashes in the url while there are noe actual folders, which requires some url rewriting. You need to enable ``mod_rewrite`` in apache (which is easy in some linux distros: ``a2enmod rewrite``) for this to work. If you are using another web server than apache, I must ask you to find the solution to this part on your own.
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
<?php
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
<?php
...

/** @AccessLevel(2) */
public function post($newItem) {
  ...
}

...
?>
```
The ``@AccessLevel`` annotation takes an argument defining the level, and you set the state of the request by calling ``$server->setAccessLevel(3)``.

We also have the ``@ContentType`` annotation which you may use to set the response content type (ie. ``@ContentType('application/json')``).

All of these annotations (except @Route) may be used per service class or per service method. That means that you can specify that all service methods in a service class require authentication, just by writing it once. If a service method has the same annotation as the service class, the service method's annotation is used.

Requests with a payload, such as POST, PUT and DELETE, should have the data in the body of the request, but if that is omitted the GET parameters (in form of the classic ? and &, or with forward slashes) are used.

In all cases, the "forward slash way" has the lowest precedence.

All service methods should check for NULL values for their arguments, as that is default if the parameter has no other default value.

phpREST is using [addendum](http://code.google.com/p/addendum/) to understand annotations.
