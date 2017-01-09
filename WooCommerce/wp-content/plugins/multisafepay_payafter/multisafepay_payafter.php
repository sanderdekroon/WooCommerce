<?php

/*
  Plugin Name: Multisafepay Betaal Na Ontvangst
  Plugin URI: http://www.multisafepay.com
  Description: Multisafepay Payment Plugin
  Author: Multisafepay
  Author URI:http://www.multisafepay.com
  Version: 3.0.0

  Copyright: � 2012 Multisafepay(email : techsupport@multisafepay.com)
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

load_plugin_textdomain('multisafepay', false, dirname(plugin_basename(__FILE__)) . '/');

if (!function_exists('is_plugin_active_for_network'))
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    add_action('plugins_loaded', 'WC_MULTISAFEPAY_PAYAFTER_Load', 0);

    function WC_MULTISAFEPAY_PAYAFTER_Load() {

        class WC_MULTISAFEPAY_PAYAFTER extends WC_MULTISAFEPAY {

            public function __construct() {
                global $woocommerce;

                $this->multisafepay_settings = (array) get_option('woocommerce_multisafepay_settings');
                $this->debug    = parent::getDebugMode ($this->multisafepay_settings['debug']);

                $this->id                   = "multisafepay_payafter";
                $this->paymentMethodCode    = "PAYAFTER";
                $this->has_fields           = false;
                $this->supports             = array(
                                                /* 'subscriptions',
                                                  'products',
                                                  'subscription_cancellation',
                                                  'subscription_reactivation',
                                                  'subscription_suspension',
                                                  'subscription_amount_changes',
                                                  'subscription_payment_method_change',
                                                  'subscription_date_changes',
                                                  'default_credit_card_form',
                                                  'pre-orders'
                                                */
                                                'refunds',
                                                );

                add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
                add_action("woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options'));
                add_filter('woocommerce_payment_gateways', array('WC_MULTISAFEPAY_PAYAFTER', 'MULTISAFEPAY_PAYAFTER_Add_Gateway'));
                add_filter('woocommerce_available_payment_gateways', array ($this , 'payafter_filter_gateways'), 1);




                if (file_exists(dirname(__FILE__) . '/images/' . $this->paymentMethodCode . '.png')) {
                    $this->icon = apply_filters('woocommerce_multisafepay_icon', plugins_url('images/' . $this->paymentMethodCode . '.png', __FILE__));
                } else {
                    $this->icon = '';
                }

                $this->settings = (array) get_option("woocommerce_{$this->id}_settings");

                $this->title        = !empty($this->settings['pmtitle']) ? $this->settings['pmtitle'] : $this->paymentMethodCode;
                $this->method_title = $this->title;



                $output = '';
                $output = '<p class="form-row form-row-wide  validate-required"><label for="birthday" class="">' . __('Geboortedatum', 'multisafepay') . '<abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="PAYAFTER_birthday" id="birthday" placeholder="dd-mm-yyyy"/>
				</p><div class="clear"></div>';

                $output .= '<p class="form-row form-row-wide  validate-required"><label for="account" class="">' . __('Rekeningnummer', 'multisafepay') . '<abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="PAYAFTER_account" id="account" placeholder="NLXX XXXX 0000 000 000"/>
				</p><div class="clear"></div>';

                $output .= '<p class="form-row form-row-wide">' . __('Met het uitvoeren van deze bestelling gaat u akkoord met de ', 'multisafepay') . '<a href="http://www.multifactor.nl/consument-betalingsvoorwaarden-2/" target="_blank">voorwaarden van MultiFactor.</a>';

                $this->PAYAFTER_Forms();


                $this->description = $this->settings['description'];
                $this->description .= $output;

                $this->enabled     = $this->settings['enabled'] == 'yes' ? 'yes': 'no';
            }

            public function PAYAFTER_Forms() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable Pay after Delivery', 'multisafepay'),
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
                    'description' => array(
                        'title' => __('Gateway Description', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('This will be shown when selecting the gateway', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),
                    'minamount' => array(
                        'title' => __('Minimal order amount', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('The minimal amount for an order to show Pay After Delivery', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),
                    'maxamount' => array(
                        'title' => __('Max order amount', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('The max order amount for an order to show Pay After Delivery', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),

                );
            }


            public function process_payment($order_id) {
                $this->type = 'direct';
                $this->gateway = $this->paymentMethodCode;
                $this->getGatewayInfo($order_id);
                $this->getCart($order_id);

                return parent::process_payment($order_id);
            }


            private function getGatewayInfo($order_id)
            {
                $order = new WC_Order($order_id);

                $this->gatewayInfo = array(
                    'referrer'    => $_SERVER['HTTP_REFERER'],
                    'user_agent'  => $_SERVER['HTTP_USER_AGENT'],
                    'birthday'    => $_POST['PAYAFTER_birthday'],
                    'bankaccount' => $_POST['PAYAFTER_account'],
                    'phone'       => $order->billing_phone,
                    'email'       => $order->billing_email,
                    'gender'      => ''
                );
            }



            private function getCart($order_id){

                $order = new WC_Order($order_id);

                $this->shopping_cart    = array();
                $this->checkout_options = array();
                $this->checkout_options['tax_tables']['default'] = array ( 'shipping_taxed'=> 'true', 'rate' => '0.21');

                //Add BTW 0%
                $this->checkout_options['tax_tables']['alternate'][] = array ('name' => 'BTW-0', 'rules' => array (array ('rate' => '0.00')));

                $tax_array = array('BTW-0');

/*
$tmp = $order->get_items('fee');
$string =  'fee: '. print_r ($tmp, true);
mail ('Testbestelling-Ronald@Multisafepay.com', 'debug - ' . $_SERVER['SCRIPT_FILENAME'], $string);


$tmp = $order->get_items('tax');
$string =  'tax: '. print_r ($tmp, true);
mail ('Testbestelling-Ronald@Multisafepay.com', 'debug - ' . $_SERVER['SCRIPT_FILENAME'], $string);
*/

/*                foreach (WC()->cart->get_fees() as $fee) {

                    $c_item = new MspItem($fee->name . " ", '', 1, number_format($fee->amount, 2, '.', ''), 'KG', 0);
                    $msp->cart->AddItem($c_item);
                    $json_array = array();
                    $json_array['fee'] = $fee->name;
                    $c_item->SetMerchantItemId(json_encode($json_array));
                    if ($fee->tax > 0) {
                        $fee_tax_percentage = round($fee->tax / $fee->amount, 2);
                        $c_item->SetTaxTableSelector($fee_tax_percentage);
                    } else {
                        $c_item->SetTaxTableSelector('BTW-0');
                    }
                }
*/


                // Shipping
                foreach ($order->get_items('shipping') as $shipping) {

                    $taxes = unserialize($shipping['taxes']);
                    $taxes = array_shift ($taxes);

                    $tax_table_selector = 'shipping';
                    $tax_percentage = round($taxes /$shipping['cost'], 2);

                    $method_id = explode (':', $shipping['method_id']);

                    $this->shopping_cart['items'][] = array (
                        'name'  		     => $shipping['type'],
                        'description' 		 => $shipping['name'],
                        'unit_price'  		 => $shipping['cost'],
                        'quantity'    		 => 1,
                        'merchant_item_id' 	 => $method_id[0],
                        'tax_table_selector' => $tax_table_selector,
                        'weight' 		     => array ('unit'=> 0,  'value'=> 'KG')
                    );

                    if (!in_array($tax_table_selector, $tax_array)) {
                        array_push($this->checkout_options['tax_tables']['alternate'], array ('name' => $tax_table_selector, 'rules' => array (array ('rate' => $tax_percentage))));
                        array_push($tax_array, $tax_table_selector);
                    }
                }


                //add coupon discount
                foreach ($order->get_items('coupon') as $coupon) {

                    $tax_table_selector = $coupon['type'];
                    $tax_percentage = round($coupon['discount_amount_tax'] /$coupon['discount_amount'], 2);

                    $this->shopping_cart['items'][] = array (
                        'name'  		     => $coupon['type'],
                        'description' 		 => $coupon['name'],
                        'unit_price'  		 => -$coupon['discount_amount'],
                        'quantity'    		 => 1,
                        'merchant_item_id' 	 => $coupon['type'],
                        'tax_table_selector' => $tax_table_selector,
                        'weight' 		     => array ('unit'=> 0,  'value'=> 'KG')
                    );

                    if (!in_array($tax_table_selector, $tax_array)) {
                        array_push($this->checkout_options['tax_tables']['alternate'], array ('name' => $tax_table_selector, 'rules' => array (array ('rate' => $tax_percentage))));
                        array_push($tax_array, $tax_table_selector);
                    }
                }

                //add item data
                $items = "<ul>\n";
                foreach ($order->get_items() as $item) {


                    $items .= "<li>" . $item['qty'].' x : '. $item['name'] . "</li>\n";

                    $tax_percentage = round($item['line_subtotal_tax']   / $item['line_subtotal'], 2);
                    $product_price          = round($item['line_subtotal'] / $item['qty'], 5);

                    if ($item['line_subtotal_tax'] > 0) {
                        $tax_table_selector =  'BTW-'. $tax_percentage*100;
                    } else {
                        $tax_table_selector = 'BTW-0';
                    }

                    $this->shopping_cart['items'][] = array (
                        'name'  		     => $item['name'],
                        'description' 		 => '',
                        'unit_price'  		 => $product_price,
                        'quantity'    		 => $item['qty'],
                        'merchant_item_id' 	 => $item['product_id'],
                        'tax_table_selector' => $tax_table_selector,
                        'weight' 		     => array ('unit'=> 0,  'value'=> 'KG')
                    );



                    if (!in_array($tax_table_selector, $tax_array)) {
                        array_push($this->checkout_options['tax_tables']['alternate'], array ('name' => $tax_table_selector, 'rules' => array (array ('rate' => $tax_percentage))));
                        array_push($tax_array, $tax_table_selector);
                    }
                }

                $items .= "</ul>\n";
            }


            public function payafter_filter_gateways($gateways) {
                global $woocommerce;

                $this->settings = (array) get_option("woocommerce_{$this->id}_settings");

                if(!empty($this->settings['minamount'])){
                    if ($woocommerce->cart->total > $this->settings['maxamount'] || $woocommerce->cart->total < $this->settings['minamount']) {
                        unset($gateways['multisafepay_payafter']);
                    }
                }

                if ($woocommerce->customer->get_country() != 'NL') {
                    unset($gateways['multisafepay_payafter']);
                }

                return $gateways;
            }

            public static function MULTISAFEPAY_PAYAFTER_Add_Gateway($methods) {
                global $woocommerce;
                $methods[] = 'WC_MULTISAFEPAY_PAYAFTER';

                return $methods;
            }

        }

        // Start
        new WC_MULTISAFEPAY_PAYAFTER();
    }

}
