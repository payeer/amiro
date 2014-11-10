<?php

require_once 'ami_env.php';

require_once dirname(__FILE__) . '/_local/eshop/AtoPaymentSystem.php';

class ATO_Payeer_Callback
{
    private $_response;

    private $_secretKey;
	
	private $_ipfilter;
	
	private $_log;
	
	private $_emailerr;


    public function __construct(array $request, $secretKey, $ipfilter, $log, $emailerr)
    {
        $this->_response = AMI::getSingleton('response');
		
        $this->_response->start();

        $this->_secretKey = (string)$secretKey;
		
		$this->_ipfilter = (string)$ipfilter;
		
		$this->_log = (string)$log;
		
		$this->_emailerr = (string)$emailerr;
		
        $this->validateRequestParams();
    }

    private function validateRequestParams()
    {	
		if (isset($_POST['m_operation_id']) && isset($_POST['m_sign']))
		{
			// проверка принадлежности ip списку доверенных ip
			$list_ip_str = str_replace(' ', '', $this->_ipfilter);
			
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
		
			$log_text = 
				"--------------------------------------------------------\n".
				"operation id		".$_POST["m_operation_id"]."\n".
				"operation ps		".$_POST["m_operation_ps"]."\n".
				"operation date		".$_POST["m_operation_date"]."\n".
				"operation pay date	".$_POST["m_operation_pay_date"]."\n".
				"shop				".$_POST["m_shop"]."\n".
				"order id			".$_POST["m_orderid"]."\n".
				"amount				".$_POST["m_amount"]."\n".
				"currency			".$_POST["m_curr"]."\n".
				"description		".base64_decode($_POST["m_desc"])."\n".
				"status				".$_POST["m_status"]."\n".
				"sign				".$_POST["m_sign"]."\n\n";
					
			if ($this->_log == 1)
			{
				file_put_contents($_SERVER['DOCUMENT_ROOT'].'/payeer.log', $log_text, FILE_APPEND);
			}
	
			$m_key = $this->_secretKey;
			
			$arHash = array(
				$_POST['m_operation_id'],
				$_POST['m_operation_ps'],
				$_POST['m_operation_date'],
				$_POST['m_operation_pay_date'],
				$_POST['m_shop'],
				$_POST['m_orderid'],
				$_POST['m_amount'],
				$_POST['m_curr'],
				$_POST['m_desc'],
				$_POST['m_status'],
				$m_key
			);
			
			$sign_hash = strtoupper(hash('sha256', implode(":", $arHash)));

			if ($_POST['m_sign'] == $sign_hash && $_POST['m_status'] == 'success' && $valid_ip)
			{
				$this->sendResponse($_POST['m_orderid'] . '|success');
			}
			else
			{
				$to = $this->_emailerr;
				$subject = "Ошибка оплаты - Payment error";
				$message = "Не удалось провести платёж через систему Payeer по следующим причинам:\n\n";
				if ($_POST["m_sign"] != $sign_hash)
				{
					$message.=" - Не совпадают цифровые подписи\n";
				}
				if ($_POST['m_status'] != "success")
				{
					$message.=" - Cтатус платежа не является success\n";
				}
				if (!$valid_ip)
				{
					$message.=" - ip-адрес сервера не является доверенным\n";
					$message.="   доверенные ip: ".$this->_ipfilter."\n";
					$message.="   ip текущего сервера: ".$_SERVER['REMOTE_ADDR']."\n";
				}
				$message.="\n".$log_text;
				$headers = "From: no-reply@".$_SERVER['HTTP_SERVER']."\r\nContent-type: text/plain; charset=utf-8 \r\n";
				mail($to, $subject, $message, $headers);
		
				$this->sendResponse($_POST['m_orderid'] . '|error');
			}
		}
    }

    private function sendResponse($message)
    {
		$this->_response->write($message);
        $this->_response->send();
    }
}

$secretKey = AtoPaymentSystem::getDriverParameter('ato_payeer', 'payeer_secret_key');
$ipfilter = AtoPaymentSystem::getDriverParameter('ato_payeer', 'payeer_ip_filter');
$log = AtoPaymentSystem::getDriverParameter('ato_payeer', 'payeer_log');
$emailerr = AtoPaymentSystem::getDriverParameter('ato_payeer', 'payeer_email_error');

$atoPayeerCallback = new ATO_Payeer_Callback($_POST, $secretKey, $ipfilter, $log, $emailerr);
