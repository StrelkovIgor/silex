<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

require __DIR__.'/../src/Config/dev.php';
require __DIR__.'/../src/app.php';
require __DIR__.'/../src/routes.php';


$app->run();