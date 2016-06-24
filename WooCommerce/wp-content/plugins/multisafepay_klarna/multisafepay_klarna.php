<?php

/*
  Plugin Name: Multisafepay Klarna
  Plugin URI: http://www.multisafepay.com
  Description: Multisafepay Payment Plugin
  Author: Multisafepay
  Author URI:http://www.multisafepay.com
  Version: 2.2.5

  Copyright: � 2012 Multisafepay(email : techsupport@multisafepay.com)
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

load_plugin_textdomain('multisafepay', false, dirname(plugin_basename(__FILE__)) . '/');
if (!class_exists('MultiSafepay')) {
    require(realpath(dirname(__FILE__)) . '/../multisafepay/MultiSafepay.combined.php');
}
if (!function_exists('is_plugin_active_for_network'))
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    add_action('plugins_loaded', 'WC_MULTISAFEPAY_KLARNA_Load', 0);

    function WC_MULTISAFEPAY_KLARNA_Load() {

        class WC_MULTISAFEPAY_KLARNA extends WC_Payment_Gateway {

            public function __construct() {
                global $woocommerce;

                $this->init_settings();
                $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');

                $this->id = "multisafepay_klarna";

                $this->has_fields = false;
                $this->paymentMethodCode = "KLARNA";
                $this->supports = array(
                    /* 'subscriptions',
                      'products',
                      'subscription_cancellation',
                      'subscription_reactivation',
                      'subscription_suspension',
                      'subscription_amount_changes',
                      'subscription_payment_method_change',
                      'subscription_date_changes',
                      'default_credit_card_form', */
                    'refunds',
                        //'pre-orders'
                );
                add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
                add_action("woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options'));
                add_filter('woocommerce_payment_gateways', array('WC_MULTISAFEPAY_KLARNA', 'MULTISAFEPAY_KLARNA_Add_Gateway'));
                add_filter('woocommerce_available_payment_gateways', 'klarna_filter_gateways', 1);

                $output = '';
                $output = '<p class="form-row form-row-wide  validate-required"><label for="birthday" class="">' . __('Geboortedatum', 'multisafepay') . '<abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="KLARNA_birthday" id="birthday" placeholder="dd-mm-yyyy"/>
				</p><div class="clear"></div>';

                /* $output .= '<p class="form-row form-row-wide  validate-required"><label for="account" class="">' . __('Rekeningnummer', 'multisafepay') . '<abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="KLARNA_account" id="account" placeholder=""/>
                  </p><div class="clear"></div>'; */

                $output .= '<p class="form-row form-row-wide  validate-required"><label for="account" class="">' . __('Geslacht', 'multisafepay') . '<abbr class="required" title="required">*</abbr></label><input type="radio" name="KLARNA_gender" id="gender" value="male"/> Man <input type="radio" name="KLARNA_gender" id="gender" value="female"/> Vrouw
				</p><div class="clear"></div>';

                //$output .= '<p class="form-row form-row-wide">' . __('Met het uitvoeren van deze bestelling gaat u akkoord met de ', 'multisafepay') . '<a href="http://www.multifactor.nl/consument-betalingsvoorwaarden-2/" target="_blank">voorwaarden van MultiFactor.</a>';

                if (file_exists(dirname(__FILE__) . '/images/' . $this->paymentMethodCode . '.png')) {
                    $this->icon = apply_filters('woocommerce_multisafepay_icon', plugins_url('images/' . $this->paymentMethodCode . '.png', __FILE__));
                } else {
                    $this->icon = '';
                }

                $this->settings = (array) get_option("woocommerce_{$this->id}_settings");
                if (!empty($this->settings['pmtitle'])) {
                    $this->title = $this->settings['pmtitle'];
                    $this->method_title = $this->settings['pmtitle'];
                } else {
                    $this->title = "Klarna Factuur";
                    $this->method_title = "Klarna Factuur";
                }

                $this->KLARNA_Forms();


                if (isset($this->settings['description'])) {
                    if ($this->settings['description'] != '') {
                        $this->description = $this->settings['description'];
                    }
                }
                $this->description .= $output;


                if (isset($this->settings['enabled'])) {
                    if ($this->settings['enabled'] == 'yes') {
                        $this->enabled = 'yes';
                    } else {
                        $this->enabled = 'no';
                    }
                } else {
                    $this->enabled = 'no';
                }
            }

            public function KLARNA_Forms() {
                $this->form_fields = array(
                    'stepone' => array(
                        'title' => __('Set-up Klarna configuration', 'multisafepay'),
                        'type' => 'title'
                    ),
                    'enabled' => array(
                        'title' => __('Enable Klarna', 'multisafepay'),
                        'type' => 'checkbox',
                        'label' => __('Enable Multisafepay for processing transactions', 'multisafepay'),
                        'default' => 'yes',
                        'description' => __('Payments will be possible when active', 'multisafepay'),
                    ),
                    'pmtitle' => array(
                        'title' => __('Title', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('Optional:overwrites the title of the payment method during checkout', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),
                    'minamount' => array(
                        'title' => __('Minimal order amount', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('The minimal amount for an order to show Klarna', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),
                    'maxamount' => array(
                        'title' => __('Max order amount', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('The max order amount for an order to show Klarna', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),
                    'description' => array(
                        'title' => __('Gateway Description', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('This will be shown when selecting the gateway', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),
                );
            }

            public function process_refund($order_id, $amount = null, $reason = '') {

                $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');
                if ($this->settings2['testmode'] == 'yes'):
                    $mspurl = 'https://testapi.multisafepay.com/v1/json/';
                else :
                    $mspurl = 'https://api.multisafepay.com/v1/json/';
                endif;

                $order = new WC_Order($order_id);
                $ordernumber = ltrim($order->get_order_number(), __('#', '', 'multisafepay'));
                $ordernumber = ltrim($ordernumber, __('n°', '', 'multisafepay'));
                $currency = $order->get_order_currency();

                require_once dirname(__FILE__) . "/../multisafepay/API/Autoloader.php";
                $msp = new Client;
                $msp->setApiKey($this->settings2['apikey']);
                $msp->setApiUrl($mspurl);

                $transactionid = $ordernumber;

                //get the order status
                $msporder = $msp->orders->get($type = 'orders', $transactionid, $body = array(), $query_string = false);
                $originalCart = $msporder->shopping_cart;
                $products = $order->get_items();
                $refundData = array();
                $refundData['checkout_data']['items'];

                foreach ($originalCart->items as $key => $item) {
                    if ($item->unit_price > 0) {
                        $refundData['checkout_data']['items'][] = $item;
                    }
                    foreach ($products as $key => $product) {
                        $product_id = $product['product_id'];
                        $item_id = $product['item_id'];
                        if ($product_id == $item->merchant_item_id) {
                            $qty_refunded = $order->get_qty_refunded_for_item($key);
                            if ($qty_refunded > 0) {
                                if ($item->unit_price > 0) {
                                    $refundItem = (OBJECT) Array();
                                    $refundItem->name = $item->name;
                                    $refundItem->description = $item->description;
                                    $refundItem->unit_price = '-' . $item->unit_price;
                                    $refundItem->quantity = $qty_refunded;
                                    $refuntItem->merchant_item_id = $item->merchant_item_id;
                                    $refundItem->tax_table_selector = $item->tax_table_selector;
                                    $refundData['checkout_data']['items'][] = $refundItem;
                                }
                            }
                        }
                    }
                }

                $endpoint = 'orders/' . $transactionid . '/refunds';
                try {
                    $mspreturn = $msp->orders->post($refundData, $endpoint);

                    return true;
                } catch (Exception $e) {

                    return new WP_Error('multisafepay_ideal', 'Order can\'t be refunded:' . $e->getMessage());
                }

                return false;
            }

            public function write_log($log) {
                if (true === WP_DEBUG) {
                    if (is_array($log) || is_object($log)) {
                        error_log(print_r($log, true));
                    } else {
                        error_log($log);
                    }
                }
            }

            public function process_payment($order_id) {
                global $wpdb, $woocommerce;

                $settings = (array) get_option('woocommerce_multisafepay_settings');

                if ($settings['debug'] == 'yes') {
                    $debug = true;
                } else {
                    $debug = false;
                }

                if ($debug) {
                    $this->write_log('MSP->Process payment start');
                }

                if ($settings['send_confirmation'] == 'yes') {
                    $mailer = $woocommerce->mailer();
                    $email = $mailer->emails['WC_Email_New_Order'];
                    $email->trigger($order_id);

                    $mailer = $woocommerce->mailer();
                    $email = $mailer->emails['WC_Email_Customer_Processing_Order'];
                    $email->trigger($order_id);
                }

                $order = new WC_Order($order_id);
                $language_locale = get_bloginfo('language');
                $language_locale = str_replace('-', '_', $language_locale);

                $paymentMethod = explode('_', $order->payment_method);
                $gateway = strtoupper($paymentMethod[1]);


                $html = '<ul>';
                $item_loop = 0;

                if (sizeof($order->get_items()) > 0) : foreach ($order->get_items() as $item) :
                        if ($item['qty']) :
                            $item_loop++;
                            $html .= '<li>' . $item['name'] . ' x ' . $item['qty'] . '</li>';
                        endif;
                    endforeach;
                endif;

                $html .= '</ul>';
                if ($settings['testmode'] == 'yes'):
                    $mspurl = true;
                else :
                    $mspurl = false;
                endif;

                $ordernumber = ltrim($order->get_order_number(), __('#', '', 'multisafepay'));
                $ordernumber = ltrim($ordernumber, __('n°', '', 'multisafepay'));

                $msp = new MultiSafepay();
                $msp->test = $mspurl;
                $msp->merchant['account_id'] = $settings['accountid'];
                $msp->merchant['site_id'] = $settings['siteid'];
                $msp->merchant['site_code'] = $settings['securecode'];
                $msp->merchant['notification_url'] = $settings['notifyurl'] . '&type=initial';
                $msp->merchant['cancel_url'] = $order->get_cancel_order_url();
                $msp->merchant['cancel_url'] = htmlspecialchars_decode(add_query_arg('key', $order->id, $msp->merchant['cancel_url']));
                $msp->merchant['redirect_url'] = add_query_arg('utm_nooverride', '1', $this->get_return_url($order));
                $msp->merchant['close_window'] = true;
                $msp->customer['locale'] = $language_locale;
                $msp->customer['firstname'] = $order->billing_first_name;
                $msp->customer['lastname'] = $order->billing_last_name;
                $msp->customer['zipcode'] = $order->billing_postcode;
                $msp->customer['city'] = $order->billing_city;
                $msp->customer['email'] = $order->billing_email;
                $msp->customer['phone'] = $order->billing_phone;
                $msp->customer['country'] = $order->billing_country;
                $msp->customer['state'] = $order->billing_state;
                $msp->parseCustomerAddress($order->billing_address_1);

                if ($msp->customer['housenumber'] == '') {
                    $msp->customer['housenumber'] = $order->billing_address_2;
                }

                $msp->transaction['id'] = $ordernumber; //$order_id; 
                $msp->transaction['currency'] = get_woocommerce_currency();
                $msp->transaction['amount'] = $order->get_total() * 100;
                $msp->transaction['description'] = 'Order ' . __('#', '', 'multisafepay') . $ordernumber . ' : ' . get_bloginfo();
                $msp->transaction['gateway'] = $gateway;
                $msp->plugin_name = 'WooCommerce';
                $msp->plugin['shop'] = 'WooCommerce';
                $msp->plugin['shop_version'] = $woocommerce->version;
                $msp->plugin['plugin_version'] = '2.2.5';
                $msp->plugin['partner'] = '';
                $msp->version = '(2.2.5)';
                $msp->transaction['items'] = $html;
                $msp->transaction['var1'] = $order->order_key;
                $msp->transaction['var2'] = $order_id;
                //$issuerName = sprintf('%s_issuer', $paymentMethod[1]);


                if ($_POST['KLARNA_birthday'] != '' && $order->billing_phone != '' && $order->billing_email != '') {
                    $msp->transaction['special'] = false;
                    $msp->gatewayinfo['birthday'] = $_POST['KLARNA_birthday'];
                    $msp->customer['birthday'] = $_POST['KLARNA_birthday'];
                    //$msp->gatewayinfo['bankaccount'] = $_POST['KLARNA_account'];
                    //$msp->customer['bankaccount'] = $_POST['KLARNA_account'];
                    $msp->customer['gender'] = $_POST['KLARNA_gender'];
                    $msp->gatewayinfo['gender'] = $_POST['KLARNA_gender'];
                    $msp->gatewayinfo['email'] = $order->billing_email;
                    $msp->gatewayinfo['phone'] = $order->billing_phone;
                }




                /**
                 * Add custom Woo cart fees as line items
                 * 
                 * TODO check tax on fee if can be added
                 */
                foreach (WC()->cart->get_fees() as $fee) {

                    $c_item = new MspItem($fee->name . " ", '', 1, number_format($fee->amount, 2, '.', ''), 'KG', 0);
                    $msp->cart->AddItem($c_item);
                    $json_array = array();
                    $json_array['fee'] = $fee->name;
                    $c_item->SetMerchantItemId(json_encode($json_array));
                    if ($fee->tax > 0) {
                        $fee_tax_percentage = round($fee->tax / $fee->amount, 2);
                        $c_item->SetTaxTableSelector($fee_tax_percentage);
                    } else {
                        $c_item->SetTaxTableSelector('BTW0');
                    }
                }



                //ADD BTW0
                $table = new MspAlternateTaxTable();
                $table->name = 'BTW0';
                $rule = new MspAlternateTaxRule('0.00');
                $table->AddAlternateTaxRules($rule);
                $msp->cart->AddAlternateTaxTables($table);

                //add shipping
                if ($order->order_shipping > 0) {
                    $c_item = new MspItem('Shipping' . " " . get_woocommerce_currency(), 'Shipping', '1', $order->order_shipping, '0', '0');
                    $msp->cart->AddItem($c_item);
                    $c_item->SetMerchantItemId('msp-shipping');
                    if ($order->order_shipping_tax > 0) {
                        $c_item->SetTaxTableSelector('shipping_tax');
                    } else {
                        $c_item->SetTaxTableSelector('BTW0');
                    }


                    if ($order->order_shipping_tax > 0) {
                        $shipping_tax = $order->order_shipping_tax;
                        $shipping_tax_percentage = round($shipping_tax / $order->order_shipping, 2);
                        $table = new MspAlternateTaxTable();
                        $table->name = 'shipping_tax';
                        $rule = new MspAlternateTaxRule($shipping_tax_percentage);
                        $table->AddAlternateTaxRules($rule);
                        $msp->cart->AddAlternateTaxTables($table);
                    }
                }


                //add coupon discount
                if ($order->order_discount != 0) {
                    $c_item = new MspItem('Discount' . " " . get_woocommerce_currency(), 'Discount', '1', -$order->order_discount, '0', '0');
                    $msp->cart->AddItem($c_item);
                    $c_item->SetMerchantItemId('discount');
                    $c_item->SetTaxTableSelector('BTW0');
                }

                $tax_array = array();

                //add item data
                foreach ($order->get_items() as $item) {
                    $product_tax = $item['line_tax'];
                    $product_tax_percentage = round($product_tax / $item['line_total'], 2);
                    $product_price = $item['line_total'] / $item['qty'];

                    $c_item = new MspItem($item['name'] . " " . get_woocommerce_currency(), '', $item['qty'], $product_price, 'KG', 0);
                    $msp->cart->AddItem($c_item);
                    $c_item->SetMerchantItemId($item['product_id']);

                    if ($item['line_subtotal_tax'] > 0) {
                        $c_item->SetTaxTableSelector($product_tax_percentage);
                    } else {
                        $c_item->SetTaxTableSelector('BTW0');
                    }

                    if ($item['line_subtotal_tax'] > 0 && !in_array($product_tax_percentage, $tax_array)) {
                        $tax_array = $product_tax_percentage;
                        $table = new MspAlternateTaxTable();
                        $table->name = $product_tax_percentage;
                        $rule = new MspAlternateTaxRule($product_tax_percentage);
                        $table->AddAlternateTaxRules($rule);
                        $msp->cart->AddAlternateTaxTables($table);
                    }
                }


                $url = $msp->startCheckout();


                if (TRUE) {
                    $this->write_log('MSP->transactiondata');
                    $this->write_log($msp);
                    $this->write_log('MSP->transaction URL');
                    $this->write_log($url);
                    $this->write_log('MSP->End debug');
                    $this->write_log('--------------------------------------');
                }


                if (!$msp->error and $url == false) {
                    $url = $msp->merchant['redirect_url'] . '?transactionid=' . $order_id;
                }
                
   

                if (!isset($msp->error)) {

                    // Reduce stock levels
                    //$order->reduce_order_stock();

                    return array(
                        'result' => 'success',
                        'redirect' => $url
                    );
                } else {
                    //$woocommerce->add_error(__('Payment error:', 'multisafepay') . ' ' . $msp->error);
                    wc_add_notice(__('Payment error:', 'multisafepay') . ' ' . $msp->error, 'error');
                }
            }

            public static function MULTISAFEPAY_KLARNA_Add_Gateway($methods) {
                global $woocommerce;
                $methods[] = 'WC_MULTISAFEPAY_KLARNA';

                return $methods;
            }

        }

        // Start 
        new WC_MULTISAFEPAY_KLARNA();
    }

    function klarna_filter_gateways($gateways) {
        global $woocommerce;
        $settings = $gateways['MULTISAFEPAY_KLARNA']->settings;

		
		if(!empty($settings['minamount'])){
        	if ($woocommerce->cart->total > $settings['maxamount'] || $woocommerce->cart->total < $settings['minamount']) {
        	    unset($gateways['MULTISAFEPAY_KLARNA']);
        	}
        }

        return $gateways;
    }

}
