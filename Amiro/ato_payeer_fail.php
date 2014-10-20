<?php

require_once 'ami_env.php';

$oResponse = AMI::getSingleton('response');
$oResponse->start();

$oRequest = AMI::getSingleton('env/request');

if ($oRequest->getCookie('ato_payeer_order')) {
    require_once dirname(__FILE__) . '/_local/eshop/AtoPaymentSystem.php';

    $secretKey = AtoPaymentSystem::getDriverParameter('ato_payeer', 'payeer_secret_key');
	$log = AtoPaymentSystem::getDriverParameter('ato_payeer', 'payeer_log');

	if ($log == 1)
	{
		$log_text = 
			"--------------------------------------------------------\n".
			"operation id		".$_GET["m_operation_id"]."\n".
			"operation ps		".$_GET["m_operation_ps"]."\n".
			"operation date		".$_GET["m_operation_date"]."\n".
			"operation pay date	".$_GET["m_operation_pay_date"]."\n".
			"shop				".$_GET["m_shop"]."\n".
			"order id			".$_GET["m_orderid"]."\n".
			"amount				".$_GET["m_amount"]."\n".
			"currency			".$_GET["m_curr"]."\n".
			"description		".base64_decode($_POST["m_desc"])."\n".
			"status				".$_GET["m_status"]."\n".
			"sign				".$_GET["m_sign"]."\n\n";
			
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/payeer.log', $log_text, FILE_APPEND);
	}
	
    list ($data, $sign) = explode('|', $oRequest->getCookie('ato_payeer_order'), 2);
    if (md5($data . 'ato_payeer' . $secretKey) === $sign){
        $oResponse->HTTP->setCookie('ato_payeer_order', NULL);
        $order = @unserialize(gzuncompress(base64_decode($data)));
        if (is_array($order) && isset($order['fail'])) 
		{
            ob_end_clean();
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache'); 
            header('Location: ' . $order['fail'], true, 301);
            die;
        }
    }
}

$oResponse->send();
