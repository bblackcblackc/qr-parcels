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
		// no admin rights, we can only edit self own account
		if (isset($oPOSTData->data))
			{
				// here we check
				$bEnableEdit = false;
				if (isset($oPOSTData->data->passportNum))
					if ($oPOSTData->data->passportNum == $cUser->userPassport)
						$bEnableEdit = true;
						
				if (isset($oPOSTData->data->defaultEmail))
					if ($oPOSTData->data->defaultEmail == $cUser->userEMail)
						$bEnableEdit = true;
						
				if (isset($oPOSTData->data->defaultPhone))
					if ($oPOSTData->data->defaultPhone == $cUser->userPhone)
						$bEnableEdit = true;
						
				if (isset($oPOSTData->data->vkID))
					if ($oPOSTData->data->vkID == $cUser->userVKID)
						$bEnableEdit = true;
						
				if ($bEnableEdit)
					{
						// edit self						
						// read parameters
						if (isset($oPOSTData->modifies->formerlyName))
							$cUser->userName = $mysqli->real_escape_string($oPOSTData->modifies->formerlyName);
						
						if (isset($oPOSTData->modifies->passportNum))
							$cUser->userPassport = intval($oPOSTData->modifies->passportNum);
						
						if (isset($oPOSTData->modifies->defaultAddress))
							$cUser->userAddress = intval($oPOSTData->modifies->defaultAddress);
							
						if (isset($oPOSTData->modifies->defaultPhone))
							$cUser->userPhone = intval($oPOSTData->modifies->defaultPhone);
						
						if (isset($oPOSTData->modifies->defaultEmail))
							$cUser->userEMail = intval($oPOSTData->modifies->defaultEmail);
							
						if (isset($oPOSTData->modifies->vkID))
							$cUser->userVKID = intval($oPOSTData->modifies->vkID);
						
						$iResult = $cUser->SaveUser($mysqli);
						
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
		// we have admin rights. edit what you wish.
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
				// read parameters
				if (isset($oPOSTData->modifies->formerlyName))
					$searchUser->userName = $mysqli->real_escape_string($oPOSTData->modifies->formerlyName);
				
				if (isset($oPOSTData->modifies->passportNum))
					$searchUser->userPassport = intval($oPOSTData->modifies->passportNum);
				
				if (isset($oPOSTData->modifies->defaultAddress))
					$searchUser->userAddress = intval($oPOSTData->modifies->defaultAddress);
					
				if (isset($oPOSTData->modifies->defaultPhone))
					$searchUser->userPhone = intval($oPOSTData->modifies->defaultPhone);
				
				if (isset($oPOSTData->modifies->defaultEmail))
					$searchUser->userEMail = intval($oPOSTData->modifies->defaultEmail);
					
				if (isset($oPOSTData->modifies->vkID))
					$searchUser->userVKID = intval($oPOSTData->modifies->vkID);
				
				if (isset($oPOSTData->modifies->isAdmin))
					$searchUser->isAdmin = ($oPOSTData->modifies->isAdmin ? 1 : 0);
					
				$iResult = $searchUser->SaveUser($mysqli);
				
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
