<?php

require_once "./service/config.php";
require_once "./service/service.php";

class Parcel
	{
		public $parcelID				=	0;
		public $parcelTimeStamp			=	0;
		public $parcelCreatorID			=	0;
		public $parcelIsCreatorCourier	=	false;
		public $parcelSenderID			=	0;
		public $parcelRecepientID		= 	0;
		public $parcelSenderAddress		= 	"";
		public $parcelRecepientAddress	= 	"";
		public $parcelSenderCoordLat	=	0;
		public $parcelSenderCoordLon	=	0;
		public $parcelRecepientCoordLat	=	0;
		public $parcelRecepientCoordLon	=	0;
		public $parcelWeight			=	0;
		public $parcelLength			=	0;
		public $parcelHeight			=	0;
		public $parcelWidth				=	0;
		public $parcelPrice				=	0;
		public $parcelMaxArrival		=	0;
		public $parcelValue				=	0;
		public $parcelComment			=	"";
		
		public $parcelCurrentCourier	=	0;
		
		public $objectOK				=	false;
		
		private $dirtyData				=	false;
		
		//////////////////////////////////////////////////////////////////////////////////////////////////	
			
		public function NewParcelFromParameters($oDBHandler,
								$iCreatorID = 0,
								$bIsCreatorCourier = false,
								$iSenderID = 0,
								$iRecepientID = 0,
								$sSenderAddress = "",
								$sRecepientAddress = "", 
								$fParcelSenderCoordsLat = 0,
								$fParcelSenderCoordsLon = 0,
								$fParcelRecepientCoordsLat = 0,
								$fParcelRecepientCoordsLon = 0,
								$fParcelWeight = 0,
								$fParcelLength = 0,
								$fParcelHeight = 0,
								$fParcelWidth = 0,
								$fParcelPrice = 0,
								$iParcelMaxArrival = 0,
								$fParcelValue = 0,
								$fParcelComment = "")
			/*
			 * @param MYSQLI	mysqli connect handler
			 * 
			 */
			{				
				if (($iSenderID == 0) or ($iRecepientID == 0) or ($sSenderAddress == "") or ($sRecepientAddress == "")
					or ($fParcelWeight <= 0) or ($fParcelLength <= 0) or ($fParcelHeight <= 0) or ($fParcelWidth <= 0)
					or ($fParcelPrice <= 0))
					return PARCEL_NO_PARAMS;
				
				$iCreatorID = intval($iCreatorID);
				$bIsCreatorCourier = ($bIsCreatorCourier ? "1" : "0");
				$iSenderID = intval($iSenderID);
				$iRecepientID = intval($iRecepientID);
				$sSenderAddress = $oDBHandler->real_escape_string($sSenderAddress);
				$sRecepientAddress = $oDBHandler->real_escape_string($sRecepientAddress);
				
				$fParcelSenderCoordsLat = floatval($fParcelSenderCoordsLat);
				$fParcelSenderCoordsLon = floatval($fParcelSenderCoordsLon);
				$fParcelRecepientCoordsLat = floatval($fParcelRecepientCoordsLat);
				$fParcelRecepientCoordsLon = floatval($fParcelRecepientCoordsLon);
				
				$fParcelWeight = floatval($fParcelWeight);
				$fParcelLength = floatval($fParcelLength);
				$fParcelHeight = floatval($fParcelHeight);
				$fParcelWidth = floatval($fParcelWidth);
				$fParcelPrice = floatval($fParcelPrice);
				$iParcelMaxArrival = intval($iParcelMaxArrival);
				$fParcelValue = floatval($fParcelValue);
				$sParcelComment = $oDBHandler->real_escape_string($sParcelComment);
				
				$sNewParcelQuery = "INSERT INTO `" . DB_PARCELS_TABLE . "` (`creator_id`, `is_creator_courier`, `sender_id`" .
					", `recepient_id`, `sender_address`, `recepient_address`, `sender_coords`, `recepient_coords`, `weight`" .
					", `value`, `length`, `width`, `height`, `price`, `max_arrival`, `comment`) " .
					"VALUES (" . $iCreatorID . ", " . $bIsCreatorCourier . ", " . $iSenderID . ", " . $iRecepientID . ", " .
					"\"" . $sSenderAddress . "\", \"" . $sRecepientAddress . "\", POINT(" . $fParcelSenderCoordsLat . ", " . 
					$fParcelSenderCoordsLon . "), POINT(" . $fParcelRecepientCoordsLat . ", " . $fParcelRecepientCoordsLon . "), " .
					$fParcelWeight . ", " . $fParcelValue . ", " . $fParcelLength . ", " . $fParcelWidth . ", " . $fParcelHeight . ", " .
					$fParcelPrice . ", FROM_UNIXTIME(" . $iParcelMaxArrival . "), \"" . $sParcelComment . "\")";
					
				$oInsertResult = $oDBHandler->query($sNewParcelQuery);
				
				if ($oDBHandler->error)
					return PARCEL_EXISTS;
				
				if ($oDBHandler->affected_rows > 0)
					{
						$this->objectOK = true;
						$this->parcelID = $oDBHandler->insert_id;
						
						$this->parcelTimeStamp = time();
						$this->parcelCreatorID = $iCreatorID;
						$this->parcelIsCreatorCourier = $bIsCreatorCourier;
						$this->parcelSenderID = $iSenderID;
						$this->parcelRecepientID = $iRecepientID;
						$this->parcelSenderAddress = $sSenderAddress;
						$this->parcelRecepientAddress = $sRecepientAddress;
						
						$this->parcelSenderCoordLat = $fParcelSenderCoordsLat;
						$this->parcelSenderCoordLon = $fParcelSenderCoordsLon;
						$this->parcelRecepientCoordLat = $fParcelRecepientCoordsLat;
						$this->parcelRecepientCoordLon = $fParcelRecepientCoordsLon;
						
						$this->parcelWeight = $fParcelWeight;
						$this->parcelHeight = $fParcelHeight;
						$this->parcelLength = $fParcelLength;
						$this->parcelWidth = $fParcelWidth;
						$this->parcelPrice = $fParcelPrice;
						$thid->parcelMaxArrival = $iParcelMaxArrival;
						$this->parcelValue = $fParcelValue;
						
						$this->parcelComment = $sCourierComment;
						return $this->parcelID;
					}
				else
					return PARCEL_DB_ERROR;
			}
				
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function DeleteParcel($oDBHandler)
		{
			if ((!$this->objectOK) or ($this->parcelID < 1))
				return PARCEL_NO_PARAMS;
			
			$sDeleteQuery = "DELETE FROM `" . DB_PARCELS_TABLE . "` WHERE `id` = " . intval($this->parcelID);
			$oDBHandler->query($sDeleteQuery);
			
			if ($oDBHandler->affected_rows > 0)
				{
					$this->objectOK = false;
					$this->dirtyData = false;
				}
			
			if ($oDBHandler->affected_rows == 1)
				return PARCEL_OK;
			else
				return PARCEL_DB_ERROR;
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function AddParcelEvent($oDBHandler, $iCourierID = 0, $iOperationID = 0, $iOperationParam1 = 0,
							$iOperationParam2 = 0, $fCoordLat = 0, $fCoordLon = 0, $sPlaceName = "")
			{
				if (($this->parcelID == 0) or ($iOperationID == 0))
					return PARCEL_NO_PARAMS;
				
				// parse
				$iCourierID = intval($iCourierID);
				$iOperationID = intval($iOperationID);
				$iOperationParam1 = intval($iOperationParam1);
				$iOperationParam2 = intval($iOperationParam2);
				$fCoordLat = floatval($fCoordLat);
				$fCoordLon = floatval($fCoordLon);
				$sPlaceName = $oDBHandler->real_escape_string($sPlaceName);
				
				$sAddEventQuery = "INSERT INTO `" . DB_EVENT_TABLE . "` (`coords`, `place_name`, `parcel_id`, " .
							"`courier_id`, `operation_id`, `operation_param1`, `operation_param2`) VALUES (" .
							"POINT(" . $fCoordLat. ", " . $fCoordLon . "), \"" . $sPlaceName . "\", " . intval($this->parcelID) . ", " .
							$iCourierID . ", " . $iOperationID . ", " . $iOperationParam1 . ", " . $iOperationParam2 . ")";
				
				$oDBHandler->query($sAddEventQuery);
				
				
				//print($sAddEventQuery);
				//print($oDBHandler->error);
				
				if (($oDBHandler->affected_rows > 0) and !$oDBHandler->error)
					return PARCEL_OK;
				else
					return PARCEL_DB_ERROR;
			}
			
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function FetchParcelEvents($oDBHandler, $iCourierID = 0, $iOperationID = 0, $iOperationParam1 = 0,
							$iOperationParam2 = 0, $fCoordLat = -1, $fCoordLon = -1, $sPlaceName = "", 
							$iOffset = 0, $iLimit = 0, $bDESC)
			{
				// escaping
				$iCourierID = intval($iCourierID);
				$iOperationID = intval($iOperationID);
				$iOperationParam1 = intval($iOperationParam1);
				$iOperationParam2 = intval($iOperationParam2);
				$fCoordLat = floatval($fCoordLat);
				$fCoordLon = floatval($fCoordLon);
				$sPlaceName = $oDBHandler->real_escape_string($sPlaceName);
				
				$sSearchClause = "";
				
				// compile search clause
				if ($iCourierID > 0)
					$sSearchClause .= "(`courier_id` = " . $iCourierID . ")";
				
				if ($iOperationID > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(`operation_id` = " . $iOperationID . ")";
					}
				
				if ($iOperationParam1 > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(`operation_param1` = " . $iOperationParam1 . ")";
					}
				
				if ($iOperationParam2 > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(`operation_param2` = " . $iOperationParam2 . ")";
					}
				
				if ($fCoordLat > -1)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(X(coords) = " . $fCoordLat . ")";
					}
					
				if ($fCoordLon > -1)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(Y(coords) = " . $fCoordLon . ")";
					}
					
				if ($sPlaceName != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(`place_name` = " . $sPlaceName . ")";
					}
				
				$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
				$sSearchClause .= "(`parcel_id` = " . intval($this->parcelID) . ")";
				
				$sLimitClause = "";
				
				if ($iLimit > 0)
					{
						$sLimitClause = "LIMIT " . intval($iOffset) . ", " . intval($iLimit);
					}
				
				$sOrderClause = "ASC";
				if ($bDESC)
					$sOrderClause = "DESC";
				
				$sFetchEventQuery = "SELECT *, X(coords) AS x_coord, Y(coords) AS y_coord, UNIX_TIMESTAMP(timestamp) AS u_time " .
									"FROM `" . DB_EVENT_TABLE . 
									"` LEFT JOIN `" . DB_OPERATIONS_TABLE . "` ON operations.id = " . DB_EVENT_TABLE . ".operation_id " .
									"WHERE " . $sSearchClause . " ORDER BY timestamp " . $sOrderClause . " " . $sLimitClause;
				
				$oAnswer = $oDBHandler->query($sFetchEventQuery);
				$oResult = array();
				
				while($oRow = $oAnswer->fetch_assoc())
					{
						$oResult[] = $oRow;
					}
					
				return $oResult;
			}
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function ParcelEventsCount($oDBHandler, $iCourierID = 0, $iOperationID = 0, $iOperationParam1 = 0,
							$iOperationParam2 = 0, $fCoordLat = -1, $fCoordLon = -1, $sPlaceName = "", 
							$iOffset = 0, $iLimit = 0, $bDESC)
			{
				// escaping
				$iCourierID = intval($iCourierID);
				$iOperationID = intval($iOperationID);
				$iOperationParam1 = intval($iOperationParam1);
				$iOperationParam2 = intval($iOperationParam2);
				$fCoordLat = floatval($fCoordLat);
				$fCoordLon = floatval($fCoordLon);
				$sPlaceName = $oDBHandler->real_escape_string($sPlaceName);
				
				$sSearchClause = "";
				
				// compile search clause
				if ($iCourierID > 0)
					$sSearchClause .= "(`courier_id` = " . $iCourierID . ")";
				
				if ($iOperationID > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(`operation_id` = " . $iOperationID . ")";
					}
				
				if ($iOperationParam1 > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(`operation_param1` = " . $iOperationParam1 . ")";
					}
				
				if ($iOperationParam2 > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(`operation_param2` = " . $iOperationParam2 . ")";
					}
				
				if ($fCoordLat > -1)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(X(coords) = " . $fCoordLat . ")";
					}
					
				if ($fCoordLon > -1)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(Y(coords) = " . $fCoordLon . ")";
					}
					
				if ($sPlaceName != "")
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(`place_name` = " . $sPlaceName . ")";
					}
				
				$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
				$sSearchClause .= "(`parcel_id` = " . intval($this->parcelID) . ")";
				
				$sFetchEventQuery = "SELECT count(*) AS cnt " .
									"FROM `" . DB_EVENT_TABLE . "` " .
									"WHERE " . $sSearchClause;

				$oAnswer = $oDBHandler->query($sFetchEventQuery);
				$oRow = $oAnswer->fetch_assoc();
					
				return $oRow["cnt"];
			}
			
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function CurrentCourier($oDBHandler)
			{
				
				if ((!$this->objectOK) or ($this->parcelID <= 0))
					return PARCEL_NOT_FOUND;
				
				$sSearchQuery = "SELECT * FROM `" . DB_EVENT_TABLE . "` WHERE `parcel_id` = " . $this->parcelID .
								" AND `operation_id` IN (" . OPERATION_PARCEL_COURIER_ASSIGN . ", " . OPERATION_PARCEL_COURIER_TO_COURIER . 
								", " . OPERATION_PARCEL_FROM_USER . ", " . OPERATION_PARCEL_TO_USER . ") ORDER BY timestamp DESC LIMIT 1";
				$oSearchResult = $oDBHandler->query($sSearchQuery);
			
				if ($oDBHandler->affected_rows > 0)
					{
						$oResultRows = $oSearchResult->fetch_assoc();
						return $oResultRows["operation_param1"];
					}
				else
					return PARCEL_NOT_FOUND;
				
			}
			
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function ParcelFromID($oDBHandler, $iParcelID)
		{
			$sSearchQuery = "SELECT *, X(sender_coords) as sender_coords_lat, Y(sender_coords) as sender_coords_lon, " .
							"X(recepient_coords) as recepient_coords_lat, Y(recepient_coords) as recepient_coords_lon, " .
							"UNIX_TIMESTAMP(max_arrival) AS u_ma FROM `" . DB_PARCELS_TABLE . "` WHERE `id` = " . intval($iParcelID);
			
			$oSearchResult = $oDBHandler->query($sSearchQuery);
			if ($oDBHandler->affected_rows > 0)
				{
					$oRow = $oSearchResult->fetch_assoc();				

					$this->objectOK = true;
					$this->parcelID = $oRow["id"];
					
					$this->parcelTimeStamp = $oRow["timestamp"];
					$this->parcelCreatorID = $oRow["creator_id"];
					$this->parcelIsCreatorCourier = ($oRow["is_creator_courier"] == 1 ? true : false);
					$this->parcelSenderID = $oRow["sender_id"];
					$this->parcelRecepientID = $oRow["recepient_id"];
					$this->parcelSenderAddress = $oRow["sender_address"];
					$this->parcelRecepientAddress = $oRow["recepient_address"];
					
					$this->parcelSenderCoordLat = $oRow["sender_coords_lat"];
					$this->parcelSenderCoordLon = $oRow["sender_coords_lon"];
					$this->parcelRecepientCoordLat = $oRow["recepient_coords_lat"];
					$this->parcelRecepientCoordLon = $oRow["recepient_coords_lon"];
					
					$this->parcelWeight = $oRow["weight"];
					$this->parcelHeight = $oRow["height"];
					$this->parcelLength = $oRow["length"];
					$this->parcelWidth = $oRow["width"];
					$this->parcelPrice = $oRow["price"];
					$this->parcelMaxArrival = $oRow["u_ma"];
					$this->parcelValue = $oRow["value"];
					
					$this->parcelComment = $oRow["comment"];
					return PARCEL_OK;
				}
			else
				{
					$this->objectOK = false;
					return PARCEL_NOT_FOUND;
				}
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function SaveParcel($oDBHandler)
		{
			if ((!$this->objectOK) or ($this->parcelID < 1))
				return USER_NO_PARAMS;
			
			$sEditParcelQuery = "UPDATE `" . DB_PARCELS_TABLE . "` SET " .
								"`creator_id` = " . intval($this->parcelCreatorID) . ", " .
								"`is_creator_courier` = " . ($this->parcelIsCreatorCourier ? 1 : 0) . ", " .
								"`sender_id` = " . intval($this->parcelSenderID) . ", " .
								"`recepient_id` = " . intval($this->parcelRecepientID) . ", " .
								"`sender_address` = \"" . $oDBHandler->real_escape_string($this->parcelSenderAddress) . "\", " .
								"`recepient_address` = \"" . $oDBHandler->real_escape_string($this->parcelRecepientAddress) . "\", " .
								"`sender_coords` = POINT(" . floatval($this->parcelSenderCoordLat) . ", " . floatval($this->parcelSenderCoordLon) . "), " .
								"`recepient_coords` = POINT(" . floatval($this->parcelRecepientCoordLat) . ", " . floatval($this->parcelRecepientCoordLon) . "), " .
								"`weight` = " . floatval($this->parcelWeight) . ", " .
								"`value` = " . floatval($this->parcelValue) . ", " .
								"`length` = " . floatval($this->parcelLength) . ", " .
								"`width` = " . floatval($this->parcelWidth) . ", " .
								"`height` = " . floatval($this->parcelHeight) . ", " .
								"`price` = " . floatval($this->parcelPrice) . ", " .
								"`max_arrival` = FROM_UNIXTIME(" . intval($this->parcelMaxArrival) . "), " .
								"`comment` = \"" . $oDBHandler->real_escape_string($this->parcelComment) . "\" " .
								" WHERE `id` = " . intval($this->parcelID);
			
			$oDBHandler->query($sEditParcelQuery);			
			print($oDBHandler->error);
			if ($oDBHandler->error)
				return USER_DB_ERROR;
			
			if ($oDBHandler->affected_rows == 1)
				return USER_OK;
			else
				return USER_DB_ERROR;
		}
		
		////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function ParcelsFromSearch($oDBHandler, $iTimestampFrom = 0, $iTimestampTo = 0, $aCouriers = array(),
						$aSenders = array(), $aRecepients = array(), $bAllParcels,
						$limit = 0, $offset = 0)
			{
			// compile search clause
				$sSearchClause = "";
				
				if ($iTimestampFrom > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(UNIX_TIMESTAMP(parcels.timestamp) >= " . intval($iTimestampFrom) . ")";
					}
				
				if ($iTimestampTo > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(UNIX_TIMESTAMP(parcels.timestamp) <= " . intval($iTimestampTo) . ")";
					}
				
				if (count($aCouriers) > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(IFNULL(history.operation_param1,0) IN (" . implode(", ", $aCouriers) . "))";
					}
				
				
				if (count($aSenders) > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(parcels.sender_id IN (" . implode(", ", $aSenders) . "))";
					}
				
				if (count($aRecepients) > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(parcels.recepient_id IN (" . implode(", ", $aRecepients) . "))";
					}
				
				// compiling limit 
				$sLimitClause = "";
				if (intval($offset) > 0)
					{
						$sLimitClause = "LIMIT " . $offset;
						if (intval($limit) > 0)
							$sLimitClause .= ", " . $limit;
					}
				
				$sSearchQuery = "SELECT parcels.*, UNIX_TIMESTAMP(max_arrival) AS u_ma, history.operation_param1 FROM `" . DB_PARCELS_TABLE . "` " .
									"LEFT JOIN `history` ON history.parcel_id = parcels.id " .
										"AND " .
											"(history.id = " .
												"(SELECT MAX(id) FROM history WHERE " .
													"(IFNULL(history.operation_id,0) IN (" .
															OPERATION_NO_OPERATION . ", " .
															OPERATION_PARCEL_COURIER_ASSIGN . ", " .
															OPERATION_PARCEL_COURIER_TO_COURIER . ", " .
															OPERATION_PARCEL_FROM_USER . ", " .
															OPERATION_PARCEL_TO_COURIER . ", " .
															OPERATION_PARCEL_INFO . ")) " .
														" AND " .
													"(history.parcel_id = parcels.id)" .
												")) ".
								"WHERE " . $sSearchClause . " " . $sLimitClause . " GROUP BY parcels.id";
				
				
				//$sSearchQuery = "SELECT parcels.* FROM `" . DB_PARCELS_TABLE . "` " .
					//			"WHERE " . $sSearchClause . " " . $sLimitClause . " GROUP BY parcels.id";
				
				$oSearchResult = $oDBHandler->query($sSearchQuery);
				//print($sSearchQuery);
				if ($oDBHandler->error)
					return USER_DB_ERROR;
				
				// compile ret array
				$aParcels = array();
				
				while($oRow = $oSearchResult->fetch_assoc())
					{
						$oTemp = new Parcel();
						$oTemp->parcelID = $oRow["id"];
						
						$oTemp->parcelTimeStamp = intval($oRow["timestamp"]);
						$oTemp->parcelCreatorID = intval($oRow["creator_id"]);
						$oTemp->parcelIsCreatorCourier = ($oRow["is_creator_courier"] == 1 ? true : false);
						$oTemp->parcelSenderID = intval($oRow["sender_id"]);
						$oTemp->parcelRecepientID = intval($oRow["recepient_id"]);
						$oTemp->parcelSenderAddress = $oRow["sender_address"];
						$oTemp->parcelRecepientAddress = $oRow["recepient_address"];
						
						@$oTemp->parcelSenderCoordLat = floatval($oRow["sender_coords_lat"]);
						@$oTemp->parcelSenderCoordLon = floatval($oRow["sender_coords_lon"]);
						@$oTemp->parcelRecepientCoordLat = floatval($oRow["recepient_coords_lat"]);
						@$oTemp->parcelRecepientCoordLon = floatval($oRow["recepient_coords_lon"]);
						
						$oTemp->parcelWeight = floatval($oRow["weight"]);
						$oTemp->parcelHeight = floatval($oRow["height"]);
						$oTemp->parcelLength = floatval($oRow["length"]);
						$oTemp->parcelWidth = floatval($oRow["width"]);
						$oTemp->parcelPrice = floatval($oRow["price"]);
						$oTemp->parcelMaxArrival = $oRow["u_ma"];
						$oTemp->parcelValue = floatval($oRow["value"]);
						
						$oTemp->parcelComment = $oRow["comment"];
						$oTemp->parcelCurrentCourier = intval($oRow["operation_param1"]);
						$aParcels[] = $oTemp;
					}
				return $aParcels;
			}
		
		/////////////////////////////////////////////////////////////////////////////////////////////////
		
		public function ParcelsCountFromSearch($oDBHandler, $iTimestampFrom = 0, $iTimestampTo = 0, $aCouriers = array(),
						$aSenders = array(), $aRecepients = array(), $bAllParcels,
						$limit = 0, $offset = 0)
			{
			// compile search clause
				$sSearchClause = "";
				
				if ($iTimestampFrom > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(UNIX_TIMESTAMP(parcels.timestamp) >= " . intval($iTimestampFrom) . ")";
					}
				
				if ($iTimestampTo > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(UNIX_TIMESTAMP(parcels.timestamp) <= " . intval($sCourierEmail) . ")";
					}
				
				if (count($aCouriers) > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(IFNULL(history.operation_param1,0) IN (" . implode(", ", $aCouriers) . "))";
					}
				
				if (count($aSenders) > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(parcels.sender_id IN (" . implode(", ", $aSenders) . "))";
					}
				
				if (count($aRecepients) > 0)
					{
						$sSearchClause .= ($sSearchClause == "" ? "" : " AND ");
						$sSearchClause .= "(parcels.recepient_id IN (" . implode(", ", $aRecepients) . "))";
					}
				
				// compiling limit 
				$sLimitClause = "";
				if (intval($offset) > 0)
					{
						$sLimitClause = "LIMIT " . $offset;
						if (intval($limit) > 0)
							$sLimitClause .= ", " . $limit;
					}
				
				$sSearchQuery = "SELECT COUNT(*) AS cnt FROM (SELECT parcels.*, history.operation_param1 FROM `" . DB_PARCELS_TABLE . "` " .
									"LEFT JOIN `history` ON history.parcel_id = parcels.id " .
										"AND " .
											"(history.id = " .
												"(SELECT MAX(id) FROM history WHERE " .
													"(IFNULL(history.operation_id,0) IN (" .
															OPERATION_NO_OPERATION . ", " .
															OPERATION_PARCEL_COURIER_ASSIGN . ", " .
															OPERATION_PARCEL_COURIER_TO_COURIER . ", " .
															OPERATION_PARCEL_FROM_USER . ", " .
															OPERATION_PARCEL_TO_COURIER . ", " .
															OPERATION_PARCEL_INFO . ")) " .
														" AND " .
													"(history.parcel_id = parcels.id)" .
												")) ".
								"WHERE " . $sSearchClause . " " . $sLimitClause . " GROUP BY parcels.id) AS counter";

				$oSearchResult = $oDBHandler->query($sSearchQuery);
				
				if ($oDBHandler->error)
					return USER_DB_ERROR;
				
				$oRow = $oSearchResult->fetch_assoc();
				
				return $oRow["cnt"];
			}
		
		/////////////////////////////////////////////////////////////////////////////////////////////////
	}

?>
