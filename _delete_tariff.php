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

if (!isset($oPOSTData->data->id))
	DropWithBadRequest("No mandatory data");

// check if auth presence
if ((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password)))
	{
		DropWithUnAuth();
	}

// connect to DB
$mysqli = new mysqli(DB_HOST,DB_RW_LOGIN,DB_RW_PASSWORD,DB_NAME);

// check DB connection
if ($mysqli->connect_errno)
	DropWithServerError("DB error");

// create User object
$cUser = new User();

// trying to authenticate
$iAuth = $cUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);

if (($iAuth != USER_OK) or (!$cUser->objectOK))
	DropWithUnAuth();

// check for admin rights
if (!$cUser->isAdmin)
	DropWithForbidden();
	
$oTariff = new Tariff();

$iResult = $oTariff->TariffFromID($mysqli, $oPOSTData->data->id);

if ($iResult != USER_OK)
	DropWithNotFound();
	
$iResult = $oTariff->DeleteTariff($mysqli);

$mysqli->close();

if ($iResult == 0)
	ReturnSuccess();
else
	DropWithServerError("Cannot delete.");

?>
