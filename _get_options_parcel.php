<?php

/*
 * @author Anton Dovgan <blackc.blackc@gmail.com>
 * 
 * 
 * @return string	JSON with results
 * 
 */

require_once "./service/config.php";
require_once "./service/service.php";
$sOptions = file_get_contents(CALC_API_BASE_URL . "get_options.php");

http_response_code(200);		
header('Content-Type: application/json');
print($sOptions);

?>
