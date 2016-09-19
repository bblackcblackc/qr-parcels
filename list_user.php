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

// check POST body
$oPOSTData = json_decode(file_get_contents("php://input"));

// check if data enough
if (!isset($oPOSTData->data->vkID) and !isset($oPOSTData->data->defaultEmail) 
	and !isset($oPOSTData->data->defaultPhone) and !isset($oPOSTData->data->passportNum))
		{
			DropWithBadRequest("Not enough parameters");
		}

// check if auth presence
$bAuth = false;
if ((isset($oPOSTData->auth->login)) and (isset($oPOSTData->auth->password)))
	$bAuth = true;

// connect to DB
$mysqli = new mysqli(DB_HOST,DB_RW_LOGIN,DB_RW_PASSWORD,DB_NAME);

// check DB connection
if ($mysqli->connect_errno)
	DropWithServerError();

// create User object
$cUser = new User();

// trying to authenticate
$iAuth = $cUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);

if (($iAuth != USER_OK) or (!$cUser->objectOK))
	DropWithUnAuth();

// check for admin rights
$bShowAll = false;
if ($cUser->isAdmin)
	$bShowAll = true;

$iTotalCount = $cUser->UsersCountFromSearch($mysqli,$oPOSTData->data->defaultPhone, $oPOSTData->data->defaultEmail, $oPOSTData->data->vkID,
								$oPOSTData->data->passportNum);

$aUsers = $cUser->UsersFromSearch($mysqli,$oPOSTData->data->defaultPhone, $oPOSTData->data->defaultEmail, $oPOSTData->data->vkID,
								$oPOSTData->data->passportNum, $oPOSTData->data->limit, $oPOSTData->data->offset);

// compile out data

$aResultDataSet = array();

if ($bShowAll)
	{
		foreach($aUsers as $oUser)
			{
				$aResultDataSet[] = array(
						"id" => $oUser->userID,
						"formerlyName" => $oUser->userName,
						"passportNum" => $oUser->userPassport,
						"defaultAddress" => $oUser->userAddress,
						"defaultEmail" => $oUser->userEMail,
						"defaultPhone" => $oUser->userPhone,
						"vkID" => $oUser->userVKID,
						"isAdmin" => $oUser->isAdmin
					);
			}
	}
else
	{
		foreach($aUsers as $oUser)
			{
				$aResultDataSet[] = array(
						"id" => $oUser->userID,
						"formerlyName" => $oUser->userName,
					);
			}
	}

$aResultOut = array(
			"success" => "success",
			"totalCount" => intval($iTotalCount),
			"data" => $aResultDataSet
		);

http_response_code(200);		
header('Content-Type: application/json');
print(json_encode($aResultOut));

?>
