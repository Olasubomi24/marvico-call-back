<?php
//header("Content-Type:application/json; charset=utf-8");
$data = json_decode(file_get_contents('php://input'), true);

$requestMethod = $_SERVER["REQUEST_METHOD"];
$machine =  $_SERVER["REMOTE_ADDR"];

include('../class/rest.php');
$api = new rest();

switch($requestMethod) {
 	case 'POST':	        
	  echo 	$api->marvico_food_settlement_details($data); 
		break;
	default:
	header("HTTP/1.0 405 Method Not Allowed");
	break;
}
