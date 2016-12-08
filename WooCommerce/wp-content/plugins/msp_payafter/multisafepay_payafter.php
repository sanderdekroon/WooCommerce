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
//                global $woocommerce;

                $this->multisafepay_settings = (array) get_option('woocommerce_multisafepay_settings');
                $this->debug    = parent::getDebugMode ($this->multisafepay_settings['debug']);

                $this->id                   = "multisafepay_payafter";
                $this->paymentMethodCode    = "Betaal Na Ontvangst";
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
                add_filter('woocommerce_available_payment_gateways', 'payafter_filter_gateways', 1);

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

                $output .= '<p class="form-row form-row-wide  validate-required"><label for="account" class="">' . __('Rekeningnummer', 'multisafepay') . '<abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="PAYAFTER_account" id="account" placeholder=""/>
				</p><div class="clear"></div>';

                $output .= '<p class="form-row form-row-wide">' . __('Met het uitvoeren van deze bestelling gaat u akkoord met de ', 'multisafepay') . '<a href="http://www.multifactor.nl/consument-betalingsvoorwaarden-2/" target="_blank">voorwaarden van MultiFactor.</a>';

                $this->PAYAFTER_Forms();

                $this->description .= $output;
//                $this->description = $this->settings['description'];
                $this->enabled     = $this->settings['enabled'] == 'yes' ? 'yes': 'no';
            }

            public function PAYAFTER_Forms() {
                $this->form_fields = array(
                    'stepone' => array(
                        'title' => __('Set-up Pay after Delivery configuration', 'multisafepay'),
                        'type' => 'title'
                    ),
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
                    'description' => array(
                        'title' => __('Gateway Description', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('This will be shown when selecting the gateway', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),
                );
            }


            public function process_payment($order_id) {
                $this->gateway = $this->paymentMethodCode;
                return parent::process_payment($order_id);
            }


            public function process_payment2($order_id) {
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
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $msp->customer['referrer'] = $_SERVER['HTTP_REFERER'];
                }
                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    $msp->customer['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                }
                $msp->transaction['id'] = $ordernumber; //$order_id;
                $msp->transaction['currency'] = get_woocommerce_currency();
                $msp->transaction['amount'] = $order->get_total() * 100;
                $msp->transaction['description'] = 'Order ' . __('#', '', 'multisafepay') . $ordernumber . ' : ' . get_bloginfo();
                $msp->transaction['gateway'] = $gateway;
                $msp->plugin_name = 'WooCommerce';
                $msp->plugin['shop'] = 'WooCommerce';
                $msp->plugin['shop_version'] = $woocommerce->version;
                $msp->plugin['plugin_version'] = '2.2.7';
                $msp->plugin['partner'] = '';
                $msp->version = '(2.2.7)';
                $msp->transaction['items'] = $html;
                $msp->transaction['var1'] = $order->order_key;
                $msp->transaction['var2'] = $order_id;
                $issuerName = sprintf('%s_issuer', $paymentMethod[1]);


                if ($_POST['PAYAFTER_birthday'] != '' && $_POST['PAYAFTER_account'] != '' && $order->billing_phone != '' && $order->billing_email != '') {
                    $msp->transaction['special'] = true;
                    $msp->gatewayinfo['birthday'] = $_POST['PAYAFTER_birthday'];
                    $msp->customer['birthday'] = $_POST['PAYAFTER_birthday'];
                    $msp->gatewayinfo['bankaccount'] = $_POST['PAYAFTER_account'];
                    $msp->customer['bankaccount'] = $_POST['PAYAFTER_account'];
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
                    $c_item->SetMerchantItemId('Shipping');
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
                if ($debug) {
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
                    if ($msp->error_code == '1024') {
					 	wc_add_notice(__('Payment error:', 'multisafepay') . ' ' . $msp->error_code.': '.__('We are sorry to inform you that your request for payment after delivery has been denied by Multifactor.<BR /> If you have questions about this rejection, you can checkout the FAQ on the website of Multifactor ', 'multisafepay').'<a href="http://www.multifactor.nl/contact" target="_blank">http://www.multifactor.nl/faq</a>'.__(' You can also contact Multifactor by calling 020-8500533 (at least 2 hours after this rejection) or by sending an email to ', 'multisafepay').' <a href="mailto:support@multifactor.nl">support@multifactor.nl</a>.'.__(' Please retry placing your order and select a different payment method.', 'multisafepay'), 'error');
					 } else {
					 	wc_add_notice(__('Payment error:', 'multisafepay') . ' ' . $msp->error, 'error');
            		}
                }
            }



            public static function MULTISAFEPAY_PAYAFTER_Add_Gateway($methods) {
//                global $woocommerce;
                $methods[] = 'WC_MULTISAFEPAY_PAYAFTER';

                return $methods;
            }

        }

        // Start
        new WC_MULTISAFEPAY_PAYAFTER();
    }

}
