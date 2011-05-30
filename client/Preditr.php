<?php

/*
 * Preditr is a Debuging tool for Production
 * 
 * The main usecase is you have a rare(ish) bug in production that is difficult
 * to produce. Preditr allows you to dump sizable amounts of data into 
 * production when certain criteria is met without affecting the performance
 * of your production environment.
 * 
 * We like to keep things simple. On a internet HTTP request, a guid is randomly
 * generated. You can then write items to the that log, dumping information into
 * them.
 * 
 * Methods:
 * 
 * Write($desc, $arg1, etc...) - Just write to the log
 * Dump($desc, $arg1) -- Write to log, automattically write Super Variables
 * 
 * 
 * Preditr Redis Schema
 * 
 * preditr.groups.name - Hash - md5(name) => name
 * preditr.groups.last - Hash - Timestamp
 * preditr.group:md5.log - List - request_guid
 * preditr.group:md5.request:guid - List - json_string
 * 
 * Preditr Request Entry
 * 
 * Json Encoded Object
 * 
 * group - string
 * desc - string
 * time - timestamp w/ microseconds
 * data - object
 * 
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
	protected $redis_prefix		= 'preditr';
	
	protected $group			= 'Default Group';
	
	protected $guid;
	protected $requestStarted	= false;
	
	/**
	 * @var Predis_Client
	 */
	protected $predis			= null;
	
	protected function __construct() 
	{
		$this->predis = new Predis_Client(array(
			'scheme' => 'tcp'
			,'host' => $this->redis_host
			,'port' => $this->redis_port
		));
		
		
	}
	
	protected function GetGuid()
	{
		if(!$this->guid)
		{
			$this->guid = $this->GenerateGuid();
		}
		
		return $this->guid;
	}
	
	protected function GenerateGuid()
	{
		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
        return $uuid;
	}
	
	protected function Write($desc, $data)
	{
		try 
		{
	
		$obj = new stdClass();
		$obj->group = $this->group;
		$obj->desc = $desc;
		$obj->time = microtime();
		$obj->data = $data;
		
		$time = time();
		
		$md5 = md5($this->group);
		$guid = $this->GetGuid();
		
		/* @var $p Predis_CommandPipeline */
		$p = $this->predis->pipeline();
		$p->hset($this->redis_prefix.'.groups.name', $md5, $this->group);
		$p->hset($this->redis_prefix.'.groups.last', $md5, $time);
		if(!$this->requestStarted)
		{
			$p->lpush($this->redis_prefix.'.group:'.$md5.'.log', $guid);
			
			$details = array();
			$details['start'] = microtime(true);
			if(isset($_SERVER['REQUEST_URI']))
			{
				$details['url'] = $_SERVER['REQUEST_URI'];
				$details['method'] = $_SERVER['REQUEST_METHOD'];
				$details['ip'] = $_SERVER['REMOTE_HOST'];
			}
			
			$p->set($this->redis_prefix.'.group:'.$md5.'.request:'.$guid.'.details', json_encode($details));
			$this->requestStarted = true;
		}
		$p->lpush($this->redis_prefix.'.group:'.$md5.'.request:'.$guid.'.logs', json_encode($data));
		
		$p->execute();
		}
		catch(Exception $ex)
		{
			$this->HandleException($ex);
		}
	}
	
	protected function HandleException($ex)
	{
		// By Default We Do Nothing
	}


	public function Log($desc)
	{
		$num_args = func_num_args();
		$args = func_get_args();
		
		$data = array();
		
		if($num_args > 1)
		{
			foreach($args as $k => $v)
			{
				$data['arg_'.$k] = $v;
			}
		}
		
		$this->Write($desc, $data);
	}
	
	public function Dump($desc)
	{
		$num_args = func_num_args();
		$args = func_get_args();
		
		$data = array();
		
		if($num_args > 1)
		{
			foreach($args as $k => $v)
			{
				$data['arg_'.$k] = $v;
			}
		}
		
		// Set the Globals
		$data['GLOBALS'] = $GLOBALS;
		
		$this->Write($desc, $data);
	}
}
