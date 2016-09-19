<?php

/*
 * @author Anton Dovgan <blackc.blackc@gmail.com>
 * 
 * @param string	JSON in POST body with parameters
 * 
 * @return string	JSON with results
 * 
 */

require_once "./service/config.php";
require_once "./service/service.php";
require_once "./service/courier_class.php";
require_once "./service/user_class.php";

// check POST body
$oPOSTData = json_decode(file_get_contents("php://input"));

// check if auth presence
if ((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password)))
	{
		DropWithUnAuth();
	}
	
// check for parameters
if (!isset($oPOSTData->data->id) and !isset($oPOSTData->data->phone) and !isset($oPOSTData->data->email))
	DropWithServerError("No mandatory parameters");

// connect to DB
$mysqli = new mysqli(DB_HOST,DB_RW_LOGIN,DB_RW_PASSWORD,DB_NAME);

// check DB connection
if ($mysqli->connect_errno)
	DropWithUnAuth();

// create User object
$cUser = new User();

// trying to authenticate
$iAuth = $cUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);

if (($iAuth != USER_OK) or (!$cUser->objectOK))
	DropWithUnAuth();

// check for admin rights
if ($cUser->isAdmin)
	{
		$oCourier = new Courier();
		
		if (isset($oPOSTData->data->id))
				$iResult = $oCourier->CourierFromID($mysqli, $oPOSTData->data->id);
		else
				$iResult = $oCourier->CourierFromSearch($mysqli, $oPOSTData->data->phone, $oPOSTData->data->email);
		
		if ($iResult == USER_OK)
			{
				$iDelResult = $oCourier->DeleteCourier($mysqli);
				
				$mysqli->close();
				
				if ($iDelResult == USER_OK)
					ReturnSuccess();
				else
					DropWithServerError();
			}
		else
			{
				$mysqli->close();
				DropWithNotFound();	
			}
	}
else
	{
		$mysqli->close();
		DropWithForbidden();
	}
	
?>
