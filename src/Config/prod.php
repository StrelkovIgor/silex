<?php

//Временая зона
date_default_timezone_set('Europe/Paris');

// Doctrine (db)
$app['db.options'] = array(
    'driver'   => 'pdo_mysql',
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'dbname'   => 'worktest',
    'user'     => 'root',
    'password' => '',
);
$app['auth.options'] = array(
	'server'	=> array(
		'http://domen.loc/service'
	),
	'keys'		=> 'Hkdt7jdKfmr1GFDhnfk0sf9'
);
$app['security_conf'] = array(
	'security.firewalls' => array(
        'admin' => array(
            'pattern' => '^/',
            'form' => array(
                'login_path' => '/login',
                'check_path' => '/login_check',
                'username_parameter' => 'form[username]',
                'password_parameter' => 'form[password]',
            ),
            'logout'  => true,
            'anonymous' => true,
            'users' => function () use ($app) {
                return new App\Models\UserModel($app['db'], $app['security.encoder.digest'], $app['auth.options']);
            },
        ),
    )
);
