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
	
// input parameters
$bAllParcels = false;

$iTimestampFrom = (isset($oPOSTData->data->timestampFrom) ? $oPOSTData->data->timestampFrom : 0);
$iTimestampTo = (isset($oPOSTData->data->timestampTo) ? $oPOSTData->data->timestampTo : 0);
$aCouriers = (isset($oPOSTData->data->courierIDs) ? $oPOSTData->data->courierIDs : array());
$aSenders = (isset($oPOSTData->data->senderIDs) ? $oPOSTData->data->senderIDs : array());
$aRecepients = (isset($oPOSTData->data->recepientIDs) ? $oPOSTData->data->recepientIDs : array());

// offset and limit
$iOffset = (isset($oPOSTData->data->offset) ? $oPOSTData->data->offset : 0);
$iLimit = (isset($oPOSTData->data->limit) ? $oPOSTData->data->limit : 0);


// check auth and rights
if (isset($oPOSTData->auth->login))
	{
		// create User object
		$oUser = new User();
		
		// trying to authenticate
		$iAuth = $oUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);
		
		if (($iAuth != USER_OK) or (!$oUser->objectOK))
			DropWithUnAuth();

		// if not admin
		if (!$oUser->isAdmin)
			{
				$bAllParcels = false;
				
				if (isset($oPOSTData->data->senderIDs))
					$aSenders = array($oUser->userID);
				
				if (isset($oPOSTData->data->recepientIDs))
					$aRecepients = array($oUser->userID);
			}
			
	}
else if (isset($oPOSTData->auth->courierLogin))
	{
		// create Courier object
		$oCourier = new Courier();
		
		// trying to authenticate
		$iAuth = $oCourier->CourierFromAuth($mysqli, $oPOSTData->auth->courierLogin, $oPOSTData->auth->courierPassword);
		
		if (($iAuth != USER_OK) or (!$oCourier->objectOK))
			DropWithUnAuth();
		
		$bAllParcels = false;
		$aCouriers = array($oCourier->courierID);
	}
	
	//print_r($aSenders);
	//print_r($aRecepients);

$oParcel = new Parcel();
$aParcels = $oParcel->ParcelsFromSearch($mysqli, $iTimestampFrom, $iTimestampTo, $aCouriers, $aSenders, $aRecepients, $bAllParcels,
				$iOffset, $iLimit);

$iParcelsCount = $oParcel->ParcelsCountFromSearch($mysqli, $iTimestampFrom, $iTimestampTo, $aCouriers, $aSenders, $aRecepients, $bAllParcels,
				$iOffset, $iLimit);

if ($iParcelsCount < 0)
	$iParcelsCount = 0;

// compile out data
$aResultDataSet = array();
$aResults = array();

foreach($aParcels as $oParcelRes)
	{
		$aResults[] = array(
			"senderID" => $oParcelRes->parcelSenderID,
			"recepientID" => $oParcelRes->parcelRecepientID,
			"senderAddress" => $oParcelRes->parcelSenderAddress,
			"recepientAddress" => $oParcelRes->parcelRecepientAddress,
			"senderLat" => $oParcelRes->parcelSenderCoordLat,
			"senderLon" => $oParcelRes->parcelSenderCoordLon,
			"recepientLat" => $oParcelRes->parcelRecepientCoordLat,
			"recepientLon" => $oParcelRes->parcelRecepientCoordLon,
			"parcelWeight" => $oParcelRes->parcelWeight,
			"parcelWidth" => $oParcelRes->parcelWidth,
			"parcelHeight" => $oParcelRes->parcelHeight,
			"parcelLength" => $oParcelRes->parcelLength,
			"parcelPrice" => $oParcelRes->parcelPrice,
			"parcelValue" => $oParcelRes->parcelValue,
			"parcelComment" => $oParcelRes->parcelComment,
			"courierID" => $oParcelRes->parcelCurrentCourier, //CurrentCourier($mysqli),
			"id" => $oParcelRes->parcelID
			);
	}

$aResultDataSet = array(
			"success" => "success",
			"totalCount" => intval($iParcelsCount),
			"data" => $aResults
		);

http_response_code(200);		
header('Content-Type: application/json');
print(json_encode($aResultDataSet));

?>
