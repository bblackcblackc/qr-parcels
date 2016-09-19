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
if (((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password))) and 
	((!isset($oPOSTData->auth->courierLogin)) or (!isset($oPOSTData->auth->courierPassword))))
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

if (isset($oPOSTData->auth->login))
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
	}
else
	{
		// auth as courier
		// create Courier object
		$cCourier = new Courier();
		
		// trying to authenticate
		$iAuth = $cCourier->CourierFromAuth($mysqli, $oPOSTData->auth->courierLogin, $oPOSTData->auth->courierPassword);
		
		if (($iAuth != USER_OK) or (!$cCourier->objectOK))
			DropWithUnAuth();
	}
	
		
// check if parcel and courier exists and we are parcel's courier
$oParcel = new Parcel();
$iParcelRes = $oParcel->ParcelFromID($mysqli,$oPOSTData->data->parcelID);

if (($iParcelRes != PARCEL_OK) or ($iCourierRes != USER_OK))
	DropWithNotFound();

if (isset($oPOSTData->auth->login))
	{
		if ($oParcel->parcelRecepientID != $cUser->userID)
			DropWithForbidden();
		
		$iOperationOwner = 0; //$cUser->userID;
		$i1stParam = 0;//$cUser->userID;
		$i2ndParam = $oParcel->CurrentCourier($mysqli);
		
		$iOperationCode = OPERATION_PARCEL_FROM_COURIER;
	}
else
	{
		$iCurrentCourier = $oParcel->CurrentCourier($mysqli, $oPOSTData->data->parcelID);
		
		if ($iCurrentCourier != $cCourier->courierID)
			DropWithForbidden();

		$iOperationOwner = $cCourier->courierID;
		$i1stParam = 0;//$oParcel->parcelRecepientID;
		$i2ndParam = $cCourier->courierID;
		
		$iOperationCode = OPERATION_PARCEL_TO_USER;
	}

// if no courier assigned and so on
//if ($i2ndParam <= 0)
//	DropWithServerError("Courier not assigned");

// prepare data
$fLat = (isset($oPOSTData->data->lat) ? $oPOSTData->data->lat : 0);
$fLon = (isset($oPOSTData->data->lon) ? $oPOSTData->data->lon : 0);
$sPlace = (isset($oPOSTData->data->placeName) ? $oPOSTData->data->placeName : "");

// assign
$iParcelEvent = $oParcel->AddParcelEvent($mysqli, $iOperationOwner, $iOperationCode, $i1stParam,
										$i2ndParam, $fLat, $fLon,
										$sPlace);

$mysqli->close();

if ($iParcelEvent == PARCEL_OK)
	ReturnSuccess();
else
	DropWithServerError("DB error");

?>
