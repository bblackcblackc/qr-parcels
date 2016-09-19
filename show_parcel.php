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
require_once "./service/parcel_class.php";
require_once "./service/courier_class.php";

// check POST body
$oPOSTData = json_decode(file_get_contents("php://input"));

// check if data enough
if (!isset($oPOSTData->data->parcelID))
		{
			DropWithBadRequest("Not enough parameters");
		}

// check if auth presence
/*if (((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password))) and 
	((!isset($oPOSTData->auth->courierLogin)) or (!isset($oPOSTData->auth->courierPassword))))
	{
		DropWithUnAuth();
	}
	*
	*/
	
	
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

$bShowAll = true;
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
			$bShowAll = false;
	}
else if (isset($oPOSTData->auth->courierLogin))
	{
		// create Courier object
		$oCourier = new Courier();
		
		// trying to authenticate
		$iAuth = $oCourier->CourierFromAuth($mysqli, $oPOSTData->auth->courierLogin, $oPOSTData->auth->courierPassword);
		
		if (($iAuth != USER_OK) or (!$oCourier->objectOK))
			DropWithUnAuth();
			
		// if we're not curent courier
		if ($oCourier->courierID != $oParcel->CurrentCourier($mysqli))
			$bShowAll = false;
	}
else
	$bShowAll = false;
	
// compile out data
if ($bShowAll)
	$aResultDataset = array(
			"senderID" => $oParcel->parcelSenderID,
			"recepientID" => $oParcel->parcelRecepientID,
			"senderAddress" => $oParcel->parcelSenderAddress,
			"recepientAddress" => $oParcel->parcelRecepientAddress,
			"senderLat" => $oParcel->parcelSenderCoordLat,
			"senderLon" => $oParcel->parcelSenderCoordLon,
			"recepientLat" => $oParcel->parcelRecepientCoordLat,
			"recepientLon" => $oParcel->parcelRecepientCoordLon,
			"parcelWeight" => $oParcel->parcelWeight,
			"parcelWidth" => $oParcel->parcelWidth,
			"parcelHeight" => $oParcel->parcelHeight,
			"parcelLength" => $oParcel->parcelLength,
			"parcelPrice" => $oParcel->parcelPrice,
			"parcelValue" => $oParcel->parcelValue,
			"parcelComment" => $oParcel->parcelComment,
			"courierID" => $oParcel->CurrentCourier($mysqli),
			"id" => $oParcel->parcelID
		);
else
	$aResultDataset = array(
			"senderID" => $oParcel->parcelSenderID,
			"recepientID" => $oParcel->parcelRecepientID,
			"senderAddress" => $oParcel->parcelSenderAddress,
			"recepientAddress" => $oParcel->parcelRecepientAddress,
			"id" => $oParcel->parcelID
		);

$aResultOut = array(
			"success" => "success",
			"data" => $aResultDataset
		);

http_response_code(200);		
header('Content-Type: application/json');
print(json_encode($aResultOut));

?>
