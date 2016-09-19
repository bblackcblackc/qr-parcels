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
if (!isset($oPOSTData->data->email) and !isset($oPOSTData->data->phone))
		{
			DropWithBadRequest("Not enough eparameters");
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
		// create Courier object
		$cCourier = new Courier();
		
		// trying to authenticate
		$iAuth = $cCourier->CourierFromAuth($mysqli, $oPOSTData->auth->courierLogin, $oPOSTData->auth->courierPassword);
		//print($iAuth);
		if (($iAuth != USER_OK) or (!$cCourier->objectOK))
			DropWithUnAuth();

		$iTotalCount = $cCourier->CouriersCountFromSearch($mysqli,$oPOSTData->data->phone, $oPOSTData->data->email);

		$aCouriers = $cCourier->CouriersFromSearch($mysqli,$oPOSTData->data->phone, $oPOSTData->data->email,
					$oPOSTData->data->limit, $oPOSTData->data->offset);
		
		// compile out data

		$aResultDataSet = array();

		foreach($aCouriers as $oCourier)
			{
				$aResultDataSet[] = array(
						"formerlyName" => $oCourier->courierName,
						"phone" => $oCourier->courierPhone,
						"email" => $oCourier->courierEMail,
						"id" => $oCourier->courierID,
						//"maxWeight" => $oCourier->courierMaxWeight,
						//"maxHeight" => $oCourier->courierMaxHeight,
						//"maxLength" => $oCourier->courierMaxLength,
						//"maxWidth" => $oCourier->courierMaxWidth,
						//"comment" => $oCourier->courierComment
					);
			}
	}
else
	{
		// create User object
		$cUser = new User();
		
		// trying to authenticate
		$iAuth = $cUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);
		
		if (($iAuth != USER_OK) or (!$cUser->objectOK))
			DropWithUnAuth();
	
		if (!$cUser->isAdmin)
			DropWithForbidden();
	
		// create new courier
		$cCourier = new Courier();
		
		$iTotalCount = $cCourier->CouriersCountFromSearch($mysqli,$oPOSTData->data->phone, $oPOSTData->data->email);

		$aCouriers = $cCourier->CouriersFromSearch($mysqli,$oPOSTData->data->phone, $oPOSTData->data->email,
					$oPOSTData->data->limit, $oPOSTData->data->offset);

		// compile out data

		$aResultDataSet = array();

		foreach($aCouriers as $oCourier)
			{
				$aResultDataSet[] = array(
						"formerlyName" => $oCourier->courierName,
						"phone" => $oCourier->courierPhone,
						"email" => $oCourier->courierEMail,
						"id" => $oCourier->courierID,
						"maxWeight" => $oCourier->courierMaxWeight,
						"maxHeight" => $oCourier->courierMaxHeight,
						"maxLength" => $oCourier->courierMaxLength,
						"maxWidth" => $oCourier->courierMaxWidth,
						"comment" => $oCourier->courierComment
					);
			}
	}

$aResultOut = array(
			"success" => "success",
			"totalCount" => intval($iTotalCount),
			"data" => $aResultDataSet
		);

http_response_code(200);		
header('Content-Type: application/json');
print(json_encode($aResultOut));

?>
