<?php

require_once 'ami_env.php';

$oResponse = AMI::getSingleton('response');
$oResponse->start();

$oRequest = AMI::getSingleton('env/request');

if ($oRequest->getCookie('ato_payeer_order')) 
{
    require_once dirname(__FILE__) . '/_local/eshop/AtoPaymentSystem.php';
	
    list ($data, $sign) = explode('|', $oRequest->getCookie('ato_payeer_order'), 2);

	$oResponse->HTTP->setCookie('ato_payeer_order', NULL);
	$order = @unserialize(gzuncompress(base64_decode($data)));

	if (is_array($order) && isset($order['success'])) 
	{
		ob_end_clean();
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');
		header('Location: ' . $order['success'], true, 301);
		die;
	}
}

$oResponse->send();
