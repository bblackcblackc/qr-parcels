<?php

require_once "./service/config.php";
require_once "./service/service.php";

class Courier
	{
		public $courierName		 =	"";
		public $courierEMail	 =	"";
		public $courierPhone	 =	"";
		public $courierMaxWeight =	0.0;
		public $courierMaxLength =	0.0;
		public $courierMaxWidth	 = 	0.0;
		public $courierMaxHeight = 	0.0;
		public $courierComment	 = 	"";
		public $objectOK		 =	false;
		public $courierID		 =	0;
		
		private $dirtyData	=	false;
		
		
		public function CourierFromAuth($oDBHandler, $sLogin = "", $sPassword = "")
			/*
			 * @param MYSQLI	mysqli connect handler
			 * 
			 */
			{
				if (($sLogin == "") or ($sPassword == ""))
					return USER_NO_AUTH;
				
				$sEscapedLogin = $oDBHandler->real_escape_string($sLogin);
				$sEscapedPassword = $oDBHandler->real_escape_string($sPassword);
				
				$sSearchQuery = "SELECT * FROM `" . DB_COURIERS_TABLE . "` WHERE (((`email` = \"" . $sEscapedLogin . "\") " .
				" OR (`phone` = \"" . $sEscapedLogin . "\")) " .
				"AND (`password` = PASSWORD(\"" . $sEscapedPassword . "\")))";
				
				$oSearchResult = $oDBHandler->query($sSearchQuery);
				
				if ($oDBHandler->error)
					return USER_NO_AUTH;
					
				if ($oDBHandler->affected_rows < 1)
					return USER_NO_AUTH;
				
				$oRow = $oSearchResult->fetch_assoc();
				
				$this->objectOK = true;
				$this->courierID = $oRow["id"];
				$this->courierName = $oRow["fio"];
				$this->courierEMail = $oRow["email"];
				$this->courierPhone = $oRow["phone"];
				$this->courierMaxWeight = $oRow["max_weight"];
				$this->courierMaxHeight = $oRow["max_height"];
				$this->courierMaxLength = $oRow["max_length"];
				$this->courierMaxWidth = $oRow["max_width"];
				$this->courierComment = $oRow["comments"];
				
				return USER_OK;
			}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////	
			
		public function NewCourierFromParameters($oDBHandler, $sCourierName, $sCourierPhone, $sCourierEmail, $sCourierPassword, 
						$fCourierMaxWeight = 0.0, $fCourierMaxHeight = 0.0, $fCourierMaxLength = 0.0, $fCourierMaxWidth = 0.0,
						$sCourierComment = "")
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
				if (($sCourierName == "") or (($sCourierEmail == "") and ($sCourierPhone == "")) or ($sCourierPassword == ""))
					return USER_NO_PARAMS;
				
				$sCourierName = $oDBHandler->real_escape_string($sCourierName);
				$sCourierPhone = $oDBHandler->real_escape_string($sCourierPhone);
				$sCourierEmail = $oDBHandler->real_escape_string($sCourierEmail);
				$fCourierMaxWeight = floatval($fCourierMaxWeight);
				$fCourierMaxHeight = floatval($fCourierMaxHeight);
				$fCourierMaxLength = floatval($fCourierMaxLength);
				$fCourierMaxWidth = floatval($fCourierMaxWidth);
				$sCourierComment = $oDBHandler->real_escape_string($sCourierComment);
				
				$sNewCourierQuery = "INSERT INTO `" . DB_COURIERS_TABLE . "` (`fio`, `phone`, `email`, `password`, `comments`, " .
					"`max_weight`, `max_height`, `max_length`, `max_width`) " .
					"VALUES (\"" . $sCourierName . "\", \"" . $sCourierPhone . "\", \"" . $sCourierEmail . "\", " .
					"PASSWORD(\"" . $sCourierPassword . "\"), \"" . $sCourierComment . "\", " .
					 $fCourierMaxWeight . ", " . $fCourierMaxHeight . ", " . $fCourierMaxLength . ", " . $fCourierMaxWidth . ")";
					
				$oInsertResult = $oDBHandler->query($sNewCourierQuery);
				
				if ($oDBHandler->error)
					return USER_EXISTS;
				
				if ($oDBHandler->affected_rows > 0)
					{
						$this->objectOK = true;
						$this->courierID = $oDBHandler->insert_id;
						$this->courierName = $sCourierName;
						$this->courierEMail = $sCourierEmail;
						$this->courierPhone = $sCourierPhone;
						$this->courierMaxWeight = $fCourierMaxWeight;
						$this->courierMaxHeight = $fCourierMaxHeight;
						$this->courierMaxLength = $fCourierMaxLength;
						$this->courierMaxWidth = $fCourierMaxWidth;
						$this->courierComment = $sCourierComment;
						return USER_OK;
					}
				else
					return USER_DB_ERROR;
			}
			
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function DeleteCourier($oDBHandler)
		{
			if ((!$this->objectOK) or ($this->courierID < 1))
				return USER_NO_PARAMS;
			
			$sDeleteQuery = "DELETE FROM `" . DB_COURIERS_TABLE . "` WHERE `id` = " . intval($this->courierID);
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
		
		public function CourierFromSearch($oDBHandler, $sCourierPhone = "", $sCourierEmail = "")
		{
			$sSearchQuery = "SELECT * FROM `" . DB_COURIERS_TABLE . "` WHERE ";
			$sSearchClause = "";
			
			if ($sCourierPhone != "")
				{
					$sSearchClause .= $sSearchClause == "" ? "" : " AND ";
					$sSearchClause .= "(`phone` = \"" . $oDBHandler->real_escape_string($sCourierPhone) . "\")";
				}
			
			if ($sCourierEmail != "")
				{
					$sSearchClause .= $sSearchClause == "" ? "" : " AND ";
					$sSearchClause .= "(`email` = \"" . $oDBHandler->real_escape_string($sCourierEmail) . "\")";
				}
				
			$sSearchQuery .= $sSearchClause;
			
			$oSearchResult = $oDBHandler->query($sSearchQuery);
			if ($oDBHandler->affected_rows > 0)
				{
					$oRow = $oSearchResult->fetch_assoc();				
					
					$this->objectOK = true;
					$this->courierID = $oRow["id"];
					$this->courierName = $oRow["fio"];
					$this->courierEMail = $oRow["email"];
					$this->courierPhone = $oRow["phone"];
					$this->courierMaxWeight = $oRow["max_weight"];
					$this->courierMaxHeight = $oRow["max_height"];
					$this->courierMaxLength = $oRow["max_length"];
					$this->courierMaxWidth = $oRow["max_width"];
					$this->courierComment = $oRow["comments"];
					
					return USER_OK;
				}
			else
				{
					$this->objectOK = false;
					return USER_NOT_FOUND;
				}
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function CourierFromID($oDBHandler, $iCourierID)
		{
			$sSearchQuery = "SELECT * FROM `" . DB_COURIERS_TABLE . "` WHERE `id` = " . intval($iCourierID);
			
			$oSearchResult = $oDBHandler->query($sSearchQuery);
			if ($oDBHandler->affected_rows > 0)
				{
					$oRow = $oSearchResult->fetch_assoc();				
					
					$this->objectOK = true;
					$this->courierID = $oRow["id"];
					$this->courierName = $oRow["fio"];
					$this->courierEMail = $oRow["email"];
					$this->courierPhone = $oRow["phone"];
					$this->courierMaxWeight = $oRow["max_weight"];
					$this->courierMaxHeight = $oRow["max_height"];
					$this->courierMaxLength = $oRow["max_length"];
					$this->courierMaxWidth = $oRow["max_width"];
					$this->courierComment = $oRow["comments"];
					
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
			if ((!$this->objectOK) or ($this->courierID < 1))
				return USER_NO_PARAMS;
			
			$sChPassQuery = "UPDATE `" . DB_COURIERS_TABLE . "` SET `password` = PASSWORD(\"" . $oDBHandler->real_escape_string($sNewPass) .
								"\") WHERE `id` = " . intval($this->courierID);
			$oDBHandler->query($sChPassQuery);
			
			if ($oDBHandler->affected_rows == 1)
				return USER_OK;
			else
				return USER_DB_ERROR;
		}
		//
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function SaveCourier($oDBHandler)
		{
			if ((!$this->objectOK) or ($this->courierID < 1))
				return USER_NO_PARAMS;
			
			$sEditCourierQuery = "UPDATE `" . DB_COURIERS_TABLE . "` SET " .
								"`fio` = \"" . $oDBHandler->real_escape_string($this->courierName) . "\", " .
								"`email` = \"" . $oDBHandler->real_escape_string($this->courierEMail) . "\", " .
								"`phone` = \"" . $oDBHandler->real_escape_string($this->courierPhone) . "\", " .
								"`max_weight` = " . floatval($this->courierMaxWeight) . ", " .
								"`max_height` = " . floatval($this->courierMaxHeight) . ", " .
								"`max_length` = " . floatval($this->courierMaxLength) . ", " .
								"`max_width` = " . floatval($this->courierMaxWidth) . ", " .
								"`comments` = \"" . $oDBHandler->real_escape_string($this->courierComment) . "\" " .
								" WHERE `id` = " . intval($this->courierID);
			
			$oDBHandler->query($sEditCourierQuery);
			
			if ($oDBHandler->error)
				return USER_DB_ERROR;
			
			if ($oDBHandler->affected_rows == 1)
				return USER_OK;
			else
				return USER_DB_ERROR;
		}
		
		////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function CouriersFromSearch($oDBHandler, $sCourierPhone = "", $sCourierEmail = "", $limit = 0, $offset = 0)
			{
			// compile search clause
				$sSearchClause = "";
				
				if ($sCourierPhone != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`phone` LIKE \"%" . $oDBHandler->real_escape_string($sCourierPhone) . "%\"";
					}
				
				if ($sCourierEmail != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`email` LIKE '%" . $oDBHandler->real_escape_string($sCourierEmail) . "%'";
					}
					
				
				// compiling limit
				$sLimitClause = "";
				if (intval($offset) > 0)
					{
						$sLimitClause = "LIMIT " . $offset;
						if (intval($limit) > 0)
							$sLimitClause .= ", " . $limit;
					}
				
				$sSearchQuery = "SELECT * FROM `" . DB_COURIERS_TABLE . "` WHERE " . $sSearchClause . " " . $sLimitClause;
				$oSearchResult = $oDBHandler->query($sSearchQuery);
				
				if ($oDBHandler->error)
					return USER_DB_ERROR;
				
				// compile ret array
				$aCouriers = array();
				
				while($oRow = $oSearchResult->fetch_assoc())
					{
						$oTemp = new Courier();
						$oTemp->courierID = $oRow["id"];
						$oTemp->courierName = $oRow["fio"];
						$oTemp->courierEMail = $oRow["email"];
						$oTemp->courierPhone = $oRow["phone"];
						$oTemp->courierMaxWeight = $oRow["max_weight"];
						$oTemp->courierMaxHeight = $oRow["max_height"];
						$oTemp->courierMaxLength = $oRow["max_length"];
						$oTemp->courierMaxWidth = $oRow["max_width"];
						$oTemp->courierComment = $oRow["comments"];
						$oTemp->objectOK = true;
						$aCouriers[] = $oTemp;
					}
				return $aCouriers;
			}
		
		/////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function CouriersCountFromSearch($oDBHandler, $sCourierPhone = "", $sCourierEmail = "")
		{
				// compile search clause
				$sSearchClause = "";
				
				if ($sCourierPhone != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`phone` LIKE \"%" . $oDBHandler->real_escape_string($sCourierPhone) . "%\"";
					}
				
				if ($sCourierEmail != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "`email` LIKE '%" . $oDBHandler->real_escape_string($sCourierEmail) . "%'";
					}
				
				$sSearchQuery = "SELECT COUNT(*) AS cnt FROM `" . DB_COURIERS_TABLE . "` WHERE " . $sSearchClause;
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
