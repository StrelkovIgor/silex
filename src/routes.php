<?php

$app->get('/', 'App\Controllers\IndexController::indexAction')
    ->bind('homepage');

$app->mount('/', new RoutesGroup\User());