<?php

require_once "./service/config.php";
require_once "./service/service.php";

class Finance
	{
		public $iOperationID	 	= 0;
		public $fOperationValue		= 0;
		public $iOperationUserID	= 0;
		public $iOperationParcelID	= 0;
		public $sOperationDesc		= "";
		public $objectOK			= false;
		
		private $dirtyData	=	false;
		
		//////////////////////////////////////////////////////////////////////////////////////////////////	
			
		public function NewOperation($oDBHandler, $fOperationValue, $iOperationUserID, $iOperationParcelID, $sOperationDesc)
			/*
			 * @param MYSQLI	mysqli connect handler
			 * 
			 */
			{				
				if (($sOperationDesc == "") or ($iOperationUserID <= 0) or ($iOperationParcelID <= 0))
					return USER_NO_PARAMS;
				
				$sOperationDesc = $oDBHandler->real_escape_string($sOperationDesc);
				$fOperationValue = floatval($fOperationValue);
				$iOperationUserID = intval($iOperationUserID);
				$iOperationParcelID = intval($iOperationParcelID);
				
				$sNewOperationQuery = "INSERT INTO `" . DB_FINOPERATIONS_TABLE . "` (`value`, `user_id`, `parcel_id`, `description`) " .
					"VALUES (" . $fOperationValue . ", " . $iOperationUserID . ", " . $iOperationParcelID . ", " .
					"\"" .  $sOperationDesc . "\")";
					
				$oInsertResult = $oDBHandler->query($sNewOperationQuery);
				
				if ($oDBHandler->error)
					return USER_EXISTS;
				
				if ($oDBHandler->affected_rows > 0)
					{
						$this->objectOK = true;
						$this->iOperationID = $oDBHandler->insert_id;
						$this->fOperationValue = $fOperationValue;
						$this->iOperationUserID = $iOperationUserID;
						$this->iOperationParcelID = $iOperationParcelID;
						return USER_OK;
					}
				else
					return USER_DB_ERROR;
			}
			
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function __DeleteTariff($oDBHandler)
		{
			if ((!$this->objectOK) or ($this->tariffID < 1))
				return USER_NO_PARAMS;
			
			$sDeleteQuery = "DELETE FROM `" . DB_TARIFFS_TABLE . "` WHERE `id` = " . intval($this->tariffID);
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
		
		public function OperationFromID($oDBHandler, $iOperationID)
		{
			$sSearchQuery = "SELECT * FROM `" . DB_FINOPERATIONS_TABLE . "` WHERE `id` = " . intval($iOperationID);
			
			$oSearchResult = $oDBHandler->query($sSearchQuery);
			if ($oDBHandler->affected_rows > 0)
				{
					$oRow = $oSearchResult->fetch_assoc();				
					
					$this->objectOK = true;
					$this->iOperationID = $oRow["id"];
					$this->fOperationValue = $oRow["value"];
					$this->iOperationUserID = $oRow["user_id"];
					$this->iOperationParcelID = $oRow["parcel_id"];
					
					return USER_OK;
				}
			else
				{
					$this->objectOK = false;
					return USER_NOT_FOUND;
				}
		}
		
		////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function OperationsList($oDBHandler, $iUserID, $iParcelID = 0)
			{				
				$sSearchQuery = "SELECT * FROM `" . DB_TARIFFS_TABLE . "` WHERE 1";
				$oSearchResult = $oDBHandler->query($sSearchQuery);
				
				if ($oDBHandler->error)
					return USER_DB_ERROR;
				
				// compile ret array
				$aTariffs = array();
				
				while($oRow = $oSearchResult->fetch_assoc())
					{
						$oTemp = new Tariff();
						$oTemp->tariffID = $oRow["id"];
						$oTemp->tariffName = $oRow["name"];
						$oTemp->tariffPriceCoeff = $oRow["price_coeff"];
						$oTemp->tariffTimeCoeff = $oRow["time_coeff"];
						$oTemp->objectOK = true;
						//print_r($oTemp);
						$aTariffs[] = $oTemp;
					}
				return $aTariffs;
			}
		
		/////////////////////////////////////////////////////////////////////////////////////////////////
	}

?>
