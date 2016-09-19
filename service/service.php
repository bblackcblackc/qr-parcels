<?php

function DropWithUnAuth()
    {
		http_response_code(401);
		
		$oAnswer = array(
				"failReason" => "Authorization required"
			);
			
		header('Content-Type: application/json');
		print(json_encode($oAnswer));
		exit(0);
    }

function DropWithForbidden()
    {
		http_response_code(403);
		
		$oAnswer = array(
				"failReason" => "Forbidden"
			);
			
		header('Content-Type: application/json');
		print(json_encode($oAnswer));
		exit(0);
    }

function DropWithServerError($sText)
    {
		http_response_code(500);
		
		$oAnswer = array(
				"failReason" => "Server error" . (($sText != "") ? (", " . $sText) : "")
			);
		
		header('Content-Type: application/json');
		print(json_encode($oAnswer));
		exit(0);
    }

function DropWithBadRequest($sText = "")
    {
		http_response_code(400);
		
		$oAnswer = array(
				"failReason" => "Bad request" . ((isset($sText) and ($sText != "")) ? (", " . $sText) : "")
			);
		
		header('Content-Type: application/json');
		print(json_encode($oAnswer));
		exit(0);
    }

function DropWithNotFound()
    {
		http_response_code(404);
		
		$oAnswer = array(
				"failReason" => "Not Found"
			);
		
		header('Content-Type: application/json');
		print(json_encode($oAnswer));
		exit(0);
    }

function ReturnSuccess($sText = "")
    {
		http_response_code(200);
		
		$oAnswer = array(
				"success" => "success"
			);
			
		if (isset($sText) and ($sText != ""))
			$oAnswer = array_merge($oAnswer, $sText);
		
		header('Content-Type: application/json');
		print(json_encode($oAnswer));
		exit(0);
    }

function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i)
		{
			$str .= $keyspace[rand(0, $max)];
		}
    return $str;
}

?>
