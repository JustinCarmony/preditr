<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if(!class_exists('Predis'))
{
	require_once 'Predis.php';
}

class Preditr
{
	protected static $_instance = null;
	
	protected $redis_host		= '127.0.0.1';
	protected $redis_port		= 6379;
	protected $redis_prefix		= 'preditr_';
	
	protected $name				= 'Default Group';
	
	protected $predis			= null;
	
	protected function __construct() 
	{
		$this->predis = new Predis_Client(array(
			'scheme' => 'tcp'
			,'host' => $this->redis_host
			,'port' => $this->redis_port
		));
		
		
	}
}
