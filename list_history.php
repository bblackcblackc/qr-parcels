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
require_once "./service/parcel_class.php";

// check POST body
$oPOSTData = json_decode(file_get_contents("php://input"));

// check if data enough
if (!isset($oPOSTData->data->parcelID))
		{
			DropWithBadRequest("Not enough parameters");
		}

// check if auth presence
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

// check parcel existence
$oParcel = new Parcel();
$iParcelRes = $oParcel->ParcelFromID($mysqli,$oPOSTData->data->parcelID);

if (($iParcelRes != PARCEL_OK) or (!$oParcel->objectOK))
	DropWithNotFound();

// check auth and rights
if (isset($oPOSTData->auth->login))
	{
		// create User object
		$oUser = new User();
		
		// trying to authenticate
		$iAuth = $oUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);
		
		if (($iAuth != USER_OK) or (!$oUser->objectOK))
			DropWithUnAuth();
		
		// if it is not ours parcel
		if (($oParcel->parcelRecepientID != $oUser->userID) and ($oParcel->parcelSenderID != $oUser->userID) 
			and (!$oUser->userID->isAdmin))
			DropWithForbidden();
	}
else
	{
		// create Courier object
		$oCourier = new Courier();
		
		// trying to authenticate
		$iAuth = $oCourier->CourierFromAuth($mysqli, $oPOSTData->auth->courierLogin, $oPOSTData->auth->courierPassword);
		
		if (($iAuth != USER_OK) or (!$oCourier->objectOK))
			DropWithUnAuth();
			
		// if we're not curent courier
		if ($oCourier->courierID != $oParcel->CurrentCourier($mysqli))
			DropWithForbidden();
	}

// offset and limit
$iOffset = (isset($oPOSTData->data->offset) ? $oPOSTData->data->offset : 0);
$iLimit = (isset($oPOSTData->data->limit) ? $oPOSTData->data->limit : 0);

$aEventResults = $oParcel->FetchParcelEvents($mysqli, 0, 0, 0, 0, -1, -1, "", 
				$iOffset, $iLimit, false);
$iResCount = $oParcel->ParcelEventsCount($mysqli, 0, 0, 0, 0, -1, -1, "", 
				$iOffset, $iLimit, false);

// compile out data
$aResultDataSet = array();
$aResults = array();

foreach($aEventResults as $aEvent)
	{
		$aResults[] = array(
				"id" => intval($aEvent["id"]),
				"lat" => floatval($aEvent["x_coord"]),
				"lon" => floatval($aEvent["y_coord"]),
				"timestamp" => intval($aEvent["u_time"]),
				"name" => $aEvent["name"],
				"parameter_destination" => intval($aEvent["operation_param1"]),
				"parameter_origin" => intval($aEvent["operation_param2"]),
				"courier_id" => intval($aEvent["courier_id"]),
				"place" => $aEvent["place_name"]
			);
	}

$aResultDataSet = array(
			"success" => "success",
			"totalCount" => intval($iResCount),
			"data" => $aResults
		);

http_response_code(200);		
header('Content-Type: application/json');
print(json_encode($aResultDataSet));

?>
