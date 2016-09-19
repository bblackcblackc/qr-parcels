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

// check if auth presence
if ((!isset($oPOSTData->auth->login)) or (!isset($oPOSTData->auth->password)))
	{
		DropWithUnAuth();
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
	{
		// no admin rights, we can only delete self own account
		if (isset($oPOSTData->data))
			{
				// here we check
				$bEnableDeletion = false;
				if (isset($oPOSTData->data->passportNum))
					if ($oPOSTData->data->passportNum == $cUser->userPassport)
						$bEnableDeletion = true;
						
				if (isset($oPOSTData->data->defaultEmail))
					if ($oPOSTData->data->defaultEmail == $cUser->userEMail)
						$bEnableDeletion = true;
						
				if (isset($oPOSTData->data->defaultPhone))
					if ($oPOSTData->data->defaultPhone == $cUser->userPhone)
						$bEnableDeletion = true;
						
				if (isset($oPOSTData->data->vkID))
					if ($oPOSTData->data->vkID == $cUser->userVKID)
						$bEnableDeletion = true;
						
				if ($bEnableDeletion)
					{
						// delete self
						$iResult = $cUser->DeleteUser($mysqli);
						$mysqli->close();
						if ($iResult == USER_OK)
							ReturnSuccess();
						else
							DropWithServerError();
					}
				else
					{
						$mysqli->close();
						DropWithForbidden();
					}
			}
		else
			{
				// parameter required
				$mysqli->close();
				DropWithBadRequest("Not enough or wrong parameters");
			}
	}
else
	{
		// we have admin rights. deleting what you wish.
		// check for parameters presence
		if (!isset($oPOSTData->data->passportNum) and !isset($oPOSTData->data->defaultEmail)
			and !isset($oPOSTData->data->defaultPhone) and !isset($oPOSTData->data->vkID) and !isset($oPOSTData->data->id))
				{
					$mysqli->close();
					DropWithBadRequest("Not enough or wrong parameters");
				}
		
		// here we delete
		$searchUser = new User();
		
		if (isset($oPOSTData->data->id))
			$iSearchResult = $searchUser->UserFromID($mysqli,$oPOSTData->data->id);
		else
			$iSearchResult = $searchUser->UserFromSearch($mysqli,$oPOSTData->data->defaultPhone, $oPOSTData->data->defaultEmail,
						$oPOSTData->data->vkID, $oPOSTData->data->passportNum);
						
		if ($iSearchResult == USER_OK)
			{
				$iResult = $searchUser->DeleteUser($mysqli);
				$mysqli->close();
		
				if ($iResult == USER_OK)
					ReturnSuccess();
				else
					DropWithServerError();				
			}
		else
			DropWithNotFound();
	}

?>
