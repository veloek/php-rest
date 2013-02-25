<?php
require_once('rest' . DIRECTORY_SEPARATOR . 'Server.php');

/**
 * Main file for examples. Instantiates the REST server implementation
 * and registeres the services in the examples folder.
 */

$server = new Server("phpREST Example Web Services");

require_once('examples' . DIRECTORY_SEPARATOR . 'tasks.php');
require_once('examples' . DIRECTORY_SEPARATOR . 'upload_file.php');

$server->handleRequest();

?>
