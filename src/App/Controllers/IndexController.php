<?php

namespace App\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class IndexController
{
	public function indexAction(Request $request, Application $app){
		return $app['twig']->render('html/index.twig');
	}
}