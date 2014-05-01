<?php
chdir('..');
require 'vendor/autoload.php';
require 'lib/helpers.php';
require 'lib/config.php';

// Create a new app object with the Savant view renderer
$app = new \Slim\Slim();

require 'controllers/controller.php';

$app->run();
