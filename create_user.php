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
require_once "./service/SendMailSmtpClass.php";

// check POST body
$oPOSTData = json_decode(file_get_contents("php://input"));

// check for parameters presence
if ((!isset($oPOSTData->modifies->formerlyName)) or (!isset($oPOSTData->modifies->passportNum)) or (!isset($oPOSTData->modifies->password))
	or (isset($oPOSTData->modifies->passportNum) and (intval($oPOSTData->modifies->passportNum) < 1))
	or (!isset($oPOSTData->modifies->defaultEmail)))
	{
		DropWithBadRequest("Not enough or wrong parameters");
	}

////////////////////////

// connect to DB
$mysqli = new mysqli(DB_HOST,DB_RW_LOGIN,DB_RW_PASSWORD,DB_NAME);

// check DB connection
if ($mysqli->connect_errno)
	DropWithUnAuth();

/////////////////////////

// check if auth presence
if (((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password))) and 
	((!isset($oPOSTData->auth->courierLogin)) or (!isset($oPOSTData->auth->courierPassword))))
	{
		// NOT AUTH 
		
		$cNewUser = new User();
		$iNewUserResult = $cNewUser->NewUserFromParameters($mysqli, $oPOSTData->modifies->formerlyName, $oPOSTData->modifies->defaultPhone,
							$oPOSTData->modifies->defaultEmail,	
							isset($oPOSTData->modifies->defaultAddress) ? $oPOSTData->modifies->defaultAddress : "",
							$oPOSTData->modifies->password,
							false,
							$oPOSTData->modifies->vkID, $oPOSTData->modifies->passportNum, 0);
		
		if ($iNewUserResult == USER_OK)
			{

				// generate random registration code
		
				$sRandCookie = random_str(250);
		
				// write it to DB
				
				$sCookieWriteQuery = "INSERT INTO `" . DB_REGISTRATIONS_TABLE . "` (`id`,`code`) VALUES (" . $cNewUser->userID . ", " .
										"\"" . $mysqli->real_escape_string($sRandCookie) . "\")";
				$mysqli->query($sCookieWriteQuery);
				
				// send email
				
				$sMailText = MAIL_TEXT . "https://api." . HOST_REG_FROM . "/register.php?q=" . $sRandCookie;
				
				date_default_timezone_set("UTC");
				$mailSMTP = new SendMailSmtpClass(MAIL_REG_FROM, PASS_REG_FROM, MAILER, HOST_REG_FROM, MAILER_PORT);
				$mailResult = $mailSMTP->send($oPOSTData->modifies->defaultEmail, MAIL_SUBJECT, $sMailText, MAIL_HEADERS);
				
				if (!($mailResult === true))
					$iNewUserResult = USER_DB_ERROR;
			}
		
		$mysqli->close();
		
	}
else
	{
		// AUTH
		if (isset($oPOSTData->auth->login))
			{
				// user
				// create User object
				$cUser = new User();
				
				// trying to authenticate
				$iAuth = $cUser->UserFromAuth($mysqli, $oPOSTData->auth->login, $oPOSTData->auth->password);
				
				if ($iAuth != USER_OK)
					DropWithUnAuth();
				
				// check for admin rights
				if (!$cUser->isAdmin)
					DropWithForbidden();

				$cNewUser = new User();
				$iNewUserResult = $cNewUser->NewUserFromParameters($mysqli, $oPOSTData->modifies->formerlyName, $oPOSTData->modifies->defaultPhone,
									$oPOSTData->modifies->defaultEmail,	
									isset($oPOSTData->modifies->defaultAddress) ? $oPOSTData->modifies->defaultAddress : "",
									$oPOSTData->modifies->password,
									isset($oPOSTData->modifies->isAdmin) ? $oPOSTData->modifies->isAdmin : false,
									$oPOSTData->modifies->vkID, $oPOSTData->modifies->passportNum);
									
				$mysqli->close();						
			}
		else
			{
				// courier
				// create Courier object
				$oCourier = new Courier();
				
				// trying to authenticate
				$iAuth = $oCourier->CourierFromAuth($mysqli, $oPOSTData->auth->courierLogin, $oPOSTData->auth->courierPassword);
				
				if ($iAuth != USER_OK)
					DropWithUnAuth();
				
				$cNewUser = new User();
				$iNewUserResult = $cNewUser->NewUserFromParameters($mysqli, $oPOSTData->modifies->formerlyName, $oPOSTData->modifies->defaultPhone,
									$oPOSTData->modifies->defaultEmail,	
									isset($oPOSTData->modifies->defaultAddress) ? $oPOSTData->modifies->defaultAddress : "",
									$oPOSTData->modifies->password,
									false,
									$oPOSTData->modifies->vkID, $oPOSTData->modifies->passportNum);
				
				$mysqli->close();	
			}
	}

switch($iNewUserResult)
	{
		case USER_OK:
			ReturnSuccess();
		case USER_NO_PARAMS:
			DropWithBadRequest("Not enough parameters");
		case USER_DB_ERROR:
			DropWithServerError("DB error");
		case USER_EXISTS:
			DropWithServerError("User already exists");
	}

?>
