<?php
chdir('..');
require 'vendor/autoload.php';
require 'lib/SimpleView.php';
require 'lib/helpers.php';
require 'lib/config.php';

// Create a new app object with the Savant view renderer
SimpleView::$template_path = dirname(__FILE__).'/../views';

$app = new \Slim\Slim(array(
  'view' => new SimpleView()
));

require 'controllers/controller.php';

$app->run();
