<?php

namespace App\Service\Auth;

class Answer
{
	private static $answer = [
			'code' 		=> 200,
			'message'	=> 'The request was successful'
		];
	
	public static function set($code,$message)
	{
		self::$answer = [
			'code' 		=> $code,
			'message'	=> $message
		];
	}
	
	public static function get()
	{
		return self::$answer;
	}
}