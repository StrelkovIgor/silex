<?php

//Сервис провайдер
$app->register(new Silex\Provider\DoctrineServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(),['twig.path' => [__DIR__ . '/views']]);
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(),[
	'locale' => 'en',
	'locale_fallbacks' => ['en']
]);
$app->register(new Silex\Provider\SecurityServiceProvider(), $app['security_conf']);


//Модели
$app['model.user'] = function($app){
	return new App\Models\UserModel($app['db'], $app['security.default_encoder'],$app['auth.options']);
};

//Сервисы
$app['service.auth'] = function($app){
	return new App\Service\AuthService($app['auth.options']);
};
