<?php

/*
  Plugin Name: Multisafepay iDEAL
  Plugin URI: http://www.multisafepay.com
  Description: Multisafepay Payment Plugin
  Author: Multisafepay
  Author URI:http://www.multisafepay.com
  Version: 2.2.4

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
    add_action('plugins_loaded', 'WC_MULTISAFEPAY_IDEAL_Load', 0);

    function WC_MULTISAFEPAY_IDEAL_Load() {

        class WC_MULTISAFEPAY_IDEAL extends WC_Payment_Gateway {

            public function __construct() {
                global $woocommerce;

                $this->init_settings();
                $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');
                $this->id = "MULTISAFEPAY_IDEAL";




                $this->paymentMethodCode = "IDEAL";
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
                add_action("woocommerce_update_options_payment_gateways_MULTISAFEPAY_IDEAL", array($this, 'process_admin_options'));
                add_filter('woocommerce_payment_gateways', array('WC_MULTISAFEPAY_IDEAL', 'MULTISAFEPAY_IDEAL_Add_Gateway'));

                /* $output = '';
                  $output .= "<select name='IDEAL_issuer' style='width:164px; padding: 2px; margin-left: 7px;'>";
                  $output .= '<option>Kies uw bank</option>';

                  if ($this->settings2['testmode'] != 'yes') {
                  $output .= '<option value="0031">ABN AMRO</option>';
                  $output .= '<option value="4371">Bunq</option>';
                  $output .= '<option value="0751">SNS Bank</option>';
                  $output .= '<option value="0721">ING</option>';
                  $output .= '<option value="0021">Rabobank</option>';
                  $output .= '<option value="0761">ASN Bank</option>';
                  $output .= '<option value="0771">Regio Bank</option>';
                  $output .= '<option value="0511">Triodos Bank</option>';
                  $output .= '<option value="0161">Van Lanschot Bankiers</option>';
                  $output .= '<option value="0801">Knab</option>';
                  } else {
                  $output .= '<option value="3151">Test Bank</option>';
                  }
                  $output .= '</select>'; */


                if (file_exists(dirname(__FILE__) . '/images/IDEAL.png')) {
                    $this->icon = apply_filters('woocommerce_multisafepay_ideal_icon', plugins_url('images/IDEAL.png', __FILE__));
                } else {
                    $this->icon = '';
                }

                $this->settings = (array) get_option("woocommerce_{$this->id}_settings");

                if (isset($this->settings['issuers'])) {
                    if ($this->settings['issuers'] == 'yes') {
                        $this->has_fields = true;
                    }
                }

                if (!empty($this->settings['pmtitle'])) {
                    $this->title = $this->settings['pmtitle'];
                    $this->method_title = $this->settings['pmtitle'];
                } else {
                    $this->title = "iDEAL";
                    $this->method_title = "iDEAL";
                }

                /* if (isset($this->settings['issuers'])) {
                  if ($this->settings['issuers'] != 'yes') {
                  $output = '';
                  }
                  } */


                $this->IDEAL_Forms();

                if (isset($this->settings['description'])) {
                    if ($this->settings['description'] != '') {
                        $this->description = $this->settings['description'];
                    }
                }
                //$this->description .= $output;


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

            public function payment_fields() {
                $output = '';
                $output .= "<select name='IDEAL_issuer' style='width:164px; padding: 2px; margin-left: 7px;'>";
                $output .= '<option value="">Kies uw bank</option>';

                if ($this->settings2['testmode'] != 'yes') {
                    $output .= '<option value="0031">ABN AMRO</option>';
                    $output .= '<option value="4371">Bunq</option>';
                    $output .= '<option value="0751">SNS Bank</option>';
                    $output .= '<option value="0721">ING</option>';
                    $output .= '<option value="0021">Rabobank</option>';
                    $output .= '<option value="0761">ASN Bank</option>';
                    $output .= '<option value="0771">Regio Bank</option>';
                    $output .= '<option value="0511">Triodos Bank</option>';
                    $output .= '<option value="0161">Van Lanschot Bankiers</option>';
                    $output .= '<option value="0801">Knab</option>';
                } else {
                    $output .= '<option value="3151">Test Bank</option>';
                }
                $output .= '</select>';
                echo $output;
            }

            public function validate_fields() {
                if (empty($_POST['IDEAL_issuer'])) {
                    wc_add_notice(__('Fout: ', 'multisafepay') . ' ' . 'U heeft nog geen bank geselecteerd.', 'error');
                    return false;
                }
                return true;
            }

            public function IDEAL_Forms() {
                $this->form_fields = array(
                    'stepone' => array(
                        'title' => __('Gateway Setup', 'multisafepay'),
                        'type' => 'title'
                    ),
                    'pmtitle' => array(
                        'title' => __('Title', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('Optional:overwrites the title of the payment method during checkout', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),
                    'enabled' => array(
                        'title' => __('Enable this gateway', 'multisafepay'),
                        'type' => 'checkbox',
                        'label' => __('Enable transaction by using this gateway', 'multisafepay'),
                        'default' => 'yes',
                        'description' => __('When enabled it will show on during checkout', 'multisafepay'),
                    ),
                    'issuers' => array(
                        'title' => __('Enable iDEAL issuers', 'multisafepay'),
                        'type' => 'checkbox',
                        'label' => __('Enable bank selection on website', 'multisafepay'),
                        'default' => 'yes',
                        'description' => __('Enable of disable the selection of the preferred bank within the website.', 'multisafepay'),
                    ),
                    'description' => array(
                        'title' => __('Gateway Description', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('This will be shown when selecting the gateway', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),
                );
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
                $settings = (array) get_option('woocommerce_multisafepay_settings');


                if ($settings['debug'] == 'yes') {
                    $debug = true;
                } else {
                    $debug = false;
                }

                if ($debug) {
                    $this->write_log('MSP->Process payment start');
                }

                global $wpdb, $woocommerce;

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
                $msp->plugin['plugin_version'] = '2.2.4';
                $msp->plugin['partner'] = '';
                $msp->version = '(2.2.4)';
                $msp->transaction['items'] = $html;
                $msp->transaction['var1'] = $order->order_key;
                $msp->transaction['var2'] = $order_id;
                $issuerName = sprintf('%s_issuer', $paymentMethod[1]);



                if (isset($_POST[$issuerName])) {
                    $msp->extravars = $_POST[$issuerName];
                    $url = $msp->startDirectXMLTransaction();
                } else {
                    $url = $msp->startTransaction();
                }


                if ($debug) {
                    $this->write_log('MSP->transactiondata');
                    $this->write_log($msp);
                    $this->write_log('MSP->transaction URL');
                    $this->write_log($url);
                    $this->write_log('MSP->End debug');
                    $this->write_log('--------------------------------------');
                }

                if (!isset($msp->error)) {
                    return array(
                        'result' => 'success',
                        'redirect' => $url
                    );
                } elseif ($msp->error_code == "1036") {
                    wc_add_notice(__('Kies uw bank voor een iDEAL betaling', 'multisafepay'), 'error');
                } else {
                    //$woocommerce->add_error(__('Payment error:', 'multisafepay') . ' ' . $msp->error);
                    wc_add_notice(__('Payment error:', 'multisafepay') . ' ' . $msp->error, 'error');
                }
            }

            public function process_refund($order_id, $amount = null, $reason = '') {

                $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');
                if ($this->settings2['testmode'] == 'yes'):
                    $mspurl = true;
                else :
                    $mspurl = false;
                endif;

                $order = new WC_Order($order_id);
                $ordernumber = ltrim($order->get_order_number(), __('#', '', 'multisafepay'));
                $ordernumber = ltrim($ordernumber, __('n°', '', 'multisafepay'));
                $currency = $order->get_order_currency();

                $msp = new MultiSafepay();
                $msp->test = $mspurl;
                $msp->merchant['account_id'] = $this->settings2['accountid'];
                $msp->merchant['site_id'] = $this->settings2['siteid'];
                $msp->merchant['site_code'] = $this->settings2['securecode'];
                $msp->merchant['api_key'] = $this->settings2['apikey'];
                $msp->transaction['id'] = $ordernumber; //$order_id;
                $msp->transaction['currency'] = $currency;
                $msp->transaction['amount'] = $amount * 100;
                $msp->signature = sha1($this->settings2['siteid'] . $this->settings2['securecode'] . $ordernumber);

                $response = $msp->refundTransaction();


                if ($msp->error) {
                    return new WP_Error('multisafepay_ideal', 'Order can\'t be refunded:' . $msp->error_code . ' - ' . $msp->error);
                } else {
                    return true;
                }
                return false;
            }

            public static function MULTISAFEPAY_IDEAL_Add_Gateway($methods) {
                global $woocommerce;
                $methods[] = 'WC_MULTISAFEPAY_IDEAL';
                return $methods;
            }

        }

        // Start
        new WC_MULTISAFEPAY_IDEAL();
    }

}
