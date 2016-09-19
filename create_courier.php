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

// connect to DB
$mysqli = new mysqli(DB_HOST,DB_RW_LOGIN,DB_RW_PASSWORD,DB_NAME);

// check DB connection
if ($mysqli->connect_errno)
	DropWithUnAuth();

// create User object
$cUser = new User();

// trying to authenticate
$iAuth = $cUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);

if ($iAuth != USER_OK)
	DropWithUnAuth();

// check for admin rights
if (!$cUser->isAdmin)
	DropWithForbidden();

// check for parameters presence
if ((!isset($oPOSTData->modifies->formerlyName) or !isset($oPOSTData->modifies->password))
	or ((!isset($oPOSTData->modifies->email)) and (!isset($oPOSTData->modifies->phone))))
	{
		DropWithBadRequest("Not enough or wrong parameters");
	}

$cNewCourier = new Courier();
$iNewCourierResult = $cNewCourier->NewCourierFromParameters($mysqli, $oPOSTData->modifies->formerlyName, $oPOSTData->modifies->phone,
					$oPOSTData->modifies->email, $oPOSTData->modifies->password, $oPOSTData->modifies->maxWeight, $oPOSTData->modifies->maxHeight,
					$oPOSTData->modifies->maxLength, $oPOSTData->modifies->maxWidth, $oPOSTData->modifies->comments);
					
$mysqli->close();

switch($iNewCourierResult)
	{
		case USER_OK:
			ReturnSuccess();
		case USER_NO_PARAMS:
			DropWithBadRequest("Not enough parameters");
		case USER_DB_ERROR:
			DropWithServerError();
		case USER_EXISTS:
			DropWithServerError("Courier already exists");
	}

?>
