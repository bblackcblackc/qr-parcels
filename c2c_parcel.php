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
if ((!isset($oPOSTData->auth->courierLogin)) or (!isset($oPOSTData->auth->courierPassword)))
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

// create Courier object
$cCourier = new Courier();

// trying to authenticate
$iAuth = $cCourier->CourierFromAuth($mysqli, $oPOSTData->auth->courierLogin, $oPOSTData->auth->courierPassword);
		
if ($iAuth != USER_OK)
	DropWithUnAuth();
		
// check if parcel and courier exists and we are parcel's courier
$oParcel = new Parcel();
$oCourier = new Courier();

$iParcelRes = $oParcel->ParcelFromID($mysqli,$oPOSTData->data->parcelID);
$iCourierRes = $oCourier->CourierFromID($mysqli,$oPOSTData->data->courierID);

$iCurrentCourier = $oParcel->CurrentCourier($mysqli);
//print($iCurrentCourier . " = " . $oParcel->parcelID);
if ($iCurrentCourier != $cCourier->courierID)
	DropWithForbidden();

if (($iParcelRes != PARCEL_OK) or ($iCourierRes != USER_OK))
	DropWithNotFound();

// prepare data
$fLat = (isset($oPOSTData->data->lat) ? $oPOSTData->data->lat : 0);
$fLon = (isset($oPOSTData->data->lon) ? $oPOSTData->data->lon : 0);
$sPlace = (isset($oPOSTData->data->placeName) ? $oPOSTData->data->placeName : "");

// assign
$iParcelEvent = $oParcel->AddParcelEvent($mysqli, $cCourier->courierID, OPERATION_PARCEL_COURIER_TO_COURIER, $oCourier->courierID,
										$cCourier->courierID, $fLat, $fLon,
										$sPlace);

$mysqli->close();

if ($iParcelEvent == PARCEL_OK)
	ReturnSuccess();
else
	DropWithServerError("DB error");

?>
