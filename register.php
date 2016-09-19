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

// fetch GET data
$sCookie = $_GET["q"];

// connect to DB
$mysqli = new mysqli(DB_HOST,DB_RW_LOGIN,DB_RW_PASSWORD,DB_NAME);

// check DB connection
if ($mysqli->connect_errno)
	DropWithServerError();

$sCookieCheckQuery = "SELECT * FROM `" . DB_REGISTRATIONS_TABLE . "` WHERE `code` = \"" . $mysqli->real_escape_string($sCookie) . "\"";
$oCookieCheck = $mysqli->query($sCookieCheckQuery);

if ($mysqli->affected_rows < 1)
	DropWithNotFound();
else
	{
		$oRow = $oCookieCheck->fetch_assoc();
		$iUserID = $oRow["id"];
		
		$sApproveRegistrationQuery = "UPDATE `" . DB_USERS_TABLE . "` SET `approved` = 1 WHERE `id` = " . $iUserID;
		$oApproveRes = $mysqli->query($sApproveRegistrationQuery);
		
		$iAffected1 = $mysqli->affected_rows;
		
		$sDeleteApprove = "DELETE FROM `" . DB_REGISTRATIONS_TABLE . "` WHERE `id` = " . $iUserID;
		$oApproveRes = $mysqli->query($sDeleteApprove);
		
		$iAffected2 = $mysqli->affected_rows;
		
		$mysqli->close();
		if (($iAffected1 > 0) and ($iAffected2 > 0))
			ReturnSuccess();
		else
			DropWithServerError();
	}

?>
