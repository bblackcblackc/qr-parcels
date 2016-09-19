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

// check if auth and new pass presence
if (((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password))) and 
	((!isset($oPOSTData->auth->courierLogin)) or (!isset($oPOSTData->auth->courierPassword))))
	{
		DropWithUnAuth();
	}

if (!isset($oPOSTData->data->parcelID))
	DropWithBadRequest("No mandatory data");

// connect to DB
$mysqli = new mysqli(DB_HOST,DB_RW_LOGIN,DB_RW_PASSWORD,DB_NAME);

// check DB connection
if ($mysqli->connect_errno)
	DropWithUnAuth();

// prepare parcel
$oParcel = new Parcel();
$iParcelRes = $oParcel->ParcelFromID($mysqli,$oPOSTData->data->parcelID);

if (($iParcelRes != PARCEL_OK) or (!$oParcel->objectOK))
	DropWithNotFound();

// check rights
if (isset($oPOSTData->auth->login))
	{
		//auth by user
		// create User object
		$oUser = new User();
		
		// trying to authenticate
		$iAuth = $oUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);
		
		if (($iAuth != USER_OK) or (!$oUser->objectOK))
			DropWithUnAuth();

		if ((!$oUser->isAdmin) and ($oUser->userID != $oParcel->parcelSenderID) and (!$oUser->userID != $oParcel->parcelRecepientID))
			DropWithForbidden();
	}
else
	{
		// auth by courier
		// create Courier object
		$oCourier = new Courier();
		
		// trying to authenticate
		$iAuth = $oCourier->CourierFromAuth($mysqli, $oPOSTData->auth->courierLogin, $oPOSTData->auth->courierPassword);
		
		if (($iAuth != USER_OK) or (!$oCourier->objectOK))
			DropWithUnAuth();
		
		if ($oCourier->courierID != $oParcel->CurrentCourier($mysqli))
			DropWithForbidden();
	}

$sDocumentsQuery = "SELECT * FROM " . DB_DOCUMENTS_TABLE . " WHERE 1";
$oDocumentsRes = $mysqli->query($sDocumentsQuery);

// prepare sender and recepient
$oSender = new User();
$oRecepient = new User();

$iSenderUser = $oSender->UserFromID($mysqli,$oParcel->parcelSenderID);
$iRecepientUser = $oRecepient->UserFromID($mysqli,$oParcel->parcelRecepientID);

if (($iSenderUser != USER_OK) or ($iRecepientUser != USER_OK))
	DropWithServerError();

// prepare derival and arrival data
$oPickupCourier = $oParcel->FetchParcelEvents($mysqli, 0, OPERATION_PARCEL_FROM_USER, 0, 0, -1, -1, "", 0, 1);
$oDeliveryCourier = $oParcel->FetchParcelEvents($mysqli, 0, OPERATION_PARCEL_TO_USER, 0, 0, -1, -1, "", 0, 1);

$sPickUpCourierName = "";
$sDeliveryCourierName = "";

$iPickUpTime = 0;
$iDeliveryTime = 0;

$fPickUpLat = 0;
$fPickUpLon = 0;
$fDeliveryLat = 0;
$fDeliveryLon = 0;

if (isset($oPickupCourier[0]))
	{
		if ($oPickupCourier[0]["courier_id"] > 0)
			{
				$oPickupCourierObject = new Courier();
				$oPickupCourierObject->CourierFromID($mysqli, $oPickupCourier[0]["courier_id"]);
				
				if (($oPickupCourierObject->objectOK) and ($oPickupCourierObject->courierID > 0))
					{
						$sPickUpCourierName = $oPickupCourierObject->courierName;
						$iPickUpTime = $oPickupCourier[0]["u_time"];
						$fPickUpLat = floatval($oPickupCourier[0]["x_coord"]);
						$fPickUpLon = floatval($oPickupCourier[0]["y_coord"]);
					}
			}
	}

if (isset($oDeliveryCourier[0]))
	{
		if ($oDeliveryCourier[0]["courier_id"] > 0)
			{
				$oDeliveryCourierObject = new Courier();
				$oDeliveryCourierObject->CourierFromID($mysqli, $oDeliveryCourier[0]["courier_id"]);
				
				if (($oDeliveryCourierObject->objectOK) and ($oDeliveryCourierObject->courierID > 0))
					{
						$sDeliveryCourierName = $oDeliveryCourierObject->courierName;
						$iDeliveryTime = $oDeliveryCourier[0]["u_time"];
						$fDeliveryLat = floatval($oDeliveryCourier[0]["x_coord"]);
						$fDeliveryLon = floatval($oDeliveryCourier[0]["y_coord"]);
					}
			}
	}

// lets print
$mysqli->close();

$oResult = array();

while($oRow = $oDocumentsRes->fetch_assoc())
	{
		$sDocumentContent = $oRow["content"];
		$sDocumentName = $oRow["name"];
		
		$sDocumentContent = str_replace(array(
					"__SENDER_USER__",
					"__RECEPIENT_USER__",
					"__SEND_DATE__",
					"__RECEIVE_DATE__",
					"__SEND_COURIER_NAME__",
					"__RECEIVE_COURIER_NAME__",
					"__PRICE__",
					"__SENDER_ADDRESS__",
					"__SENDER_COORD_LAT__",	"__SENDER_COORD_LON__",
					"__RECEPIENT_ADDRESS__",
					"__RECEPIENT_COORD_LAT__", "__RECEPIENT_COORD_LON__",
					"__SENDER_PASSPORT__", "__SENDER_PHONE__", "__SENDER_EMAIL__",
					"__RECEPIENT_PASSPORT__", "__RECEPIENT_PHONE__", "__RECEPIENT_EMAIL__",
					"__PARCEL_WEIGHT__", "__PARCEL_LENGTH__", "__PARCEL_WIDTH__", "__PARCEL_HEIGHT__",
					"__PARCEL_VOLUME__",
					"__PARCEL_VALUE__"
				),
				array(
					$oSender->userName,
					$oRecepient->userName,
					$iPickUpTime,
					$iDeliveryTime,
					$sPickUpCourierName,
					$sDeliveryCourierName,
					$oParcel->parcelPrice,
					$oSender->userAddress,
					$fPickUpLat, $fPickUpLon,
					$oRecepient->userAddress,
					$fDeliveryLat, $fDeliveryLon,
					$oSender->userPassport, $oSender->userPhone, $oSender->userEMail,
					$oRecepient->userPassport, $oRecepient->userPhone, $oRecepient->userEMail,
					$oParcel->parcelWeight, $oParcel->parcelLength, $oParcel->parcelWidth, $oParcel->parcelHeight,
					round($oParcel->parcelWidth*$oParcel->parcelHeight*$oParcel->parcelLength,2),
					$oParcel->parcelValue
				),
				$sDocumentContent);
		
		$oResult[] = array(
				"name" => $sDocumentName,
				"content" => $sDocumentContent
			);
	}

$oResultDataSet["success"] = "success";
$oResultDataSet["data"] = $oResult;

http_response_code(200);		
header('Content-Type: application/json');
print(json_encode($oResultDataSet));

?>
