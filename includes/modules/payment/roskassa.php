<?php

class roskassa {

    var $code, $title, $description, $enabled;

    function roskassa() {

      global $order;



      $this->signature = false;

      $this->api_version = '1.0';



      $this->code = 'roskassa';

      $this->title = 'RosKassa';

      $this->public_title = 'RosKassa';

      $this->description = '<img src="images/icon_popup.gif" border="0">&nbsp;<a href="https://roskassa.net" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit RosKassa Website</a>';

      $this->sort_order = MODULE_PAYMENT_ROSKASSA_SORT_ORDER;

      $this->enabled = ((MODULE_PAYMENT_ROSKASSA_STATUS == 'True') ? true : false);



      if ((int)MODULE_PAYMENT_ROSKASSA_ORDER_PREPARE_STATUS_ID > 0) {

          $this->order_status = MODULE_PAYMENT_ROSKASSA_ORDER_PREPARE_STATUS_ID;

      }



      if(is_object($order)) $this->update_status();



      $this->form_action_url = 'https://pay.roskassa.net/';

  }



  function update_status() {	

      global $order;

      if ($this->enabled == true)

         if(!in_array(strtoupper($order->info['currency']),array('RUB','USD','EUR')))

            $this->enabled = false;				

    }



    function javascript_validation() {

      return false;

  }



  function selection() {	

    global $cart_roskassa_id;



    if (tep_session_is_registered('cart_roskassa_id'))

    {

        $order_id = substr($cart_roskassa_id, strpos($cart_roskassa_id, '-')+1);



        $check_query = tep_db_query('select orders_id from '.TABLE_ORDERS_STATUS_HISTORY.' where orders_id = "'.(int)$order_id.'" limit 1');



        if (tep_db_num_rows($check_query) < 1)

        {

            tep_db_query('delete from '.TABLE_ORDERS.' where orders_id = "'.(int)$order_id.'"');

            tep_db_query('delete from '.TABLE_ORDERS_TOTAL.' where orders_id = "'.(int)$order_id.'"');

            tep_db_query('delete from '.TABLE_ORDERS_STATUS_HISTORY.' where orders_id = "'.(int)$order_id.'"');

            tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS.' where orders_id = "'.(int)$order_id.'"');

            tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS_ATTRIBUTES.' where orders_id = "'.(int)$order_id.'"');

            tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS_DOWNLOAD.' where orders_id = "'.(int)$order_id.'"');



            tep_session_unregister('cart_roskassa_id');

        }

    }	

    return array('id' => $this->code,'module' => $this->public_title, 'fields' => array(array('title' => '', 'field' => '<b>Online Payment GateWay</b>')));	

}



function pre_confirmation_check() {

    global $cartID, $cart;



    if ( empty($cart->cartID))

    {

        $cartID = $cart->cartID = $cart->generate_cart_id();

    }



    if (!tep_session_is_registered('cartID'))

    {

        tep_session_register('cartID');

    }

}



