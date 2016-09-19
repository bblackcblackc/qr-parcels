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
//require_once "./service/user_class.php";
require_once "./service/tariff_class.php";

// connect to DB
$mysqli = new mysqli(DB_HOST,DB_RW_LOGIN,DB_RW_PASSWORD,DB_NAME);

// check DB connection
if ($mysqli->connect_errno)
	DropWithServerError();

$oTariff = new Tariff();

$aTariffs = $oTariff->TariffsList($mysqli);

// compile out data

$aResultDataSet = array();

foreach($aTariffs as $oTariff)
	{
		$aResultDataSet[] = array(
				"id" => $oTariff->tariffID,
				"tariffName" => $oTariff->tariffName,
				"tariffPriceCoeff" => $oTariff->tariffPriceCoeff,
				"tariffTimeCoeff" => $oTariff->tariffTimeCoeff
			);
	}

$aResultOut = array(
			"success" => "success",
			"data" => $aResultDataSet
		);

http_response_code(200);		
header('Content-Type: application/json');
print(json_encode($aResultOut));

?>
