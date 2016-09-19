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
require_once "./service/tariff_class.php";
require_once "./service/finance_class.php";

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
	or !isset($oPOSTData->modifies->parcelWidth) or !isset($oPOSTData->modifies->parcelTariff))
	{
		DropWithBadRequest("Not enough or wrong parameters");
	}

// prepare data for parcel

$iCreatorID = (isset($cCourier) ? $cCourier->courierID : $cUser->userID);

// check users
$oSenderUser = new User();
$oRecepientUser = new User();

$iSenderRes = $oSenderUser->UserFromID($mysqli,$oPOSTData->modifies->senderID);
$iRecepientRes = $oRecepientUser->UserFromID($mysqli,$oPOSTData->modifies->recepientID);

if (($iSenderRes != USER_OK) or ($iRecepientRes != USER_OK))
	DropWithNotFound();

// check addresses
$sSenderAddress = (isset($oPOSTData->modifies->senderAddress) ? $oPOSTData->modifies->senderAddress : $oSenderUser->userAddress);
$sRecepientAddress = (isset($oPOSTData->modifies->recepientAddress) ? $oPOSTData->modifies->recepientAddress : $oRecepientUser->userAddress);

if (($sSenderAddress == "") or ($sRecepientAddress == ""))
	DropWithBadRequest("Addresses are mandatory");
	
// check tariff
$oTariff = new Tariff();

$iTariffResult = $oTariff->TariffFromID($mysqli, $oPOSTData->modifies->parcelTariff);

if ($iTariffResult != USER_OK)
	DropWithNotFound("No such tariff");
	
//////////////////////////
//
// REQUESTING FOR PRICE

$oPriceRequestParameters = array(
		"derivalCity" => $sSenderAddress,
		"arrivalCity" => $sRecepientAddress,
		"weight" => floatval($oPOSTData->modifies->parcelWeight),
		"length" => floatval($oPOSTData->modifies->parcelLength),
		"width" => floatval($oPOSTData->modifies->parcelWidth),
		"height" => floatval($oPOSTData->modifies->parcelHeight)
	);

// check additional options
if (isset($oPOSTData->modifies->parcelAdditionalOptions))
	$oPriceRequestParameters["additionalOptions"] = $oPOSTData->modifies->parcelAdditionalOptions;

$sCalcURL = CALC_API_BASE_URL . "get_transport.php";
$oCalcCurl = curl_init($sCalcURL);
curl_setopt($oCalcCurl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($oCalcCurl, CURLOPT_POST, TRUE);
curl_setopt($oCalcCurl, CURLOPT_POSTFIELDS, json_encode($oPriceRequestParameters));
$sCalcAnswer = curl_exec($oCalcCurl);

$oCalcAnswer = json_decode($sCalcAnswer);

if (isset($oCalcAnswer->failReason))
	DropWithNotFound("No way");

// all ok, creating parcel

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
						//
						$oCalcAnswer->priceTotal * $oTariff->tariffPriceCoeff,
						$oCalcAnswer->timeTotal * $oTariff->tariffTimeCoeff * 86400 + time(),
						//
						$oPOSTData->modifies->parcelVolume,
						$oPOSTData->modifies->parcelComment);

// if all ok
if ($iNewParcelResult > PARCEL_OK)
	{
		// and parcel created by courier
		if (isset($cCourier))
			{
				// assign parcel to its courier
				$iParcelEvent = $oNewParcel->AddParcelEvent($mysqli, $cCourier->courierID, OPERATION_PARCEL_FROM_USER, $cCourier->courierID,
												$oPOSTData->modifies->senderID, $oPOSTData->modifies->senderLat, $oPOSTData->modifies->senderLon,
												$sSenderAddress);
				if ($iParcelEvent != PARCEL_OK)
					{
						$mysqli->close();
						DropWithServerError("DB error");
					}
			}
		
		// and we must create financial record
		$oFinance = new Finance();
		$iParcelFinance = $oFinance->NewOperation($mysqli, 0-$oNewParcel->parcelPrice, $oNewParcel->parcelSenderID, $oNewParcel->parcelID,
					"Задолженность за экспедиторские услуги");
				if ($iParcelFinance != PARCEL_OK)
					{
						$mysqli->close();
						DropWithServerError("DB error");
					}					
	}

$mysqli->close();

//
// parcel additional info
// 

$oResultDataSet = $oCalcAnswer;

$oResultDataSet->priceBase = $oCalcAnswer->priceTotal;
$oResultDataSet->priceTotal = $oCalcAnswer->priceTotal * $oTariff->tariffPriceCoeff;

$oResultDataSet->timeBase = $oCalcAnswer->timeTotal;
$oResultDataSet->timeTotal = $oCalcAnswer->timeTotal * $oTariff->tariffTimeCoeff;

$oResultDataSet->tariffName = $oTariff->tariffName;
$oResultDataSet->id = $iNewParcelResult;
//

switch($iNewParcelResult)
	{
		case PARCEL_NO_PARAMS:
			DropWithBadRequest("Not enough parameters");
		case PARCEL_DB_ERROR:
			DropWithServerError();
		case PARCEL_EXISTS:
			DropWithServerError("Parcel already exists");
		default:
			ReturnSuccess((array) $oResultDataSet);
	}

?>
