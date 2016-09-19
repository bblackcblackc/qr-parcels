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

DropWithServerError("This method is obsolete. Use V2 method.");

// check POST body
$oPOSTData = json_decode(file_get_contents("php://input"));

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
	DropWithUnAuth();

// check type of auth
if (isset($oPOSTData->auth->courierLogin))
	{
		// create User object
		$cCourier = new Courier();

		// trying to authenticate
		$iAuth = $cCourier->CourierFromAuth($mysqli, $oPOSTData->auth->courierLogin, $oPOSTData->auth->courierPassword);
		
		if ($iAuth != USER_OK)
			DropWithUnAuth();
	}
else
	{
		// create User object
		$cUser = new User();

		// trying to authenticate
		$iAuth = $cUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);
		
		if ($iAuth != USER_OK)
			DropWithUnAuth();
		
		// if we're user -- sender must be equeal with us
		if ($cUser->userID != $oPOSTData->modifies->senderID)
			DropWithForbidden();
	}
	
// check for parameters presence
if (!isset($oPOSTData->modifies->senderID) or !isset($oPOSTData->modifies->recepientID)
	or !isset($oPOSTData->modifies->parcelWeight) or !isset($oPOSTData->modifies->parcelLength) or !isset($oPOSTData->modifies->parcelHeight)
	or !isset($oPOSTData->modifies->parcelWidth) or !isset($oPOSTData->modifies->parcelPrice))
	{
		DropWithBadRequest("Not enough or wrong parameters");
	}

// prepare data for parcel

$iCreatorID = (isset($cCourier) ? $cCourier->courierID : $cUser->userID);

$oSenderUser = new User();
$oRecepientUser = new User();

$iSenderRes = $oSenderUser->UserFromID($mysqli,$oPOSTData->modifies->senderID);
$iRecepientRes = $oRecepientUser->UserFromID($mysqli,$oPOSTData->modifies->recepientID);

if (($iSenderRes != USER_OK) or ($iRecepientRes != USER_OK))
	DropWithNotFound();

$sSenderAddress = (isset($oPOSTData->modifies->senderAddress) ? $oPOSTData->modifies->senderAddress : $oSenderUser->userAddress);
$sRecepientAddress = (isset($oPOSTData->modifies->recepientAddress) ? $oPOSTData->modifies->recepientAddress : $oRecepientUser->userAddress);

// create parcel

$oNewParcel = new Parcel();
$iNewParcelResult = $oNewParcel->NewParcelFromParameters($mysqli,
						$iCreatorID,
						isset($cCourier),
						$oPOSTData->modifies->senderID,
						$oPOSTData->modifies->recepientID,
						$sSenderAddress,
						$sRecepientAddress,
						$oPOSTData->modifies->senderLat,
						$oPOSTData->modifies->senderLon,
						$oPOSTData->modifies->recepientLat,
						$oPOSTData->modifies->recepientLon,
						$oPOSTData->modifies->parcelWeight,
						$oPOSTData->modifies->parcelLength,
						$oPOSTData->modifies->parcelHeight,
						$oPOSTData->modifies->parcelWidth,
						$oPOSTData->modifies->parcelPrice,
						$oPOSTData->modifies->parcelVolume,
						$oPOSTData->modifies->parcelComment);

// if all ok, and if parcel created by courier
// we must write first event -- assignment parcel to courier
//print($iNewParcelResult . " r ");
if (isset($cCourier) and ($iNewParcelResult == PARCEL_OK))
	{
		$iParcelEvent = $oNewParcel->AddParcelEvent($mysqli, $cCourier->courierID, OPERATION_PARCEL_FROM_USER, $cCourier->courierID,
										$oPOSTData->modifies->senderID, $oPOSTData->modifies->senderLat, $oPOSTData->modifies->senderLon,
										$sSenderAddress);
		if ($iParcelEvent != PARCEL_OK)
			{
				$mysqli->close();
				return PARCEL_DB_ERROR;
			}
	}

$mysqli->close();

switch($iNewParcelResult)
	{
		case PARCEL_NO_PARAMS:
			DropWithBadRequest("Not enough parameters");
		case PARCEL_DB_ERROR:
			DropWithServerError();
		case PARCEL_EXISTS:
			DropWithServerError("Parcel already exists");
		default:
			ReturnSuccess(array("id" => $iNewParcelResult));
	}

?>
