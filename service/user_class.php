<?php

require_once "./service/config.php";
require_once "./service/service.php";

class User
	{
		public $isAdmin		 =	false;
		public $userName	 =	"";
		public $userEMail	 =	"";
		public $userPhone	 =	"";
		public $userAddress  =	"";
		public $userPassport =	0;
		public $userVKID	 =	"";
		public $objectOK		=	false;
		public $userID		=	0;
		
		private $dirtyData	=	false;
		
		
		public function UserFromAuth($oDBHandler, $sLogin = "", $sPassword = "")
			/*
			 * @param MYSQLI	mysqli connect handler
			 * 
			 */
			{
				if (($sLogin == "") or ($sPassword == ""))
					return USER_NO_AUTH;
				
				$sEscapedLogin = $oDBHandler->real_escape_string($sLogin);
				$sEscapedPassword = $oDBHandler->real_escape_string($sPassword);
				
				$sSearchQuery = "SELECT * FROM `" . DB_USERS_TABLE . "` WHERE (((`email` = \"" . $sEscapedLogin . "\") " .
				" OR (`vkid` = \"" . $sEscapedLogin . "\") " .
				" OR (`phone` = \"" . $sEscapedLogin . "\")) " .
				"AND (`password` = PASSWORD(\"" . $sEscapedPassword . "\")) " .
				"AND `approved` = 1)";
				
				$oSearchResult = $oDBHandler->query($sSearchQuery);
				
				if ($oDBHandler->error)
					return USER_NO_AUTH;
					
				if ($oDBHandler->affected_rows < 1)
					return USER_NO_AUTH;
				
				$oRow = $oSearchResult->fetch_assoc();
				
				$this->objectOK = true;
				$this->userID = $oRow["id"];
				$this->userName = $oRow["fio"];
				$this->userEMail = $oRow["email"];
				$this->userPhone = $oRow["phone"];
				$this->userAddress = $oRow["address"];
				$this->userPassport = $oRow["passport"];
				$this->userVKID = $oRow["vkid"];
				$this->isAdmin = $oRow["admin"] > 0 ? true : false;
				
				return USER_OK;
			}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////	
			
		public function NewUserFromParameters($oDBHandler, $sUserName, $sUserPhone = "", $sUserEmail, $sUserAddress = "", $sUserPassword, $bAdminFlag = false,
						$sUserVKID = "", $iUserPassport, $iApproved = 1)
			/*
			 * @param MYSQLI	mysqli connect handler
			 * @param string	user name
			 * @param string	user phone
			 * @param string	user email
			 * @param string	user address
			 * @param string	user password
			 * @param bool		user admin flag
			 * @param string	user vk id
			 * @param int		user passport number
			 * 
			 */
			{				
				if (($sUserName == "") or ($sUserEmail == "") or ($sUserPassword == "") or ($iUserPassport == 0))
					return USER_NO_PARAMS;
				
				$sUserName = $oDBHandler->real_escape_string($sUserName);
				$sUserPhone = $oDBHandler->real_escape_string($sUserPhone);
				$sUserEmail = $oDBHandler->real_escape_string($sUserEmail);
				$sUserAddress = $oDBHandler->real_escape_string($sUserAddress);
				$sUserVKID = $oDBHandler->real_escape_string($sUserVKID);
				$sUserPassword = $oDBHandler->real_escape_string($sUserPassword);
				$iUserPassport = intval($iUserPassport);
				$iApproved = intval($iApproved);
				
				$sNewUserQuery = "INSERT INTO `" . DB_USERS_TABLE . "` (`fio`, `address`, `phone`, `email`, `admin`, `vkid`, " .
					"`password`, `passport`, `approved`) " .
					"VALUES (\"" . $sUserName . "\", \"" . $sUserAddress . "\", \"" . $sUserPhone . "\", \"" . $sUserEmail . "\", " .
					($bAdminFlag ? 1 : 0) . ", \"" . $sUserVKID . "\", PASSWORD(\"" . $sUserPassword . "\"), " . $iUserPassport . ", " .
					$iApproved . ")";
					
				$oInsertResult = $oDBHandler->query($sNewUserQuery);
				
				if ($oDBHandler->error)
					return USER_EXISTS;
				
				if ($oDBHandler->affected_rows > 0)
					{
						$this->objectOK = true;
						$this->userID = $oDBHandler->insert_id;
						$this->userName = $sUserName;
						$this->userEMail = $sUserEmail;
						$this->userPhone = $sUserPhone;
						$this->userAddress = $sUserAddress;
						$this->userPassport = $iUserPassport;
						$this->userVKID = $sUserVKID;
						$this->isAdmin = $bAdminFlag;
						return USER_OK;
					}
				else
					return USER_DB_ERROR;
			}
			
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function DeleteUser($oDBHandler)
		{
			if ((!$this->objectOK) or ($this->userID < 1))
				return USER_NO_PARAMS;
			
			$sDeleteQuery = "DELETE FROM `" . DB_USERS_TABLE . "` WHERE `id` = " . intval($this->userID) . " AND `approved` = 1";
			$oDBHandler->query($sDeleteQuery);
			
			if ($oDBHandler->affected_rows > 0)
				{
					$this->objectOK = false;
					$this->dirtyData = false;
				}
			
			if ($oDBHandler->affected_rows == 1)
				return USER_OK;
			else
				return USER_DB_ERROR;
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function UserFromSearch($oDBHandler, $sUserPhone = "", $sUserEmail = "", $sUserVKID = "", $iUserPassport = 0)
		{
			$sSearchQuery = "SELECT * FROM `" . DB_USERS_TABLE . "` WHERE ";
			$sSearchClause = "(`approved` = 1)";
			
			if ($sUserPhone != "")
				{
					$sSearchClause .= $sSearchClause == "" ? "" : " AND ";
					$sSearchClause .= "(`phone` = \"" . $oDBHandler->real_escape_string($sUserPhone) . "\")";
				}
			
			if ($sUserEmail != "")
				{
					$sSearchClause .= $sSearchClause == "" ? "" : " AND ";
					$sSearchClause .= "(`email` = \"" . $oDBHandler->real_escape_string($sUserEmail) . "\")";
				}
				
			if ($sUserVKID != "")
				{
					$sSearchClause .= $sSearchClause == "" ? "" : " AND ";
					$sSearchClause .= "(`vkid` = \"" . $oDBHandler->real_escape_string($sVKID) . "\")";
				}
				
			if ($iUserPassport > 0)
				{
					$sSearchClause .= $sSearchClause == "" ? "" : " AND ";
					$sSearchClause .= "(`passport` = " . intval($iUserPassport) . ")";
				}
				
			$sSearchQuery .= $sSearchClause;
			
			$oSearchResult = $oDBHandler->query($sSearchQuery);
			if ($oDBHandler->affected_rows > 0)
				{
					$oRow = $oSearchResult->fetch_assoc();				
					
					$this->objectOK = true;
					$this->userID = $oRow["id"];
					$this->userName = $oRow["fio"];
					$this->userEMail = $oRow["email"];
					$this->userPhone = $oRow["phone"];
					$this->userAddress = $oRow["address"];
					$this->userPassport = $oRow["passport"];
					$this->userVKID = $oRow["vkid"];
					$this->isAdmin = $oRow["admin"] > 0 ? true : false;
					
					return USER_OK;
				}
			else
				{
					$this->objectOK = false;
					return USER_NOT_FOUND;
				}
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function UserFromID($oDBHandler, $iUserID)
		{
			$sSearchQuery = "SELECT * FROM `" . DB_USERS_TABLE . "` WHERE `id` = " . intval($iUserID) . " AND `approved` = 1";
			
			$oSearchResult = $oDBHandler->query($sSearchQuery);
			if ($oDBHandler->affected_rows > 0)
				{
					$oRow = $oSearchResult->fetch_assoc();				
					
					$this->objectOK = true;
					$this->userID = $oRow["id"];
					$this->userName = $oRow["fio"];
					$this->userEMail = $oRow["email"];
					$this->userPhone = $oRow["phone"];
					$this->userAddress = $oRow["address"];
					$this->userPassport = $oRow["passport"];
					$this->userVKID = $oRow["vkid"];
					$this->isAdmin = $oRow["admin"] > 0 ? true : false;
					
					return USER_OK;
				}
			else
				{
					$this->objectOK = false;
					return USER_NOT_FOUND;
				}
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function ChangePass($oDBHandler, $sNewPass)
		{
			if ((!$this->objectOK) or ($this->userID < 1))
				return USER_NO_PARAMS;
			
			$sChPassQuery = "UPDATE `" . DB_USERS_TABLE . "` SET `password` = PASSWORD(\"" . $oDBHandler->real_escape_string($sNewPass) .
								"\") WHERE `id` = " . intval($this->userID) . " AND `approved` = 1";
			$oDBHandler->query($sChPassQuery);
			
			if ($oDBHandler->affected_rows == 1)
				return USER_OK;
			else
				return USER_DB_ERROR;
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function SaveUser($oDBHandler)
		{
			if ((!$this->objectOK) or ($this->userID < 1))
				return USER_NO_PARAMS;
			
			$sEditUserQuery = "UPDATE `" . DB_USERS_TABLE . "` SET " .
								"`fio` = \"" . $oDBHandler->real_escape_string($this->userName) . "\", " .
								"`passport` = " . intval($this->userPassport) . ", " .
								"`address` = \"" . $oDBHandler->real_escape_string($this->userAddress) . "\", " .
								"`phone` = " . intval($this->userPhone) . ", " .
								"`email` = \"" . $oDBHandler->real_escape_string($this->userEMail) . "\", " .
								"`fio` = \"" . $oDBHandler->real_escape_string($this->userName) . "\", " .
								"`admin` = " . ($this->isAdmin ? 1 : 0) . ", " .
								"`vkid` = \"" . $oDBHandler->real_escape_string($this->userVKID) . "\" " .
								" WHERE `id` = " . intval($this->userID) . " AND `approved` = 1";
			
			$oDBHandler->query($sEditUserQuery);
			
			if ($oDBHandler->error)
				return USER_DB_ERROR;
			
			if ($oDBHandler->affected_rows == 1)
				return USER_OK;
			else
				return USER_DB_ERROR;
		}
		
		////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function UsersFromSearch($oDBHandler, $sUserPhone = "", $sUserEmail = "", $sUserVKID = "", 
							$iUserPassport = 0, $limit = 0, $offset = 0)
			{
			// compile search clause
				$sSearchClause = "(`approved` = 1)";
				
				if ($sUserPhone != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`phone` LIKE \"%" . $oDBHandler->real_escape_string($sUserPhone) . "%\"";
					}
				
				if ($sUserEmail != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`email` LIKE '%" . $oDBHandler->real_escape_string($sUserEmail) . "%'";
					}
					
				if ($sUserVKID != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`vkid` LIKE '%" . $oDBHandler->real_escape_string($sUserVKID) . "%'";
					}
					
				if ($iUserPassport != 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`passport` LIKE '%" . intval($iUserPassport) . "%'";
					}
				
				// compiling limit
				$sLimitClause = "";
				if (intval($offset) > 0)
					{
						$sLimitClause = "LIMIT " . $offset;
						if (intval($limit) > 0)
							$sLimitClause .= ", " . $limit;
					}
				
				$sSearchQuery = "SELECT * FROM `" . DB_USERS_TABLE . "` WHERE " . $sSearchClause . " " . $sLimitClause;
				$oSearchResult = $oDBHandler->query($sSearchQuery);
				
				if ($oDBHandler->error)
					return USER_DB_ERROR;
				
				// compile ret array
				$aUsers = array();
				
				while($oRow = $oSearchResult->fetch_assoc())
					{
						$oTemp = new User();
						$oTemp->userID = $oRow["id"];
						$oTemp->userName = $oRow["fio"];
						$oTemp->userEMail = $oRow["email"];
						$oTemp->userPhone = $oRow["phone"];
						$oTemp->userAddress = $oRow["address"];
						$oTemp->userPassport = $oRow["passport"];
						$oTemp->userVKID = $oRow["vkid"];
						$oTemp->isAdmin = ($oRow["admin"] > 0 ? true : false);
						$oTemp->objectOK = true;
						//print_r($oTemp);
						$aUsers[] = $oTemp;
					}
				return $aUsers;
			}
		
		/////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function UsersCountFromSearch($oDBHandler, $sUserPhone = "", $sUserEmail = "", $sUserVKID = "", 
							$iUserPassport = 0)
		{
				// compile search clause
				$sSearchClause = "(`approved` = 1)";
				
				if ($sUserPhone != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`phone` LIKE \"%" . $oDBHandler->real_escape_string($sUserPhone) . "%\"";
					}
				
				if ($sUserEmail != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`email` LIKE '%" . $oDBHandler->real_escape_string($sUserEmail) . "%'";
					}
					
				if ($sUserVKID != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`vkid` LIKE '%" . $oDBHandler->real_escape_string($sUserVKID) . "%'";
					}
					
				if ($iUserPassport != 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`passport` LIKE '%" . intval($iUserPassport) . "%'";
					}
				
				$sSearchQuery = "SELECT COUNT(*) AS cnt FROM `" . DB_USERS_TABLE . "` WHERE " . $sSearchClause;
				$oSearchResult = $oDBHandler->query($sSearchQuery);
				
				if ($oDBHandler->error)
					return USER_DB_ERROR;
				
				$oRow = $oSearchResult->fetch_assoc();
				$iCount = $oRow["cnt"];
				return $iCount;
		}
		
		/////////////////////////////////////////////////////////////////////////////////////////////////
	}

?>
