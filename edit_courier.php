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
require_once "./service/courier_class.php";

// check POST body
$oPOSTData = json_decode(file_get_contents("php://input"));

// check if auth presence
if ((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password)))
	{
		DropWithUnAuth();
	}

// check for parameters presence
if (!isset($oPOSTData->modifies) or 
	(!isset($oPOSTData->data->email) and !isset($oPOSTData->data->phone)))
	{
		DropWithBadRequest("Not enough or wrong parameters");
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

if (($iAuth != USER_OK) or (!$cUser->objectOK))
	DropWithUnAuth();

// check for admin rights
if (!$cUser->isAdmin)
	{
		$mysqli->close();
		DropWithForbidden();
	}
else
	{
		// we have admin rights. edit what you wish.
		// try to fetch courier
		$searchCourier = new Courier();
		
		if (isset($oPOSTData->data->id))
			$iSearchResult = $searchCourier->CourierFromID($mysqli,$oPOSTData->data->id);
		else
			$iSearchResult = $searchCourier->CourierFromSearch($mysqli,$oPOSTData->data->phone, $oPOSTData->data->email);
						
		if ($iSearchResult == USER_OK)
			{
				// read parameters
				if (isset($oPOSTData->modifies->formerlyName))
					$searchCourier->courierName = $mysqli->real_escape_string($oPOSTData->modifies->formerlyName);
				
				if (isset($oPOSTData->modifies->phone))
					$searchCourier->courierPhone = intval($oPOSTData->modifies->phone);
					
				if (isset($oPOSTData->modifies->email))
					$searchCourier->courierEMail = $mysqli->real_escape_string($oPOSTData->modifies->email);
				
				if (isset($oPOSTData->modifies->comments))
					$searchCourier->courierComment = $mysqli->real_escape_string($oPOSTData->modifies->comments);
				
				if (isset($oPOSTData->modifies->maxWeight))
					$searchCourier->courierMaxWeight = floatval($oPOSTData->modifies->maxWeight);
					
				if (isset($oPOSTData->modifies->maxHeight))
					$searchCourier->courierMaxHeight = floatval($oPOSTData->modifies->maxHeight);
				
				if (isset($oPOSTData->modifies->maxLength))
					$searchCourier->courierMaxLength = floatval($oPOSTData->modifies->maxLength);
				
				if (isset($oPOSTData->modifies->maxWidth))
					$searchCourier->courierMaxWidth = floatval($oPOSTData->modifies->maxWidth);
					
				$iResult = $searchCourier->SaveCourier($mysqli);
				
				$mysqli->close();
		
				if ($iResult == USER_OK)
					ReturnSuccess();
				else
					DropWithServerError();				
			}
		else
			DropWithNotFound();
	}

?>
