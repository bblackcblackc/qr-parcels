<?php

require_once "./service/config.php";
require_once "./service/service.php";

class Tariff
	{
		public $tariffName	 		=	"";
		public $tariffPriceCoeff	=	0;
		public $tariffTimeCoeff		=	0;
		public $objectOK			=	false;
		public $tariffID			=	0;
		
		private $dirtyData	=	false;
		
		//////////////////////////////////////////////////////////////////////////////////////////////////	
			
		public function NewTariffFromParameters($oDBHandler, $sTariffName, $fTariffPriceCoeff = 1, $fTariffTimeCoeff = 1)
			/*
			 * @param MYSQLI	mysqli connect handler
			 * @param string	user name
			 * @param float	user phone
			 * @param float	user email
			 * 
			 */
			{				
				if ($sTariffName == "")
					return USER_NO_PARAMS;
				
				$sTariffName = $oDBHandler->real_escape_string($sTariffName);
				$fTariffPriceCoeff = floatval($fTariffPriceCoeff);
				$fTariffTimeCoeff = floatval($fTariffTimeCoeff);
				
				$sNewTariffQuery = "INSERT INTO `" . DB_TARIFFS_TABLE . "` (`name`, `price_coeff`, `time_coeff`) " .
					"VALUES (\"" . $sTariffName . "\", " . $fTariffPriceCoeff . ", " . $fTariffTimeCoeff . ")";
					
				$oInsertResult = $oDBHandler->query($sNewTariffQuery);
				
				if ($oDBHandler->error)
					return USER_EXISTS;
				
				if ($oDBHandler->affected_rows > 0)
					{
						$this->objectOK = true;
						$this->tariffID = $oDBHandler->insert_id;
						$this->tariffName = $sUserName;
						$this->tariffPriceCoeff = $fTariffPriceCoeff;
						$this->tariffTimeCoeff = $fTariffTimeCoeff;
						return USER_OK;
					}
				else
					return USER_DB_ERROR;
			}
			
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function DeleteTariff($oDBHandler)
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
		
		public function TariffFromID($oDBHandler, $iTariffID)
		{
			$sSearchQuery = "SELECT * FROM `" . DB_TARIFFS_TABLE . "` WHERE `id` = " . intval($iTariffID);
			
			$oSearchResult = $oDBHandler->query($sSearchQuery);
			if ($oDBHandler->affected_rows > 0)
				{
					$oRow = $oSearchResult->fetch_assoc();				
					
					$this->objectOK = true;
					$this->tariffID = $oRow["id"];
					$this->tariffName = $oRow["name"];
					$this->tariffPriceCoeff = $oRow["price_coeff"];
					$this->tariffTimeCoeff = $oRow["time_coeff"];
					
					return USER_OK;
				}
			else
				{
					$this->objectOK = false;
					return USER_NOT_FOUND;
				}
		}
		
		////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function TariffsList($oDBHandler)
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