function confirmation() {

    global $cartID, $cart_roskassa_id, $customer_id, $languages_id, $order, $order_total_modules;



    if (tep_session_is_registered('cartID'))

    {

        $insert_order = false;



        if (tep_session_is_registered('cart_roskassa_id'))

        {

            $order_id = substr($cart_roskassa_id, strpos($cart_roskassa_id, '-')+1);



            $curr_check = tep_db_query("select currency from ".TABLE_ORDERS." where orders_id = '".(int)$order_id."'");

            $curr = tep_db_fetch_array($curr_check);



            if (($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_roskassa_id, 0, strlen($cartID))))

            {

                $check_query = tep_db_query('select orders_id from '.TABLE_ORDERS_STATUS_HISTORY.' where orders_id = "'.(int)$order_id.'" limit 1');



                if (tep_db_num_rows($check_query) < 1)

                {

                    tep_db_query('delete from '.TABLE_ORDERS.' where orders_id = "'.(int)$order_id.'"');

                    tep_db_query('delete from '.TABLE_ORDERS_TOTAL.' where orders_id = "'.(int)$order_id.'"');

                    tep_db_query('delete from '.TABLE_ORDERS_STATUS_HISTORY.' where orders_id = "'.(int)$order_id.'"');

                    tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS.' where orders_id = "'.(int)$order_id.'"');

                    tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS_ATTRIBUTES.' where orders_id = "'.(int)$order_id.'"');

                    tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS_DOWNLOAD.' where orders_id = "'.(int)$order_id.'"');

                }



                $insert_order = true;

            }

        } else

        {

            $insert_order = true;

        }



        if ($insert_order == true)

        {

            $order_totals = array ();

            if (is_array($order_total_modules->modules))

            {

                reset($order_total_modules->modules);

                while ( list (, $value) = each($order_total_modules->modules))

                {

                    $class = substr($value, 0, strrpos($value, '.'));

                    if ($GLOBALS[$class]->enabled)

                    {

                        for ($i = 0, $n = sizeof($GLOBALS[$class]->output); $i < $n; $i++)

                        {

                            if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text']))

                            {

                                $order_totals[] = array ('code'=>$GLOBALS[$class]->code,

                                    'title'=>$GLOBALS[$class]->output[$i]['title'],

                                    'text'=>$GLOBALS[$class]->output[$i]['text'],

                                    'value'=>$GLOBALS[$class]->output[$i]['value'],

                                    'sort_order'=>$GLOBALS[$class]->sort_order);

                            }

                        }

                    }

                }

            }



            $sql_data_array = array ('customers_id'=>$customer_id,

                'customers_name'=>$order->customer['firstname'].' '.$order->customer['lastname'],

                'customers_company'=>$order->customer['company'],

                'customers_street_address'=>$order->customer['street_address'],

                'customers_suburb'=>$order->customer['suburb'],

                'customers_city'=>$order->customer['city'],

                'customers_postcode'=>$order->customer['postcode'],

                'customers_state'=>$order->customer['state'],

                'customers_country'=>$order->customer['country']['title'],

                'customers_telephone'=>$order->customer['telephone'],

                'customers_email_address'=>$order->customer['email_address'],

                'customers_address_format_id'=>$order->customer['format_id'],

                'delivery_name'=>$order->delivery['firstname'].' '.$order->delivery['lastname'],

                'delivery_company'=>$order->delivery['company'],

                'delivery_street_address'=>$order->delivery['street_address'],

                'delivery_suburb'=>$order->delivery['suburb'],

                'delivery_city'=>$order->delivery['city'],

                'delivery_postcode'=>$order->delivery['postcode'],

                'delivery_state'=>$order->delivery['state'],

                'delivery_country'=>$order->delivery['country']['title'],

                'delivery_address_format_id'=>$order->delivery['format_id'],

                'billing_name'=>$order->billing['firstname'].' '.$order->billing['lastname'],

                'billing_company'=>$order->billing['company'],

                'billing_street_address'=>$order->billing['street_address'],

                'billing_suburb'=>$order->billing['suburb'],

                'billing_city'=>$order->billing['city'],

                'billing_postcode'=>$order->billing['postcode'],

                'billing_state'=>$order->billing['state'],

                'billing_country'=>$order->billing['country']['title'],

                'billing_address_format_id'=>$order->billing['format_id'],

                'payment_method'=>$order->info['payment_method'],

                'cc_type'=>$order->info['cc_type'],

                'cc_owner'=>$order->info['cc_owner'],

                'cc_number'=>$order->info['cc_number'],

                'cc_expires'=>$order->info['cc_expires'],

                'date_purchased'=>'now()',

                'orders_status'=>$order->info['order_status'],

                'currency'=>$order->info['currency'],

                'currency_value'=>$order->info['currency_value']);



            tep_db_perform(TABLE_ORDERS, $sql_data_array);



            $insert_id = tep_db_insert_id();



            for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++)

            {

                $sql_data_array = array ('orders_id'=>$insert_id,

                    'title'=>$order_totals[$i]['title'],

                    'text'=>$order_totals[$i]['text'],

                    'value'=>$order_totals[$i]['value'],

                    'class'=>$order_totals[$i]['code'],

                    'sort_order'=>$order_totals[$i]['sort_order']);



                tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);

            }



            for ($i = 0, $n = sizeof($order->products); $i < $n; $i++)

            {

                $sql_data_array = array ('orders_id'=>$insert_id,

                    'products_id'=>tep_get_prid($order->products[$i]['id']),

                    'products_model'=>$order->products[$i]['model'],

                    'products_name'=>$order->products[$i]['name'],

                    'products_price'=>$order->products[$i]['price'],

                    'final_price'=>$order->products[$i]['final_price'],

                    'products_tax'=>$order->products[$i]['tax'],

                    'products_quantity'=>$order->products[$i]['qty']);



                tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);



                $order_products_id = tep_db_insert_id();



                $attributes_exist = '0';

                if ( isset ($order->products[$i]['attributes']))

                {

                    $attributes_exist = '1';

                    for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++)

                    {

                        $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from ".TABLE_PRODUCTS_OPTIONS." popt, ".TABLE_PRODUCTS_OPTIONS_VALUES." poval, ".TABLE_PRODUCTS_ATTRIBUTES." pa where pa.products_id = '".$order->products[$i]['id']."' and pa.options_id = '".$order->products[$i]['attributes'][$j]['option_id']."' and pa.options_id = popt.products_options_id and pa.options_values_id = '".$order->products[$i]['attributes'][$j]['value_id']."' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '".$languages_id."' and poval.language_id = '".$languages_id."'");

                        $attributes_values = tep_db_fetch_array($attributes);



                        $sql_data_array = array ('orders_id'=>$insert_id,

                            'orders_products_id'=>$order_products_id,

                            'products_options'=>$attributes_values['products_options_name'],

                            'products_options_values'=>$attributes_values['products_options_values_name'],

                            'options_values_price'=>$attributes_values['options_values_price'],

                            'price_prefix'=>$attributes_values['price_prefix']);



                        tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

                    }

                }

            }



            $cart_roskassa_id = $cartID.'-'.$insert_id;

            tep_session_register('cart_roskassa_id');

        }

    }



    return false;

}



