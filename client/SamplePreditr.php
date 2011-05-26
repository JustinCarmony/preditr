<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'Preditr.php';

class SamplePreditr extends Preditr
{
	
	protected function __construct() 
	{
		$this->redis_host = '127.0.0.1';
		$this->redis_port = 6379;
		$this->redis_prefix = 'sample_';
		
		$this->name = 'Sample Preditr';
		
		parent::__construct();
	}
	
	/**
	 *
	 * @return SamplePreditr
	 */
	public static function GetInstance()
	{
		if(!self::$_instance)
		{
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
}