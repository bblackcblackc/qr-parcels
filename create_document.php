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

// check if auth and new pass presence
if ((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password)))
	{
		DropWithUnAuth();
	}

if (!isset($oPOSTData->modifies->name) or !isset($oPOSTData->modifies->content))
	DropWithBadRequest("No mandatory data");

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

// need to be admin
if (!$cUser->isAdmin)
	DropWithForbidden();

// prepare data
$sDocumentName = $mysqli->real_escape_string($oPOSTData->modifies->name);
$sDocumentContent = $mysqli->real_escape_string(base64_decode($oPOSTData->modifies->content));

// lets write
$sAddDocumentQuery = "INSERT INTO `" . DB_DOCUMENTS_TABLE . "` (`name`, `content`) VALUES (\"" . $sDocumentName . 
						"\", \"" . $sDocumentContent. "\")";
$mysqli->query($sAddDocumentQuery);

if ($mysqli->error)
	{
		$mysqli->close();
		DropWithServerError("DB error.");
	}
else if ($mysqli->affected_rows > 0)
	{
		$mysqli->close();
		ReturnSuccess();
	}
else
	{
		$mysqli->close();
		DropWithServerError("Unknown");
	}

?>
