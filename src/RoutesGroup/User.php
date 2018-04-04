<?php 

namespace RoutesGroup;

use Silex\Application;
use Silex\Route;
use Silex\Api\ControllerProviderInterface;
use Silex\ControllerCollection;

class User implements ControllerProviderInterface
{
	public function connect(Application $app) {
		
		$route = new ControllerCollection(new Route());
		
		$route->match('/login', 'App\Controllers\UserController::loginAction')->bind('login');
		$route->match('/reg', 'App\Controllers\UserController::regAction')->bind('reg');
		$route->match('/logout', 'App\Controllers\UserController::logoutAction')->bind('logout');
		$route->post('/service', 'App\Controllers\UserController::serviceAction');
		
		return $route;
	}
}