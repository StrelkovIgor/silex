<?php

namespace App\Service\Auth;

class SendDriver
{
	private $domen;
	private $curl;
	
	private $header;
	private $body;
	
	public function __construct($domen){
		$this->domen = $domen;
	}
	
	public function create($query){
		$this->curl = array(
			CURLOPT_URL => $this->domen, 
			CURLOPT_POST => true,
			CURLOPT_HEADER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => $query,
			CURLOPT_CONNECTTIMEOUT => 5
		);
		return $this;
	}
	
	public function query()
	{
		$ch = curl_init();
		curl_setopt_array($ch, $this->curl);
		
		$response = curl_exec($ch);
		
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$this->header = substr($response, 0, $header_size);
		$this->body = substr($response, $header_size);
		
		curl_close($ch);
		
	}
	
	public function getHeader()
	{
		return $this->header;
	}
	
	public function getBody()
	{
		return $this->body;
	}
	
	public function getBodyArray()
	{
		return json_decode($this->body,true);
	}
	
	
}