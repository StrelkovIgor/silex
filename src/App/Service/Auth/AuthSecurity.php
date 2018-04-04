<?php

namespace App\Service\Auth;

class AuthSecurity
{
	protected $config;
	
	public function __construct($config)
	{
		$this->config = $config;
	}
	
	protected function getSecretKey(array $data)
	{
		return $this->generateSecretKey($data);
	}
	
	protected function isSecretKey($key, array $data)
	{
		return $key === $this->generateSecretKey($data);
	}
	
	
	private function generateSecretKey($data)
	{
		$key = md5($this->config['keys']);
		foreach($data as $value)
		{
			$key = md5($key.$value.$this->config['keys']);
		}
		return $key;
	}
	
}