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

// check POST body
$oPOSTData = json_decode(file_get_contents("php://input"));

// check if auth presence
if ((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password)))
	{
		DropWithUnAuth();
	}

// check mandatory params
if (!isset($oPOSTData->data->parcelID))
	{
		DropWithServerError("No mandatory data");
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

if (($iAuth != USER_OK) or (!$cUser->objectOK))
	DropWithUnAuth();

// check for admin rights
if (!$cUser->isAdmin)
	DropWithForbidden();

// create Parcel object
$oParcel = new Parcel();
$iParcelRes = $oParcel->ParcelFromID($mysqli,$oPOSTData->data->parcelID);

if (($iParcelRes != PARCEL_OK) or (!$oParcel->objectOK))
	DropWithNotFound();

// modifying params						
if (isset($oPOSTData->modifies->senderID))
	$oParcel->parcelSenderID = $oPOSTData->modifies->senderID;

if (isset($oPOSTData->modifies->recepientID))
	$oParcel->parcelRecepientID = $oPOSTData->modifies->recepientID;

if (isset($oPOSTData->modifies->senderAddress))
	$oParcel->parcelSenderAddress = $oPOSTData->modifies->senderAddress;

if (isset($oPOSTData->modifies->recepientAddress))
	$oParcel->parcelRecepientAddress = $oPOSTData->modifies->recepientAddress;
						
if (isset($oPOSTData->modifies->senderLat))
	$oParcel->parcelSenderCoordLat = $oPOSTData->modifies->senderLat;

if (isset($oPOSTData->modifies->senderLon))
	$oParcel->parcelSenderCoordLon = $oPOSTData->modifies->senderLon;

if (isset($oPOSTData->modifies->recepientLat))
	$oParcel->parcelRecepientCoordLat = $oPOSTData->modifies->recepientLat;

if (isset($oPOSTData->modifies->recepientLon))
	$oParcel->parcelRecepientCoordLon = $oPOSTData->modifies->recepientLon;

if (isset($oPOSTData->modifies->parcelWeight))
	$oParcel->parcelWeight = $oPOSTData->modifies->parcelWeight;

if (isset($oPOSTData->modifies->parcelWidth))
	$oParcel->parcelWidth = $oPOSTData->modifies->parcelWidth;

if (isset($oPOSTData->modifies->parcelPrice))
	$oParcel->parcelPrice = $oPOSTData->modifies->parcelPrice;

if (isset($oPOSTData->modifies->parcelLength))
	$oParcel->parcelLength = $oPOSTData->modifies->parcelLength;

if (isset($oPOSTData->modifies->parcelHeight))
	$oParcel->parcelHeight = $oPOSTData->modifies->parcelHeight;

if (isset($oPOSTData->modifies->parcelValue))
	$oParcel->parcelValue = $oPOSTData->modifies->parcelValue;

if (isset($oPOSTData->modifies->parcelComment))
	$oParcel->parcelComment = $oPOSTData->modifies->parcelComment;

$iResult = $oParcel->SaveParcel($mysqli);
						
$mysqli->close();
if ($iResult == USER_OK)
	ReturnSuccess();
else
	DropWithServerError("Error saving parcel");

?>
