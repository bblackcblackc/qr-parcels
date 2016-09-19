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
require_once "./service/user_class.php";
require_once "./service/tariff_class.php";

// check POST body
$oPOSTData = json_decode(file_get_contents("php://input"));

// check for parameters presence
if ((!isset($oPOSTData->modifies->tariffName)) or (!isset($oPOSTData->modifies->tariffPriceCoeff)) or 
	(!isset($oPOSTData->modifies->tariffTimeCoeff)))
	{
		DropWithBadRequest("Not enough or wrong parameters");
	}

////////////////////////

// connect to DB
$mysqli = new mysqli(DB_HOST,DB_RW_LOGIN,DB_RW_PASSWORD,DB_NAME);

// check DB connection
if ($mysqli->connect_errno)
	DropWithUnAuth();

/////////////////////////

// check if auth presence
if ((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password)))
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

$cNewTariff = new Tariff();
$iNewTariffResult = $cNewTariff->NewTariffFromParameters($mysqli, $oPOSTData->modifies->tariffName, 
					$oPOSTData->modifies->tariffPriceCoeff,
					$oPOSTData->modifies->tariffTimeCoeff);			
$mysqli->close();						

switch($iNewTariffResult)
	{
		case USER_OK:
			ReturnSuccess(array("id" => $cNewTariff->tariffID));
		case USER_NO_PARAMS:
			DropWithBadRequest("Not enough parameters");
		case USER_DB_ERROR:
			DropWithServerError("DB error");
		case USER_EXISTS:
			DropWithServerError("Tariff already exists");
	}

?>
