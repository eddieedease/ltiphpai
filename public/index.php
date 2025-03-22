<?php

use DI\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
$config = require __DIR__ . '/../config/config.php';

$container->set('config', $config);
AppFactory::setContainer($container);

$app = AppFactory::create();

// Add routing middleware
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Routes
require __DIR__ . '/../src/routes.php';

$app->run();
