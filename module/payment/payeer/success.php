<?php

if (isset($_GET['m_orderid'])) 
{
	ob_end_clean();
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');
	header('Location: ' . $_SERVER['HOST'] . '/members/order?action=process&status=ok&item_number=' . $_GET['m_orderid'], true, 301);
	die;
}

?>