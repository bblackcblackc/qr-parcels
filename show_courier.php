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

// check if data enough
if (!isset($oPOSTData->data->id) and !isset($oPOSTData->data->email) 
	and !isset($oPOSTData->data->phone))
		{
			DropWithBadRequest("Not enough parameters");
		}

// check if auth and new pass presence
if (((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password))) and 
	((!isset($oPOSTData->auth->courierLogin)) or (!isset($oPOSTData->auth->courierPassword))))
	{
		DropWithUnAuth();
	}

// connect to DB
$mysqli = new mysqli(DB_HOST,DB_RW_LOGIN,DB_RW_PASSWORD,DB_NAME);

// check DB connection
if ($mysqli->connect_errno)
	DropWithServerError();

if (isset($oPOSTData->auth->courierLogin))
	{
		// auth as courier
		// create Courier object
		$cCourier = new Courier();
		
		// trying to authenticate
		$iAuth = $cCourier->CourierFromAuth($mysqli, $oPOSTData->auth->courierLogin, $oPOSTData->auth->courierPassword);
		
		if (($iAuth != USER_OK) or (!$cCourier->objectOK))
			DropWithUnAuth();
			
		$bCorrectData = false;
		if (isset($oPOSTData->data->phone) and ($oPOSTData->data->phone == $cCourier->courierPhone))
			$bCorrectData = true;
			
		if (isset($oPOSTData->data->email) and ($oPOSTData->data->email == $cCourier->courierEMail))
			$bCorrectData = true;
		
		if (isset($oPOSTData->data->id) and ($oPOSTData->data->id == $cCourier->courierID))
			$bCorrectData = true;	
		
		if ($bCorrectData)
			{
				$aResultDataset = array(
					"formerlyName" => $cCourier->courierName,
					"phone" => $cCourier->courierPhone,
					"email" => $cCourier->courierEMail,
					"id" => $cCourier->courierID,
					"maxWeight" => $cCourier->courierMaxWeight,
					"maxHeight" => $cCourier->courierMaxHeight,
					"maxLength" => $cCourier->courierMaxLength,
					"maxWidth" => $cCourier->courierMaxWidth,
					"comment" => $cCourier->courierComment
				);
			}
		else
			{
				// create Courier object
				$cCourier = new Courier();
				
				$iResult = $cCourier->CourierFromSearch($mysqli, $oPOSTData->data->phone, $oPOSTData->data->email);
				
				if ($iResult != USER_OK)
					{
						$mysqli->close();
						DropWithNotFound();
					}
					
				$aResultDataset = array(
					"formerlyName" => $cCourier->courierName,
					"phone" => $cCourier->courierPhone,
					"email" => $cCourier->courierEMail,
					"id" => $cCourier->courierID,
				);
			}
	}
else
	{
		// auth as user		
		// create User object
		$cUser = new User();
		
		// trying to authenticate
		$iAuth = $cUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);
		
		if (($iAuth != USER_OK) or (!$cUser->objectOK))
			{
				$mysqli->close();
				DropWithUnAuth();
			}
	
		if (!$cUser->isAdmin)
			{
				$mysqli->close();
				DropWithForbidden();
			}
		
		// create Courier object
		$cCourier = new Courier();
		
		$iResult = $cCourier->CourierFromSearch($mysqli, $oPOSTData->data->phone, $oPOSTData->data->email);
		
		if ($iResult != USER_OK)
			{
				$mysqli->close();
				DropWithNotFound();
			}
		
		$aResultDataset = array(
					"formerlyName" => $cCourier->courierName,
					"phone" => $cCourier->courierPhone,
					"email" => $cCourier->courierEMail,
					"id" => $cCourier->courierID,
					"maxWeight" => $cCourier->courierMaxWeight,
					"maxHeight" => $cCourier->courierMaxHeight,
					"maxLength" => $cCourier->courierMaxLength,
					"maxWidth" => $cCourier->courierMaxWidth,
					"comment" => $cCourier->courierComment
				);
	}

$aResultOut = array(
			"success" => "success",
			"data" => $aResultDataset
		);

http_response_code(200);		
header('Content-Type: application/json');
print(json_encode($aResultOut));

?>