function process_button() {

  global $customer_id, $order, $sendto, $currency, $cart_roskassa_id, $shipping;

  $order_id = substr($cart_roskassa_id, strpos($cart_roskassa_id, '-')+1);

  $total = $this->format_raw($order->info['total']);

  $process = false;

  $i = 0;

  foreach ($order->products as $value) { 

    $process['receipt[items]['.$i.'][name]'] = $value['name'];

    $process['receipt[items]['.$i.'][count]'] = $value['qty'];

    $process['receipt[items]['.$i.'][price]'] = $this->format_raw($value['final_price']);

    $i++;
  }

  if (isset($shipping['cost']) && $shipping['cost'] > 0) {

    $process['receipt[items]['.$i.'][name]'] = $shipping['title'];

    $process['receipt[items]['.$i.'][count]'] = 1;

    $process['receipt[items]['.$i.'][price]'] = $this->format_raw($shipping['cost']);

  }

 
  $arr = array(

    "shop_id" => MODULE_PAYMENT_ROSKASSA_ESHOPID,

    "order_id" => $order_id,

    "amount" => $total,

    "currency" => strtoupper($currency),

    "test" => MODULE_PAYMENT_ROSKASSA_TEST_MODE,

  );

  ksort($arr);

  $sign_hash_str = http_build_query($arr);

  $sign_hash = md5($sign_hash_str . MODULE_PAYMENT_ROSKASSA_SECRETKEY);

  $process_button_string =	tep_draw_hidden_field('shop_id', MODULE_PAYMENT_ROSKASSA_ESHOPID);

  foreach ($process as $key => $value) {

    $process_button_string .= tep_draw_hidden_field($key, $value);

  }

  $process_button_string .=  tep_draw_hidden_field('order_id', $order_id) .

                            tep_draw_hidden_field('amount', $total) .

                            tep_draw_hidden_field('currency', strtoupper($currency)) .

                            tep_draw_hidden_field('test', MODULE_PAYMENT_ROSKASSA_TEST_MODE) .

                            tep_draw_hidden_field('sign', $sign_hash) .

                            tep_draw_hidden_field('success_url', tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL')) .

                            tep_draw_hidden_field('fail_url', tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));

  return $process_button_string;

}



function before_process() {

  global $cart;

  $this->after_process();

  $cart->reset(true);

  tep_session_unregister('sendto');

  tep_session_unregister('billto');

  tep_session_unregister('shipping');

  tep_session_unregister('payment');

  tep_session_unregister('comments');

  tep_session_unregister('cart_roskassa_id');

  tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));

}



function after_process() {

  return false;

}



function get_error() {

  return false;

}



function check() {

  if (!isset($this->_check)) {

    $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_ROSKASSA_STATUS'");

    $this->_check = tep_db_num_rows($check_query);

}

return $this->_check;	

}



function install() {



   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable RosKassa payment', 'MODULE_PAYMENT_ROSKASSA_STATUS', 'False', 'Do you want to accept RosKassa payments?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");



   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Eshop ID', 'MODULE_PAYMENT_ROSKASSA_ESHOPID', '6C8699E19349382C416CD2F5E7AAD21D', 'You id from RosKassa system.', '6', '0', now())");	



   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Secret Key', 'MODULE_PAYMENT_ROSKASSA_SECRETKEY', '0vfd9xhQ', 'You secret key from RosKassa system.', '6', '0', now())");		



   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Test Mode', 'MODULE_PAYMENT_ROSKASSA_TEST_MODE', '1', 'The Test Mode', '6', '0', now())");     	



   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Prepare Order Status', 'MODULE_PAYMENT_ROSKASSA_ORDER_PREPARE_STATUS_ID', '0', 'Set the status of prepare orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");



   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Paid Order Status', 'MODULE_PAYMENT_ROSKASSA_ORDER_PAID_STATUS_ID', '0', 'Set the status of paid orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");		



   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_ROSKASSA_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");		

}



function remove() {

  tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");

}



function keys() {

  return array(

      'MODULE_PAYMENT_ROSKASSA_STATUS',

      'MODULE_PAYMENT_ROSKASSA_ESHOPID',

      'MODULE_PAYMENT_ROSKASSA_SECRETKEY',

      'MODULE_PAYMENT_ROSKASSA_TEST_MODE',

      'MODULE_PAYMENT_ROSKASSA_ORDER_PREPARE_STATUS_ID',

      'MODULE_PAYMENT_ROSKASSA_ORDER_PAID_STATUS_ID',

      'MODULE_PAYMENT_ROSKASSA_SORT_ORDER',

  );	

}

function format_raw($number, $currency_code = '', $currency_value = '') {

  global $currencies, $currency;



  if (empty($currency_code) || !$this->is_set($currency_code)) {

     $currency_code = $currency;

 }



 if (empty($currency_value) || !is_numeric($currency_value)) {

     $currency_value = $currencies->currencies[$currency_code]['value'];

 }



 return number_format(tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');

}	

}

?>