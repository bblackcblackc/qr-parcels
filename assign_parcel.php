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
require_once "./service/parcel_class.php";

// check POST body
$oPOSTData = json_decode(file_get_contents("php://input"));

// check if auth and new pass presence
if ((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password)))
	{
		DropWithUnAuth();
	}

// check if data enough
if (!isset($oPOSTData->data->parcelID) and !isset($oPOSTData->data->courierID))
		{
			DropWithBadRequest("Not enough eparameters");
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
		
// if we're user -- sender must be equeal with us
if (!$cUser->isAdmin)
	DropWithForbidden();

// check if parcel and courier exists
$oParcel = new Parcel();
$oCourier = new Courier();

$iParcelRes = $oParcel->ParcelFromID($mysqli,$oPOSTData->data->parcelID);
$iCourierRes = $oCourier->CourierFromID($mysqli,$oPOSTData->data->courierID);

if (($iParcelRes != PARCEL_OK) or ($iCourierRes != USER_OK))
	DropWithNotFound();

// assign
$iParcelEvent = $oParcel->AddParcelEvent($mysqli, $oCourier->courierID, OPERATION_PARCEL_COURIER_ASSIGN, $oCourier->courierID,
										$cUser->userID, $oPOSTData->data->senderLat, $oPOSTData->data->senderLon,
										$sSenderAddress);

$mysqli->close();

if ($iParcelEvent == PARCEL_OK)
	ReturnSuccess();
else
	DropWithServerError("DB error");

?>
