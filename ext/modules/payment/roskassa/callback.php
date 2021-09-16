<?chdir('../../../../');require ('includes/application_top.php');reset($HTTP_POST_VARS);function ob_exit($status = null){	if($status) {		ob_end_flush();		isset($_REQUEST['debug']) ? exit($status) : exit();	}	else {		ob_end_clean();		header("HTTP/1.0 200 OK");		echo "OK";		exit();	}}function debug_file(){	header('Content-type: text/plain; charset=utf-8');	echo file_get_contents(__FILE__);}function from_request($name){	return isset($_REQUEST[$name]) ? trim(stripslashes($_REQUEST[$name])) : null;}function update_order($orderId=0,$order_status_id=1,$customer_notified=0,$comments=''){	$sql_data_array = array('orders_id'=>$orderId,		'orders_status_id'=>$order_status_id,		'date_added'=>'now()',		'customer_notified'=>$customer_notified,		'comments'=>$comments);	tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);	tep_db_query("update ".TABLE_ORDERS." set orders_status = '".$order_status_id."', last_modified = now() where orders_id = '".$orderId."'");}function update_buy(){	require (DIR_WS_CLASSES.'order.php');	$order = new order($orderId);	for ($i = 0, $n = sizeof($order->products); $i < $n; $i++)	{		if (STOCK_LIMITED == 'true')		{			$stock_query = tep_db_query("select products_quantity from ".TABLE_PRODUCTS." where products_id = '".tep_get_prid($order->products[$i]['id'])."'");			if (tep_db_num_rows($stock_query) > 0)			{				$stock_values = tep_db_fetch_array($stock_query);				$stock_left = $stock_values['products_quantity'];				tep_db_query("update ".TABLE_PRODUCTS." set products_quantity = '".$stock_left."' where products_id = '".tep_get_prid($order->products[$i]['id'])."'");				if (($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false'))				{					tep_db_query("update ".TABLE_PRODUCTS." set products_status = '0' where products_id = '".tep_get_prid($order->products[$i]['id'])."'");				}			}		}		tep_db_query("update ".TABLE_PRODUCTS." set products_ordered = products_ordered + ".sprintf('%d', $order->products[$i]['qty'])." where products_id = '".tep_get_prid($order->products[$i]['id'])."'");	}}ob_start();list($shop_id, $order_id, $amount, $currency, $status, $sign) = array($_POST['shop_id'], $_POST['order_id'], $_POST['amount'], $_POST['currency'], $_POST['status'], $_POST['sign']);$order_query = tep_db_query("select orders_status, currency, currency_value from ".TABLE_ORDERS." where orders_id = '".$order_id."'");if (tep_db_num_rows($order_query) <= 0) {	$err = "ERROR: ORDER NOT EXISTS!\n";	ob_exit($err);}$my_order = tep_db_fetch_array($order_query);$order = $my_order;$total_query = tep_db_query("select value from ".TABLE_ORDERS_TOTAL." where orders_id = '".$order_id."' and class = 'ot_total' limit 1");$total = tep_db_fetch_array($total_query);$order_amount = $total['value']*$order['currency_value'];$order_currency = (strtoupper($order['currency']) == 'RUR') ? 'RUB' : $order['currency'];$order_secretKey = MODULE_PAYMENT_ROSKASSA_SECRETKEY;$order_eshopId = MODULE_PAYMENT_ROSKASSA_ESHOPID;if( strtoupper($order_currency) != strtoupper($currency) || number_format($amount, $currencies->get_decimal_places($order['currency'])) != number_format($total['value']*$order['currency_value'], $currencies->get_decimal_places($order['currency']))){			$err = "ERROR: AMOUNT/CURRENCY MISMATCH!\n";	$err .= "amount: $amount; order_amount: $order_amount;\ncurrency: $currency; order_currency: $order_currency;\n\n";	ob_exit($err);}	if($shop_id != $order_eshopId){				$err = "ERROR: INCORRECT ESHOP_ID!\n";	$err .= "shop_id: $shop_id; order_eshopId: ".$order_eshopId.";\n\n";	ob_exit($err);}else{	$post = $_POST;	unset($post['sign']);	ksort($post);	$str = http_build_query($post);	$sign_hash = md5($str . $order_secretKey);	if ($sign != $sign_hash || !$sign) {			$err = "ERROR: HASH MISMATCH\n";		$err .= "Control hash string: $sign_hash;\n";		update_order($order_id,MODULE_PAYMENT_ROSKASSA_ORDER_PREPARE_STATUS_ID,((SEND_EMAILS == 'true')?'1':'0'),"RosKassa::CREATED");		ob_exit($err);	} else {		update_order($order_id,MODULE_PAYMENT_ROSKASSA_ORDER_PAID_STATUS_ID,((SEND_EMAILS == 'true')?'1':'0'),"RosKassa::PAID");		update_buy();		ob_exit();	}}require ('includes/application_bottom.php');?>