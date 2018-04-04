<?php

namespace App\Service;

use App\Service\Auth\AuthSecurity;
use App\Service\Auth\Answer;
use App\Service\Auth\SendDriver;

class AuthService extends AuthSecurity
{
	
	public function __construct($config)
	{
		parent::__construct($config);
	}
	
	public function service($request, $userModel)
	{
		if(!$this->checkKey($request)){
			Answer::set(102,'Invalid key');
			return Answer::get();
		}
		
		$method = $request['type']."Action";
		if(!method_exists($this, $method))
		{
			Answer::set(103,'Unknown type');
			return Answer::get();
		}
		
		$this->$method($request, $userModel);
	
		return Answer::get();
	}
	
	public function sendService($query, $type)
	{
		$serverArray = [];
		$query['type'] = $type;
		$query['keys'] = $this->getSecretKey($query);
		foreach($this->config['server'] as $key => $server)
		{
			$send = new SendDriver($server);
			$send->create($query)->query();
			$serverArray[$key] = $send->getBody();
		}
		return $serverArray;
	}
	
	protected function checkKey($request)
	{
		$key = array_pop($request);
		return $this->isSecretKey($key,$request);
	}
	
	public function checkUser($username,$synchron = false)
	{
		$server = [];
		$syn = true;
		$s = $this->sendService(['username' => $username], 'userexists');
		foreach($s as $key => $value)
		{
			$code = json_decode($value,true);
			if(in_array($code['code'], [301,201]))
			{
				$server[$key] = $this->config['server'][$key];
			}
			if($synchron && is_array($code['message']) && count($s)>1 && isset($s[$key+1]))
			{
				$el1 = $code['message'];
				$el2 = json_decode($s[$key+1],true);
				$el2 = $el2['message'];
				unset($el1['id'], $el2['id']);
				$syn = $syn && ($this->getSecretKey($el1) == $this->getSecretKey($el2));
			}
		}
		
		if($synchron && count($server) && $syn)
		{
			return json_decode(array_shift($s),true);
		}
		
		return $server;
	}
	
	public function regAction($request, $userModel)
	{
		if($this->userExists($request, $userModel))
		{
			return;
		}
		
		$userModel->saveService($request);
	}
	
	public function userexistsAction($request, $userModel)
	{
		$data = $userModel->loadUserByUsername($request['username'],false);
		if($data->getId() !== null){
			Answer::set(201,[$data->getUsername(), $data->getSalt(), $data->getPassword(), $data->getMail()]);
		}else{
			Answer::set(104,'User is not found');
		}
	}
	
	public function userExists($request, $userModel)
	{
		if($userModel->isUser($request['username']))
		{
			Answer::set(301,'User exists');
			return true;
		}
		return false;
	}
	
}