<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once '../client/Predis.php';

include 'config.php';

try
{

	// Strip the URI into it's individual parts.

	$uri = str_ireplace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);

	// trim off any slashes
	$uri = trim($uri, '/');

	$parts = explode('/', $uri);

	$arguments = array();
	$method = $parts[0];

	if(count($parts) > 1)
	{
		$arguments = array_slice($parts, 1);
		
	}

	$predis = new Predis_Client($config['predis']);

	$return = call_user_func(array($predis, $method), $arguments);
}
catch(Exception $ex)
{
	header("HTTP/1.1 500 Internal Server Error");
	$obj = new stdClass();
	$obj->success = false;
	$obj->err_msg = $ex->getMessage();
	echo json_encode($obj);
	exit();
}

$obj = new stdClass();
$obj->success = true;
$obj->return = $return;

echo json_encode($obj);

exit();