<?php

require_once 'ami_env.php';


$oResponse = AMI::getSingleton('response');
$oResponse->start();


$oRequest = AMI::getSingleton('env/request');

if ($oRequest->getCookie('ato_payeer_order')) 
{
    require_once dirname(__FILE__) . '/_local/eshop/AtoPaymentSystem.php';

    $secretKey = AtoPaymentSystem::getDriverParameter('ato_payeer', 'payeer_secret_key');
	$log = AtoPaymentSystem::getDriverParameter('ato_payeer', 'payeer_log');
	$ipfilter = AtoPaymentSystem::getDriverParameter('ato_payeer', 'payeer_ip_filter');
	$emailerr = AtoPaymentSystem::getDriverParameter('ato_payeer', 'payeer_email_error');
	
    list ($data, $sign) = explode('|', $oRequest->getCookie('ato_payeer_order'), 2);
	
	// проверка принадлежности ip списку доверенных ip
	$list_ip_str = str_replace(' ', '', $ipfilter);
	
	if (!empty($list_ip_str)) 
	{
		$list_ip = explode(',', $list_ip_str);
		$this_ip = $_SERVER['REMOTE_ADDR'];
		$this_ip_field = explode('.', $this_ip);
		$list_ip_field = array();
		$i = 0;
		$valid_ip = FALSE;
		foreach ($list_ip as $ip)
		{
			$ip_field[$i] = explode('.', $ip);
			if ((($this_ip_field[0] ==  $ip_field[$i][0]) or ($ip_field[$i][0] == '*')) and
				(($this_ip_field[1] ==  $ip_field[$i][1]) or ($ip_field[$i][1] == '*')) and
				(($this_ip_field[2] ==  $ip_field[$i][2]) or ($ip_field[$i][2] == '*')) and
				(($this_ip_field[3] ==  $ip_field[$i][3]) or ($ip_field[$i][3] == '*')))
				{
					$valid_ip = TRUE;
					break;
				}
			$i++;
		}
	}
	else
	{
		$valid_ip = TRUE;
	}
	
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
			"description		".base64_decode($_GET["m_desc"])."\n".
			"status				".$_GET["m_status"]."\n".
			"sign				".$_GET["m_sign"]."\n\n";
			
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/payeer.log', $log_text, FILE_APPEND);
	}
					
    if (md5($data . 'ato_payeer' . $secretKey) === $sign && $valid_ip)
	{
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
	else
	{		
		$arHash = array(
			$_GET['m_operation_id'],
			$_GET['m_operation_ps'],
			$_GET['m_operation_date'],
			$_GET['m_operation_pay_date'],
			$_GET['m_shop'],
			$_GET['m_orderid'],
			$_GET['m_amount'],
			$_GET['m_curr'],
			$_GET['m_desc'],
			$_GET['m_status'],
			$secretKey
		);
		
		$sign_hash = strtoupper(hash('sha256', implode(":", $arHash)));
			
		$to = $emailerr;
		$subject = "Payment error";
		$message = "Failed to make the payment through the system Payeer for the following reasons:\n\n";
		if ($_GET["m_sign"] != $sign_hash)
		{
			$message.=" - Do not match the digital signature\n";
		}
		if ($_GET['m_status'] != "success")
		{
			$message.=" - The payment status is not success\n";
		}
		if (!$valid_ip)
		{
			$message.=" - the ip address of the server is not trusted\n";
			$message.="   trusted ip: ".$ipfilter."\n";
			$message.="   ip of the current server: ".$_SERVER['REMOTE_ADDR']."\n";
		}
		$message.="\n".$log_text;
		$headers = "From: no-reply@".$_SERVER['HTTP_SERVER']."\r\nContent-type: text/plain; charset=utf-8 \r\n";
		mail($to, $subject, $message, $headers);
		
		$oResponse->HTTP->setCookie('ato_payeer_order', NULL);
        $order = @unserialize(gzuncompress(base64_decode($data)));
        if (is_array($order)) 
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
