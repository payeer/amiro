<?php

class AtoPayeer_PaymentSystemDriver extends AMI_PaymentSystemDriver{

    const ERROR_MISSING_OBLIGATORY_PARAMETER = 1;
    const ERROR_RUR_ONLY = 2;
    const ERROR_UNSUPPORTED_CURRENCY = 3;


    protected $driverName = 'ato_payeer';


    private $_obligatoryParams =
        array(
            'payeer_shop',
            'payeer_secret_key'
        );

    public function getPayButton(&$aRes, $aData, $bAutoRedirect = false)
    {

        foreach (array('return', 'description') as $k){
            $aData[$fldName] = htmlspecialchars($aData[$k]);
        }

        foreach (array_keys($aData) as $key) {
            if (mb_strpos($key, 'payeer_') === 0) {
                unset($aData[$key]);
            }
        }

        $aEclusion =
            array(
                'return',
                'cancel',
                'callback',
                'pay_to_email',
                'amount',
                'currency',
                'description_title',
                'description',
                'order',
                'button_name',
                'button'
            );

        $hiddens = '';
        foreach ($aData as $key => $value) {
            if (!in_array($key, $aEclusion)) {
                $hiddens .=
                    '<input type="hidden" name="' . $key .
                    '" value="' . (is_null($value) ? $aData[$key] : $value) .
                    '" />' . "\n";
            }
        }
        $aData['hiddens'] = $hiddens;

        return parent::getPayButton($aRes, $aData, $bAutoRedirect);
    }


    public function getPayButtonParams($aData, &$aRes)
    {
        $oResponse = AMI::getSingleton('response');
        $oResponse->HTTP->setCookie('ato_payeer_order', NULL);

        foreach ($this->_obligatoryParams as $key) {
            if(empty($aData[$key])){
                $aRes['errno'] = self::ERROR_MISSING_OBLIGATORY_PARAMETER;
                $aRes['error'] = 'Obligatory parameter "' . $key . ' is missed';
                return false;
            }
        }

        $aData += array(
            'payeer_payment'     => 0
        );

        $aRes['errno'] = 0;
        $aRes['error'] = 'Success';

		$aData['description'] = base64_encode('Оплата заказа № ' . $aData['order']);
		$aData['currency'] = 'RUB';
		
		$m_shop 	= $aData['payeer_shop'];
		$m_orderid 	= $aData['order'];
		$m_amount 	= number_format($aData['amount'], 2, '.', '');
		$m_curr 	= $aData['currency'];
		$m_desc 	= $aData['description'];
		$m_key 		= $aData['payeer_secret_key'];

		$arHash = array(
			$m_shop,
			$m_orderid,
			$m_amount,
			$m_curr,
			$m_desc,
			$m_key
		);
		$aData['sign'] = strtoupper(hash('sha256', implode(":", $arHash)));

        $data =
            base64_encode(
                gzcompress(
                    serialize(
                        array(
                            'id'      => $aData['order'],
                            'success' => $aData['return'],
                            'fail'    => $aData['cancel']
                        )
                    ),
                    9
                )
            );
        $oResponse->HTTP->setCookie(
            'ato_payeer_order',
            $data . '|' .
            md5($data . 'ato_payeer' . $aData['payeer_secret_key'])
        );

        return parent::getPayButtonParams($aData, $aRes);
    }

    public function payProcess($aGet, $aPost, &$aRes, $aCheckData, $aOrderData)
    {
        $aRes['errno'] = 0;
        $aRes['error'] = 'Success';

        return parent::payProcess($aGet, $aPost, $aRes, $aCheckData, $aOrderData);
    }


    public function payCallback($aGet, $aPost, &$aRes, $aCheckData, $aOrderData)
    {

        $response = AMI::getSingleton('response');

        foreach (array('order_id', 'order_amount', 'sign') as $param) {
            if (empty($aGet[$param])) {
                $response->start()->write('ato_payeer[1]');
                return -1;
            }
        }
        $sign = md5(
            $aGet['order_id'] .
            'ato_payeer' .
            $aGet['order_amount'] .
            $aCheckData['payeer_secret_key']
        );
        if ($aGet['sign'] != $sign) {
            $response->start()->write('ato_payeer[2]');
            return -1;
        }
        $response->start()->write('ato_payeer[0]');
        return 1;
    }


    public function getProcessOrder($aGet, $aPost, &$aRes, $aAdditionalParams)
    {
        $aRes['error'] = 'Success';
        $aRes['errno'] = 0;

        return parent::getProcessOrder($aGet, $aPost, $aRes, $aAdditionalParams);
    }
}
