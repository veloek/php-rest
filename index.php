<?php
require_once('rest' . DIRECTORY_SEPARATOR . 'Server.php');

/**
 * Main file for examples. Instantiates the REST server implementation
 * and registeres the services in the examples folder.
 *
 * To start creating you own web services, replace example services
 * with you own services and follow the given structure.
 *
 * See more at https://github.com/veloek/php-rest
 */

$server = new Server("phpREST Example Web Services");

require_once('examples' . DIRECTORY_SEPARATOR . 'tasks.php');
require_once('examples' . DIRECTORY_SEPARATOR . 'upload_file.php');
require_once('examples' . DIRECTORY_SEPARATOR . 'crowded.php');
require_once('examples' . DIRECTORY_SEPARATOR . 'subroutes.php');

$server->handleRequest();
