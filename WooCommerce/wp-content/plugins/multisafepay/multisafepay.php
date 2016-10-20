<?php

/*
  Plugin Name: Multisafepay
  Plugin URI: http://www.multisafepay.com
  Description: Multisafepay Payment Plugin
  Author: Multisafepay
  Author URI:http://www.multisafepay.com
  Version: 3.0.0

  Copyright: � 2012 Multisafepay(email : techsupport@multisafepay.com)
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

require_once ('api/Autoloader.php');

load_plugin_textdomain('multisafepay', false, dirname(plugin_basename(__FILE__)) . '/');
register_activation_hook(__FILE__, 'MULTISAFEPAY_register');

function MULTISAFEPAY_register() {
    global $wpdb, $woocommerce;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    wp_insert_term(__('Awaiting Payment', 'multisafepay'), 'shop_order_status');
}

if (!function_exists('is_plugin_active_for_network'))
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    add_action('plugins_loaded', 'WC_MULTISAFEPAY_Load', 0);

    function WC_MULTISAFEPAY_Load() {

        class WC_MULTISAFEPAY_Paymentmethod extends WC_Payment_Gateway {
            public function __construct() {
                global $woocommerce;
                $gateway_info = array(
                    'BABYGIFTCARD'              => 'Baby giftcard',
                    'BOEKENBON'                 => 'Boekenbon',
                    'VVVBON'                    => 'VVV Bon',
                    'EROTIEKBON'                => 'Erotiekbon',
                    'FIJNCADEAU'                => 'Fijncadeau',
                    'PARFUMCADEAUKAART'         => 'Parfum cadeaukaart',
                    'WEBSHOPGIFTCARD'           => 'Webshop giftcard',
                    'FASHIONCHEQUE'             => 'Fashion Cheque',
                    'GEZONDHEIDSBON'            => 'Gezondheidsbon',
                    'LIEF'                      => 'Lief cadeaukaart',
                    'GOODCARD'                  => 'GoodCard',
                    'WIJNCADEAU'                => 'WijnCadeau',
                    'FASHIONGIFTCARD'           => 'Fashion Giftcard',
                    'PODIUM'                    => 'Podium Cadeaukaart',
                    'SPORTENFIT'                => 'Sport en Fit',
                    'YOURGIFT'                  => 'Yourgift',
                    'NATIONALETUINBON'          => 'Nationale tuinbon',
                    'NATIONALEVERWENCADEAUBON'  => 'Nationale verwencadeaubon',
                    'BEAUTYANDWELLNESS'         => 'Beauty and wellness',
                    'FIETSBON'                  => 'Fietsbon',
                    'WELLNESS-GIFTCARD'         => 'Wellness giftcard',
                    'WINKELCHEQUE'              => 'Winkelcheque',
                    'GIVACARD'                  => 'Givacard',
                    'BODYBUILDINGKLEDING'       => 'Bodybuildkleding',
                );
                $gateway_codes = array(
                    '0'  => 'BABYGIFTCARD',
                    '1'  => 'BOEKENBON',
                    '2'  => 'VVVBON',
                    '3'  => 'EROTIEKBON',
                    '4'  => 'FIJNCADEAU',
                    '5'  => 'PARFUMCADEAUKAART',
                    '6'  => 'WEBSHOPGIFTCARD',
                    '7'  => 'FASHIONCHEQUE',
                    '8'  => 'GEZONDHEIDSBON',
                    '9'  => 'LIEF',
                    '10' => 'GOODCARD',
                    '11' => 'WIJNCADEAU',
                    '12' => 'FASHIONGIFTCARD',
                    '13' => 'PODIUM',
                    '14' => 'SPORTENFIT',
                    '15' => 'YOURGIFT',
                    '16' => 'NATIONALETUINBON',
                    '17' => 'NATIONALEVERWENCADEAUBON',
                    '18' => 'BEAUTYANDWELLNESS',
                    '19' => 'FIETSBON',
                    '20' => 'WELLNESS-GIFTCARD',
                    '21' => 'WINKELCHEQUE',
                    '22' => 'GIVACARD',
                    '23' => 'BODYBUILDINGKLEDING',
                );

                $this->init_settings();
                $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');
                $this->id = "multisafepay_" . strtolower($gateway_codes[$this->pmCode]);
                $this->has_fields = false;
                $this->paymentMethodCode = $gateway_codes[$this->pmCode];
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

                $output = '';

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
                    $this->title = $gateway_info[$gateway_codes[$this->pmCode]];
                    $this->method_title = $gateway_info[$gateway_codes[$this->pmCode]];
                }

                $this->GATEWAY_Forms();

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
            public function write_log($log) {
                if (true === WP_DEBUG) {
                    if (is_array($log) || is_object($log)) {
                        error_log(print_r($log, true));
                    } else {
                        error_log($log);
                    }
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
            public function GATEWAY_Forms() {
                $this->form_fields = array(
                    'stepone' => array(
                        'title' => __('Gateway Setup', 'multisafepay'),
                        'type' => 'title'
                    ),
                    'enabled' => array(
                        'title' => __('Enable this gateway', 'multisafepay'),
                        'type' => 'checkbox',
                        'label' => __('Enable transaction by using this gateway', 'multisafepay'),
                        'default' => 'yes',
                        'description' => __('When enabled it will show on during checkout', 'multisafepay'),
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
                );
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
                $msp->transaction['id'] = $ordernumber; //$order_id;
                $msp->transaction['currency'] = get_woocommerce_currency();
                $msp->transaction['amount'] = $order->get_total() * 100;
                $msp->transaction['description'] = 'Order ' . __('#', '', 'multisafepay') . $ordernumber . ' : ' . get_bloginfo();
                $msp->transaction['gateway'] = $gateway;
                $msp->plugin_name = 'WooCommerce';
                $msp->plugin['shop_version'] = $woocommerce->version;
                $msp->plugin['plugin_version'] = '3.0.0';
                $msp->plugin['partner'] = '';
                $msp->version = '(3.0.0)';
                $msp->transaction['items'] = $html;
                $msp->transaction['var1'] = $order->order_key;
                $msp->transaction['var2'] = $order_id;
                $issuerName = sprintf('%s_issuer', $paymentMethod[1]);

                $url = $msp->startTransaction();

                if ($debug) {
                    $this->write_log('MSP->transactiondata');
                    $this->write_log($msp);
                    $this->write_log('MSP->transaction URL');
                    $this->write_log($url);
                    $this->write_log('MSP->End debug');
                    $this->write_log('--------------------------------------');
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
        }

        class WC_MULTISAFEPAY_Paymentmethod_0  extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 0;  }
        class WC_MULTISAFEPAY_Paymentmethod_1  extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 1;  }
        class WC_MULTISAFEPAY_Paymentmethod_2  extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 2;  }
        class WC_MULTISAFEPAY_Paymentmethod_3  extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 3;  }
        class WC_MULTISAFEPAY_Paymentmethod_4  extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 4;  }
        class WC_MULTISAFEPAY_Paymentmethod_5  extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 5;  }
        class WC_MULTISAFEPAY_Paymentmethod_6  extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 6;  }
        class WC_MULTISAFEPAY_Paymentmethod_7  extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 7;  }
        class WC_MULTISAFEPAY_Paymentmethod_8  extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 8;  }
        class WC_MULTISAFEPAY_Paymentmethod_9  extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 9;  }
        class WC_MULTISAFEPAY_Paymentmethod_10 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 10; }
        class WC_MULTISAFEPAY_Paymentmethod_11 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 11; }
        class WC_MULTISAFEPAY_Paymentmethod_12 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 12; }
        class WC_MULTISAFEPAY_Paymentmethod_13 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 13; }
        class WC_MULTISAFEPAY_Paymentmethod_14 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 14; }
        class WC_MULTISAFEPAY_Paymentmethod_15 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 15; }
        class WC_MULTISAFEPAY_Paymentmethod_16 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 16; }
        class WC_MULTISAFEPAY_Paymentmethod_17 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 17; }
        class WC_MULTISAFEPAY_Paymentmethod_18 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 18; }
        class WC_MULTISAFEPAY_Paymentmethod_19 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 19; }
        class WC_MULTISAFEPAY_Paymentmethod_20 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 20; }
        class WC_MULTISAFEPAY_Paymentmethod_21 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 21; }
        class WC_MULTISAFEPAY_Paymentmethod_22 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 22; }
        class WC_MULTISAFEPAY_Paymentmethod_23 extends WC_MULTISAFEPAY_Paymentmethod { protected $pmCode = 23; }

        class WC_MULTISAFEPAY extends WC_Payment_Gateway {

            public function install() {
                $this->create_tables();
            }

            private function create_tables() {
                global $wpdb;
                $wpdb->hide_errors();

                $collate = '';

                if ($wpdb->has_cap('collation')) {
                    if (!empty($wpdb->charset)) {
                        $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
                    }
                    if (!empty($wpdb->collate)) {
                        $collate .= " COLLATE $wpdb->collate";
                    }
                }

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

                $woocommerce_tables = "
			CREATE TABLE {$wpdb->prefix}woocommerce_multisafepay (
	 		 id bigint(20) NOT NULL auto_increment,
	  		trixid varchar(200) NOT NULL,
	  		orderid varchar(200) NOT NULL,
	  		status varchar(200) NOT NULL,
	  		PRIMARY KEY  (id)
			) $collate;
			";
                dbDelta($woocommerce_tables);
            }

            public function __construct() {
                $this->install();
                global $woocommerce;
                $this->init_settings();

                add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
                add_action('woocommerce_update_options_payment_gateways_multisafepay', array($this, 'process_admin_options'));
                add_action('init', array($this, 'MULTISAFEPAY_Response'), 12);
                add_action('woocommerce_order_status_completed', array($this, 'setToShipped'), 13);

                $this->id = 'multisafepay';
                $this->has_fields = false;
                $this->icon = apply_filters('woocommerce_multisafepay_icon', plugins_url('images/msp.gif', __FILE__));
                $this->MULTISAFEPAY_Form();
                $this->init_settings();
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




                if (!empty($this->settings['pmtitle'])) {
                    $this->title = $this->settings['pmtitle'];
                    $this->method_title = $this->settings['pmtitle'];
                } else {
                    $this->method_title = 'MultiSafepay';
                    $this->title = 'MultiSafepay';
                }

                if (empty($woocommerce->fco_added)) {
                    if ($this->settings['enablefco'] == "yes") {
                        $woocommerce->fco_added = true;
                        add_action('woocommerce_proceed_to_checkout', array(&$this, 'checkout_button'), 12);
                    }
                }


                if (isset($_GET['action'])) {
                    if ($_GET['action'] == 'feed') {
                        add_action('woocommerce_api_' . strtolower(get_class()), array($this, 'feed'), 12);
                    } elseif ($_GET['action'] == 'doFastCheckout') {
                        add_action('woocommerce_api_' . strtolower(get_class()), array($this, 'doFastCheckout'), 12);
                    }
                }


                add_filter('woocommerce_payment_gateways', array('WC_MULTISAFEPAY', 'MULTISAFEPAY_Add_Gateway'));

                $this->description = $this->settings['description'];
                if ($this->settings['enabled'] == 'yes') {
                    $this->enabled = 'yes';
                } else {
                    $this->enabled = 'no';
                }
                $this->settings['notifyurl'] = sprintf('%s/index.php?page=multisafepaynotify', get_option('siteurl'));
            }

 function setToShipped($order_id) {
                $order = new WC_Order($order_id);
                $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');

                if ($this->settings2['settoshipped'] == 'yes') {
                    if ($this->settings2['testmode'] == 'yes'):
                        $mspurl = true;
                    else :
                        $mspurl = false;
                    endif;


                    $msp = new MultiSafepay();
                    $msp->test = $mspurl;
                    $msp->merchant['account_id'] = $this->settings['accountid'];
                    $msp->merchant['site_id'] = $this->settings['siteid'];
                    $msp->merchant['site_code'] = $this->settings['securecode'];
                    $msp->transaction['id'] = $order_id;
                    $status = $msp->getStatus();
                    $details = $msp->details;

                    if ($msp->error) {
                        return new WP_Error('multisafepay', 'Can\'t receive transaction data to update correct information at MultiSafepay:' . $msp->error_code . ' - ' . $msp->error);
                    }

                    if ($details['paymentdetails']['type'] == 'KLARNA' || $details['paymentdetails']['type'] == 'PAYAFTER') {

                        $msp = new MultiSafepay();
                        $msp->test = $mspurl;
                        $msp->merchant['account_id'] = $this->settings2['accountid'];
                        $msp->merchant['site_id'] = $this->settings2['siteid'];
                        $msp->merchant['site_code'] = $this->settings2['securecode'];
                        $msp->transaction['id'] = $order_id;
                        $msp->transaction['shipdate'] = date('Y-m-d H:i:s');

                        $response = $msp->updateTransaction();

                        if ($msp->error) {
                            return new WP_Error('multisafepay', 'Transaction status can\'t be updated:' . $msp->error_code . ' - ' . $msp->error);
                        } else {
                            if ($details['paymentdetails']['type'] == 'KLARNA') {
                                $order->add_order_note(__('Klarna Invoice: ') . '<br /><a href="https://online.klarna.com/invoices/' . $details['paymentdetails']['externaltransactionid'] . '.pdf">https://online.klarna.com/invoices/' . $details['paymentdetails']['externaltransactionid'] . '.pdf</a>');
                                echo '<div class="updated"><p>Transaction updated to status shipped.</p></div>';
                            }
                        }
                    }
                }
            }

            function feed() {
                $args = array('post_type' => 'product');
                $products = get_posts($args);

                foreach ($products as $product) {
                    $_pf = new WC_Product_Factory();
                    $_product = $_pf->get_product($product->ID);
                    print_r($_product);
                    exit;
                }
            }

 function doFastCheckout() {

                global $woocommerce;
                WC()->cart->calculate_totals();
                $paymentAmount = number_format(WC()->cart->subtotal, 2, '.', '');

                $language_locale = get_bloginfo('language');
                $language_locale = str_replace('-', '_', $language_locale);

                if ($this->settings['testmode'] == 'yes'):
                    $mspurl = true;
                else :
                    $mspurl = false;
                endif;

                $msp = new MultiSafepay();
                $msp->test = $mspurl;
                $msp->merchant['account_id'] = $this->settings['accountid'];
                $msp->merchant['site_id'] = $this->settings['siteid'];
                $msp->merchant['site_code'] = $this->settings['securecode'];
                $msp->merchant['notification_url'] = $this->settings['notifyurl'] . '&type=initial';
                $msp->merchant['cancel_url'] = WC()->cart->get_cart_url() . 'index.php?type=cancel&cancel_order=true';
                $msp->merchant['redirect_url'] = $this->settings['notifyurl'] . '&type=redirect';
                $msp->merchant['close_window'] = true;
                $msp->customer['locale'] = $language_locale;
                $msp->transaction['id'] = uniqid();
                $msp->transaction['currency'] = get_woocommerce_currency();
                $msp->transaction['amount'] = round(WC()->cart->subtotal * 100);
                $msp->transaction['description'] = 'Woocommerce FCO transaction' . ' : ' . get_bloginfo();
                $msp->transaction['items'] = $item_list;
                $msp->customer['referrer'] = $_SERVER['HTTP_REFERER'];
                $msp->customer['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $msp->customer['ipaddress'] = $_SERVER['REMOTE_ADDR'];
                $msp->use_shipping_notification = false;
                $msp->plugin_name = 'WooCommerce FCO';
                $msp->plugin['shop_version'] = $woocommerce->version;
                $msp->plugin['plugin_version'] = '2.2.0';
                $msp->plugin['partner'] = '';
                $msp->version = '2.2.0';


                $tax_array = array();

                $i = 0;
                foreach (WC()->cart->get_cart() as $cart_item_key => $values) {

                    /*
                     * Get product data from WooCommerce
                     */
                    $_product = $values['data'];
                    $qty = absint($values['quantity']);
                    $sku = $_product->get_sku();
                    $values['name'] = html_entity_decode($_product->get_title(), ENT_NOQUOTES, 'UTF-8');

                    /*
                     * Append variation data to name.
                     */
                    if ($_product->product_type == 'variation') {
                        $meta = WC()->cart->get_item_data($values, true);

                        if (empty($sku)) {
                            $sku = $_product->parent->get_sku();
                        }

                        if (!empty($meta)) {
                            $values['name'] .= " - " . str_replace(", \n", " - ", $meta);
                        }
                    }

                    $product_price = number_format($_product->get_price_excluding_tax(), 4, '.', '');
                    $product_tax_applied = $values['line_tax'] / $qty;
                    $percentage = $product_tax_applied / $product_price * 100;

                    $c_item = new MspItem($values['name'] . " " . get_woocommerce_currency(), '', $qty, $product_price, 'KG', 0);
                    $msp->cart->AddItem($c_item);
                    $json_array = array();
                    $json_array['sku'] = $sku;
                    $c_item->SetMerchantItemId(json_encode($json_array));
                    $c_item->SetTaxTableSelector($percentage);

                    $my_taxrate = round($percentage) / 100;
                    $my_taxname = $percentage;
                    $tax_array[$i]['name'] = $my_taxname;
                    $tax_array[$i]['rate'] = $my_taxrate;
                    $i++;
                }


                $tax_array = array_unique($tax_array);
                $a = 0;
                while ($tax_array[$a]) {
                    $table = new MspAlternateTaxTable();
                    $table->name = $tax_array[$a]['name'];
                    $rule = new MspAlternateTaxRule($tax_array[$a]['rate']);
                    $table->AddAlternateTaxRules($rule);
                    $msp->cart->AddAlternateTaxTables($table);
                    $a++;
                }


                $table = new MspAlternateTaxTable();
                $table->name = 'BTW0';
                $rule = new MspAlternateTaxRule('0.00');
                $table->AddAlternateTaxRules($rule);
                $msp->cart->AddAlternateTaxTables($table);



                /**
                 * Add custom Woo cart fees as line items
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

                /*
                 * Get discount(s)
                 */
                if (WC()->cart->get_cart_discount_total()) {
                    foreach (WC()->cart->get_coupons('cart') as $code => $coupon) {
                        $c_item = new MspItem('Cart Discount ' . $code, '', 1, -number_format(WC()->cart->coupon_discount_amounts[$code], 2, '.', ''), 'KG', 0);
                        $msp->cart->AddItem($c_item);
                        $json_array = array();
                        $json_array['cartcoupon'] = $code;
                        $c_item->SetMerchantItemId(json_encode($json_array));
                        $c_item->SetTaxTableSelector('BTW0');
                    }
                }

                if (WC()->cart->get_order_discount_total()) {
                    foreach (WC()->cart->get_coupons('order') as $code => $coupon) {
                        $c_item = new MspItem('Order Discount ' . $code, '', 1, -number_format(WC()->cart->coupon_discount_amounts[$code], 2, '.', ''), 'KG', 0);
                        $msp->cart->AddItem($c_item);
                        $json_array = array();
                        $json_array['ordercoupon'] = $code;
                        $c_item->SetMerchantItemId(json_encode($json_array));
                        $c_item->SetTaxTableSelector('BTW0');
                    }
                }

                $chosen_methods = WC()->session->get('chosen_shipping_methods');
                $chosen_shipping = $chosen_methods[0];
                WC()->shipping->calculate_shipping($this->get_shipping_packages());


                $tax_shipping = false;
                $shipping_taxes = array();

                foreach (WC()->shipping->packages[0]['rates'] as $rate) {

                    if (!empty($rate->taxes) && !$tax_shipping) {
                        $tax_shipping = true;
                        foreach ($rate->taxes as $tax_id => $value) {
                            $shipping_taxes = WC_Tax::get_shipping_tax_rates($tax_id);
                            foreach ($shipping_taxes as $ship_tax_rate => $value) {
                                if ($value['shipping'] == 'yes') {
                                    $final_ship_rate = $value['rate'] / 100;
                                    $rule = new MspDefaultTaxRule($final_ship_rate, $tax_shipping);
                                    $msp->cart->AddDefaultTaxRules($rule);
                                }
                            }
                        }
                    }
                    $shipping_method = new MspFlatRateShipping($rate->label, number_format($rate->cost, '2', '.', ''));
                    $msp->cart->AddShipping($shipping_method);
                }

                $url = $msp->startCheckout();

                if (!$msp->error) {
                    wp_redirect($url);
                    exit;
                } else {
                    echo $msp->error;
                }
            }

 public function process_refund($order_id, $amount = null, $reason = '') {
				global $wpdb;
                $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');
                if ($this->settings2['testmode'] == 'yes'):
                    $mspurl = true;
                else :
                    $mspurl = false;
                endif;

                $order = new WC_Order($order_id);
                $currency = $order->get_order_currency();

                $results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'woocommerce_multisafepay WHERE orderid = \'' . $order_id . '\'', OBJECT);

                if (!empty($results)) {
                      $transactionid=$results[0]->trixid;
                }else{
	                $transactionid=$order_id;
                }

                $msp = new MultiSafepay();
                $msp->test = $mspurl;
                $msp->merchant['account_id'] = $this->settings2['accountid'];
                $msp->merchant['site_id'] = $this->settings2['siteid'];
                $msp->merchant['site_code'] = $this->settings2['securecode'];
                $msp->merchant['api_key'] = $this->settings2['apikey'];
                $msp->transaction['id'] = $transactionid;
                $msp->transaction['currency'] = $currency;
                $msp->transaction['amount'] = $amount * 100;
                $msp->signature = sha1($this->settings2['siteid'] . $this->settings2['securecode'] . $transactionid);

                $response = $msp->refundTransaction();


                if ($msp->error) {
                    return new WP_Error('multisafepay_ideal', 'Order can\'t be refunded:' . $msp->error_code . ' - ' . $msp->error);
                } else {
                    return true;
                }
                return false;
            }

            public function get_shipping_packages() {
                // Packages array for storing 'carts'
                $packages = array();
                $packages[0]['contents'] = WC()->cart->cart_contents;  // Items in the package
                $packages[0]['contents_cost'] = 0;      // Cost of items in the package, set below
                $packages[0]['applied_coupons'] = WC()->session->applied_coupon;
                $packages[0]['destination']['country'] = WC()->customer->get_shipping_country();
                $packages[0]['destination']['state'] = WC()->customer->get_shipping_state();
                $packages[0]['destination']['postcode'] = WC()->customer->get_shipping_postcode();
                $packages[0]['destination']['city'] = WC()->customer->get_shipping_city();
                $packages[0]['destination']['address'] = WC()->customer->get_shipping_address();
                $packages[0]['destination']['address_2'] = WC()->customer->get_shipping_address_2();

                foreach (WC()->cart->get_cart() as $item)
                    if ($item['data']->needs_shipping())
                        if (isset($item['line_total']))
                            $packages[0]['contents_cost'] += $item['line_total'];

                return apply_filters('woocommerce_cart_shipping_packages', $packages);
            }

            function checkout_button() {
                if (get_woocommerce_currency() == 'EUR') {
                    $button_locale_code = get_locale();
                    $image = plugins_url('/images/' . $button_locale_code . '/button.png', __FILE__);

                    echo '<div id="msp_fastcheckout" >';
                    echo '<a class="checkout-button"  style="width:219px;border:none;margin-bottom:15px;" href="' . add_query_arg('action', 'doFastCheckout', add_query_arg('wc-api', 'WC_MULTISAFEPAY', home_url('/'))) . '">';
                    echo "<img src='" . $image . "' style='border:none;vertical-align: center;width: 219px;border-radius: 0px;box-shadow: none;padding: 0px;' border='0' alt='" . __('Pay with FastCheckout', 'multisafepay') . "'/>";
                    echo "</a>";
                    echo '</div>';
                }
            }

            public function MULTISAFEPAY_Form() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title'         => __('Enable Multisafepay', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __('Enable Multisafepay for processing transactions', 'multisafepay'),
                        'default'       => 'no',
                        'description'   => __('Only enable if you want to select the payment method on the website from Multisafepay instead of youre own chackout page.', 'multisafepay'),
                        'desc_tip'      => true,
                    ),

                    'apikey' => array(
                        'title'         => __('API Key', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('Copy the API-Key from your MultiSafepay account', 'multisafepay'),
                        'desc_tip'      => true,
                        'css'           => 'width: 300px;'
                    ),

                    'testmode' => array(
                        'title'         => __('Multisafepay sandbox', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __('Enable Multisafepay sandbox', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('Use this if you want to test transactions (You need a MultiSafepay test account for this.)', 'multisafepay'),
                        'desc_tip'      => true,
                    ),

                    'notifyurl' => array(
                        'title'         => __('Notification url', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('Copy&Paste this URL to your website configuration Notification-URL at your Multisafepay dashboard.', 'multisafepay'),
                        'desc_tip'      => true,
                        'css'           => 'width: 800px;',
                    ),

                    'pmtitle' => array(
                        'title'         => __('Title', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('Optional:overwrites the title of the payment method during checkout', 'multisafepay'),
                        'desc_tip'      => true,
                        'css'           => 'width: 300px;'
                    ),

                    'description' => array(
                        'title'         => __('Gateway Description', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('This will be shown when selecting the gateway', 'multisafepay'),
                        'css'           => 'width: 300px;',
                        'desc_tip'      => true,
                    ),

                    'gateways' => array(
                        'title'         => __('Coupons', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __('Enable MultiSafepay coupons', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('This will add the coupons available within your MultiSafepay account', 'multisafepay'),
                        'desc_tip'      => true,
                    ),
                    'enablefco' => array(
                        'title'         => __('FastCheckout', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __('Enable FastCheckout', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('This will enable the FastCheckout button in checkout', 'multisafepay'),
                        'desc_tip'      => true,
                    ),

                    'send_invoice' => array(
                        'title'         => __('Send invoice after completed transaction', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __('Select to send the invoice', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('The invoice will be sent when a transaction is completed', 'multisafepay'),
                        'desc_tip'      => true,
                    ),
                    'send_confirmation' => array(
                        'title'         => __('Sent order confirmation', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __('Sent the order confirmation', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('Select this to sent the order confirmation before the transaction', 'multisafepay'),
                        'desc_tip'      => true,
                    ),
                    'debug' => array(
                        'title'         => __('Enable debugging', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __('Enable debugging', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('When enabled (and wordpress debug is enabled it will log transactions)', 'multisafepay'),
                        'desc_tip'      => true,
                    ),
                );
            }

            public function write_log($log) {
                if (true === WP_DEBUG) {
                    if (is_array($log) || is_object($log))
                        error_log(print_r($log, true));
                    else
                        error_log($log);
                }
            }

            public function process_payment($order_id) {
                global $wpdb, $woocommerce;

                $settings = (array) get_option('woocommerce_multisafepay_settings');

                if ($settings['debug'] == 'yes')
                    $debug = true;
                else
                    $debug = false;

                if ($debug)
                    $this->write_log('MSP->Process payment start..');

                if ($this->settings['send_confirmation'] == 'yes') {
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

                if ($this->settings['testmode'] == 'yes'):
                    $mspurl = true;
                else :
                    $mspurl = false;
                endif;


                $ordernumber = ltrim($order->get_order_number(), __('#', '', 'multisafepay'));
                $ordernumber = ltrim($ordernumber, __('n°', '', 'multisafepay'));

                $msp = new Client();

				$api  = $this->settings['apikey'];
				$mode = $this->settings['testmode'];

				$msp->setApiKey($api);
				$msp->setApiUrl($mode);

				list ($billingStreet,  $billingHouseNumber)  = $msp->parseCustomerAddress($order->billing_address_1);
				list ($shippingStreet, $shippingHouseNumber) = $msp->parseCustomerAddress($order->shipping_address_1);

				$type = (isset ($this->type) ? $this->type : 'redirect');

                $my_order = array(
                      "type"        		=> $type,
                      "order_id"            => $ordernumber,
                      "currency"            => get_woocommerce_currency(),
                      "amount"              => round($order->get_total() * 100),
                      "description"         => 'Order ' . __('#', '', 'multisafepay') . $ordernumber . ' : ' . get_bloginfo(),
                      "var1"                => $order->order_key,
                      "var2"                => $order_id,
                      "var3"                => '',
                      "items"               => $html,
                      "manual"              => 0,
                      "gateway"             => '',
                      "seconds_active"      => 3600*100, //$this->settings[''],
                      "payment_options" => array(
                          "notification_url"=> $this->settings['notifyurl'] . '&type=initial',
                          "redirect_url"    => add_query_arg('utm_nooverride', '1', $this->get_return_url($order)),
                          "cancel_url"		=> htmlspecialchars_decode(add_query_arg('key', $order->id, $order->get_cancel_order_url())),
                          "close_window"    => "true"
                      ),

                      "customer" => array(
                            "locale"          => $language_locale,
                            "ip_address"      => $_SERVER['REMOTE_ADDR'],
                            "forwarded_ip"    => '',
                            "referrer"		  => $_SERVER['HTTP_REFERER'],
                            "user_agent"	  => $_SERVER['HTTP_USER_AGENT'],
                            "first_name"      => $order->billing_first_name,
                            "last_name"       => $order->billing_last_name,
                            "address1"        => $billingStreet,
                            "address2"        => '',
                            "house_number"    => $billingHouseNumber,
                            "zip_code"        => $order->billing_postcode,
                            "city"            => $order->billing_city,
                            "state"           => $order->billing_state,
                            "country"         => $order->billing_country,
                            "phone"           => $order->billing_phone,
                            "birthday"        => '',
                            "email"           => $order->billing_email,
                      ),

                      "delivery" => array(
                            "locale"          => $language_locale,
                            "ip_address"      => $_SERVER['REMOTE_ADDR'],
                            "forwarded_ip"    => '',
                            "referrer"		  => $_SERVER['HTTP_REFERER'],
                            "user_agent"	  => $_SERVER['HTTP_USER_AGENT'],
                            "first_name"      => $order->shipping_first_name,
                            "last_name"       => $order->shipping_last_name,
                            "address1"        => $shippingStreet,
                            "address2"        => '',
                            "house_number"    => $shippingHouseNumber,
                            "zip_code"        => $order->shipping_postcode,
                            "city"            => $order->shipping_city,
                            "state"           => $order->shipping_state,
                            "country"         => $order->shipping_country,
                            "phone"           => $order->shipping_phone,
                            "birthday"        => '1990-01-01',
                            "strange"        => 'strange',
                            "email"           => $order->shipping_email,
                      ),


                //      "gateway_info"        => $this->gatewayInfo,
                //      "shopping_cart"       => (isset ($this->shopping_cart)    ? $this->shopping_cart    : array()),
                //      "checkout_options"    => (isset ($this->checkout_options) ? $this->checkout_options : array()),

                      "google_analytics" => array(
                          "account" => "UA-XXXXXXXXX",
                      ),

                      "plugin" => array(
                          "shop"            => "WooCommerce",
                          "shop_version"    => 'WooCommerce '. $woocommerce->version,
                          "plugin_version"  => '(3.0.0)',
                          "partner"         => '',
                          "shop_root_url"   => '',
                      ),
                      "custom_info" => array(
                          "custom_1" => '',
                          "custom_2" => '',
                      )
                );

                try {
                    $msp->orders->post($my_order);
                    $url = $msp->orders->getPaymentLink();
                } catch (Exception $e) {

                    $msg = 'Error: ' . htmlspecialchars($e->getMessage());
                    echo $msg;
                    if ($debug)
                        $this->write_log($msg);
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

            // MSP Response
 public function Multisafepay_Response() {
                global $wpdb, $wp_version, $woocommerce;
                $redirect = false;
                $initial_request = false;

                if (isset($_GET['transactionid'])) {

                    if ($_GET['type'] == 'initial')
                        $initial_request = true;

                    if ($_GET['type'] == 'redirect')
                        $redirect = true;

                    if ($_GET['type'] == 'cancel')
                        return true;

                    $transactionid = $_GET['transactionid'];

                    $msp = new Client();

                    $api  = $this->settings['apikey'];
                    $mode = $this->settings['testmode'];

                    $msp->setApiKey($api);
                    $msp->setApiUrl($mode);

                    try {
                        $this->transactie = $msp->orders->get($transactionid, 'orders', array(), false);
                    } catch (Exception $e) {

                        $msg = "Unable to get transaction. Error: " . htmlspecialchars($e->getMessage());
                        echo $msg;
                    }

    $string =  'transactie: '. print_r ($this->transactie, true);
//    mail ('Testbestelling-Ronald@Multisafepay.com', 'debug - ' . $_SERVER['SCRIPT_FILENAME'], $string);

                    $updated = false;

                    $status         = $this->transactie->status;
                    $amount         = $this->transactie->amount / 100;
                    $transactie_id  = $this->transactie->transaction_id;
                    $order_id       = $this->transactie->order_id;

                    $order = new WC_Order($order_id);
                    $results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'woocommerce_multisafepay WHERE trixid = \'' . $transactie_id . '\'', OBJECT);

                    if (!empty($results)) {
                        $order = new WC_Order($results[0]->orderid);
                    }

                    $gateway = $this->transactie->payment_details->type;

                    if ($this->transactie->fastcheckout == 'YES' && empty($results)) {
                        if (empty($transactie_id)) {
                            $location = $woocommerce->cart->get_cart_url();
                            wp_safe_redirect($location);
                            exit();
                        }

                        if (!empty($this->transactie->shopping_cart)) {


                            $order = wc_create_order();
                            $wpdb->query("INSERT INTO " . $wpdb->prefix . woocommerce_multisafepay . " ( trixid, orderid, status ) VALUES ( '" . $transactie_id . "', '" . $order->id . "', '" . $status . "'  )");

                            $billing_address = array();
                            $billing_address['address_1']   = $this->transactie->customer->address1 . ' ' . $this->transactie->customer->house_number;
                            $billing_address['address_2']   = $this->transactie->customer->address2;
                            $billing_address['city']        = $this->transactie->customer->city;
                            $billing_address['state']       = $this->transactie->customer->state;
                            $billing_address['postcode']    = $this->transactie->customer->zip_code;
                            $billing_address['country']     = $this->transactie->customer->country;
                            $billing_address['phone']       = $this->transactie->customer->phone1;
                            $billing_address['email']       = $this->transactie->customer->email;

                            $shipping_address = array();
                            $shipping_address['address_1']  = $this->transactie->delivery->address1 . ' ' . $this->transactie->delivery->house_number;
                            $shipping_address['address_2']  = $this->transactie->delivery->address2;
                            $shipping_address['city']       = $this->transactie->delivery->city;
                            $shipping_address['state']      = $this->transactie->delivery->state;
                            $shipping_address['postcode']   = $this->transactie->delivery->zip_code;
                            $shipping_address['country']    = $this->transactie->delivery->country;
                            $shipping_address['phone']      = $this->transactie->delivery->phone1;
                            $shipping_address['email']      = $this->transactie->delivery->email;

                            $order->set_address($billing_address,  'billing');
                            $order->set_address($shipping_address, 'shipping');
// ===>  nog doen ......
            foreach ($woocommerce->shipping->load_shipping_methods() as $shipping_method) {
                if ($shipping_method->title === $details['shipping']['name']) {
                    $shipping['method_title'] = $details['shipping']['name'];
                    $shipping['total'] = $details['shipping']['cost'];
                    $rate = new WC_Shipping_Rate($shipping->id, isset($shipping['method_title']) ? $shipping['method_title'] : '', isset($shipping['total']) ? floatval($shipping['total']) : 0, array(), $shipping->id);
                }
            }
// ===>  tot hier ......
                            $order->add_shipping($rate);
                            $order->add_order_note($transactie_id);

                            $gateways = new WC_Payment_Gateways();
                            $all_gateways = $gateways->get_available_payment_gateways();

                            foreach ($all_gateways as $gateway) {
                                if ($gateway->id == "MULTISAFEPAY") {
                                    $selected_gateway = $gateway;
                                }
                            }

                            $order->set_payment_method($selected_gateway);
                            $return_url         = $order->get_checkout_order_received_url();
                            $cancel_url         = $order->get_cancel_order_url();
                            $view_order_url     = $order->get_view_order_url();
                            $retry_payment_url  = $order->get_checkout_payment_url();


    $string =  'shoppincart: '. print_r ($this->transactie->shopping_cart, true);
    mail ('Testbestelling-Ronald@Multisafepay.com', 'debug - ' . $_SERVER['SCRIPT_FILENAME'], $string);





                            foreach ($details['shopping-cart'] as $product) {
                                $sku = json_decode($product['merchant-item-id']);
                                if (!empty($sku->sku)) {
                                    $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku->sku));
                                    $product_item = new WC_Product($product_id);
                                    $product_item->qty = $product['quantity'];
                                    $order->add_product($product_item, $product['quantity']);
                                } elseif (!empty($sku->cartcoupon)) {
                                    $code = $sku->cartcoupon;
                                    $amount = (float) str_replace('-', '', $product['unit-price']);
                                    update_post_meta($order->id, '_cart_discount', $amount);
                                    update_post_meta($order->id, '_order_total', $details['transaction']['amount'] / 100);
                                    $tax_percentage = (($details['transaction']['amount'] / 100) - ($details['order-total']['total'] - $details['total-tax']['total'] + $details['shipping']['cost'])) / ($details['order-total']['total'] - $details['total-tax']['total'] + $details['shipping']['cost']);
                                    $applied_discount_tax = round(($amount * (1 + $tax_percentage)) - $amount, 2);
                                    update_post_meta($order->id, '_cart_discount_tax', $applied_discount_tax);
                                    $order->calculate_taxes();
                                    $order_data = get_post_meta($order->id);
                                    $new_order_tax = round($order_data['_order_tax'][0] - (($amount * (1 + $tax_percentage)) - $amount), 2);
                                    update_post_meta($order->id, '_order_tax', $new_order_tax);
                                    $id = $order->add_coupon($code, $amount, $applied_discount_tax);
                                } elseif (!empty($sku->ordercoupon)) {
                                    $code = $sku->ordercoupon;
                                    $amount = (float) str_replace('-', '', $product['unit-price']);
                                    update_post_meta($order->id, '_cart_discount', $amount);
                                    update_post_meta($order->id, '_order_total', $details['transaction']['amount'] / 100);
                                    $tax_percentage = (($details['transaction']['amount'] / 100) - ($details['order-total']['total'] - $details['total-tax']['total'] + $details['shipping']['cost'])) / ($details['order-total']['total'] - $details['total-tax']['total'] + $details['shipping']['cost']);
                                    $applied_discount_tax = round(($amount * (1 + $tax_percentage)) - $amount, 2);
                                    update_post_meta($order->id, '_cart_discount_tax', $applied_discount_tax);
                                    $order->calculate_taxes();
                                    $order_data = get_post_meta($order->id);
                                    $new_order_tax = round($order_data['_order_tax'][0] - (($amount * (1 + $tax_percentage)) - $amount), 2);
                                    update_post_meta($order->id, '_order_tax', $new_order_tax);
                                    $id = $order->add_coupon($code, $amount, $applied_discount_tax);
                                } elseif (!empty($sku->fee)) {
                                    //TODO PROCESS CART FEE
                                }
                            }
                            update_post_meta($order->id, '_order_total', $details['transaction']['amount'] / 100);
                            $order->calculate_taxes();

                            foreach ($order->get_items('tax') as $key => $value) {
                                $data = wc_get_order_item_meta($key, 'tax_amount');
                                wc_update_order_item_meta($key, 'tax_amount', $data - $applied_discount_tax);
                            }


                            $amount = $details['transaction']['amount'] / 100;


                            switch ($status) {
                                case 'cancelled':
                                    $order->cancel_order();
                                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    $updated = true;
                                    break;
                                case 'initialized':
                                    if ($gateway == 'BANKTRANS') {
                                        $order->update_status('wc-on-hold', sprintf(__('Banktransfer payment. Waiting for payment update', 'multisafepay'), $amount));
                                        $return_url = $order->get_checkout_order_received_url();
                                        $updated = true;
                                        break;
                                    } else {
                                        $order->update_status('wc-pending');
                                        $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                        $updated = true;
                                        break;
                                    }
                                case 'completed':
                                    if ($order->get_total() != $amount) {
                                        if ($order->status != 'processing') {
                                            $order->update_status('wc-on-hold', sprintf(__('Validation error: Multisafepay amounts do not match (gross %s).', 'multisafepay'), $amount));
                                            $return_url = $order->get_checkout_order_received_url();

                                            if ($redirect) {
                                                wp_redirect($return_url);
                                                exit;
                                            }
                                        }
                                    }

                                    if ($order->status != 'processing' && $order->status != 'completed' && $order->status != 'wc-completed') {
                                        $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                        //$order->update_status('wc-processing');
                                        //$order->reduce_order_stock();
                                        $woocommerce->cart->empty_cart();
                                        $order->payment_complete();

                                        $mailer = $woocommerce->mailer();
                                        /* if ($this->settings['send_confirmation'] == 'no') {
                                          $email = $mailer->emails['WC_Email_New_Order'];
                                          $email->trigger($order->id);
                                          }
                                          $email = $mailer->emails['WC_Email_Customer_Processing_Order'];
                                          $email->trigger($order->id); */

                                        if ($this->settings['send_invoice'] == 'yes') {
                                            $mailer->customer_invoice($order);
                                        }
                                        if ($order->status == 'processing') {
                                            $updated = true;
                                        }
                                    } else {
                                        $updated = true;
                                    }
                                    break;
                                case 'refunded':
                                    /* if ($order->get_total() == $amount) {
                                      $order->update_status('wc-refunded', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                                      $order->add_order_note(sprintf(__('Multisafepay payment status', 'multisafepay'), $status));
                                      }
                                      $updated = true; */
                                    break;
                                case 'uncleared' :

                                    $order->update_status('wc-on-hold');
                                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    $updated = true;
                                    break;
                                case 'reserved' :
                                case 'declined':
                                    $order->update_status('wc-failed', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    $updated = true;
                                    break;
                                case 'expired':
                                    $order->update_status('wc-failed', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    $updated = true;
                                    break;
                                case 'void' :
                                    $order->cancel_order();
                                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    $updated = true;
                                    break;
                            }

                            if ($redirect) {
                                wp_redirect($return_url);
                                exit;
                            }
                        }

                        if ($redirect) {
                            wp_redirect($return_url);
                            exit;
                        }

                        if ($initial_request) {
                            $location = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))));
                            echo '<a href=' . $location . '>' . __('Klik hier om terug te keren naar de website', 'multisafepay') . '</a>';
                            exit;
                        } else {
                            header("Content-type: text/plain");
                            if ($updated == true) {
                                if (isset($_GET['cancel_order'])) {
                                    $order->cancel_order();
                                    $location = $woocommerce->cart->get_cart_url();
                                    wp_safe_redirect($location);
                                    exit();
                                } elseif (isset($_GET['order']) || isset($_GET['key'])) {
                                    $location = $order->get_checkout_order_received_url();
                                    wp_safe_redirect($location);
                                    exit();
                                } else {
                                    echo 'OK';
                                }
                            } else {
                                if (isset($_GET['cancel_order'])) {
                                    $order->cancel_order();
                                    $location = $woocommerce->cart->get_cart_url();
                                    wp_safe_redirect($location);
                                    exit();
                                } elseif (isset($_GET['order']) || isset($_GET['key'])) {
                                    $location = $order->get_checkout_order_received_url();
                                    wp_safe_redirect($location);
                                    exit();
                                } else {
                                    echo 'OK';
                                }
                            }
                            exit;
                        }
                    } else {

                        if ($order->status != 'processing') {
                            switch ($status) {
                                case 'cancelled':
                                    $order->cancel_order();
                                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    $updated = true;
                                    break;
                                case 'initialized';
                                    if ($gateway == 'BANKTRANS') {
                                        $order->update_status('wc-on-hold', sprintf(__('Banktransfer payment. Waiting for payment update', 'multisafepay'), $amount));
                                        $return_url = $order->get_checkout_order_received_url();
                                        $updated = true;
                                        break;
                                    } else {
                                        $order->update_status('wc-pending');
                                        $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                        $updated = true;
                                        break;
                                    }
                                case 'completed':
                                    if ($order->get_total() != $amount) {
                                        if ($order->status != 'processing') {
                                            $order->update_status('wc-on-hold', sprintf(__('Validation error: Multisafepay amounts do not match (gross %s).', 'multisafepay'), $amount));
                                            if ($redirect) {
                                                $return_url = $order->get_checkout_order_received_url();
                                                wp_redirect($return_url);
                                                exit;
                                            }
                                        }
                                    }

                                    if ($order->status != 'processing' && $order->status != 'completed' && $order->status != 'wc-completed') {

                                        $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                        //$order->update_status('wc-processing');
                                        $order->payment_complete();
                                        //$order->reduce_order_stock();
                                        $woocommerce->cart->empty_cart();
                                        $mailer = $woocommerce->mailer();
                                        /* if ($this->settings['send_confirmation'] == 'no') {
                                          $email = $mailer->emails['WC_Email_New_Order'];
                                          $email->trigger($order->id);
                                          }
                                          $email = $mailer->emails['WC_Email_Customer_Processing_Order'];
                                          $email->trigger($order->id); */
                                        if ($this->settings['send_invoice'] == 'yes') {
                                            $mailer->customer_invoice($order);
                                        }
                                        if ($order->status == 'processing') {
                                            $updated = true;
                                        }
                                    } else {
                                        $updated = true;
                                    }

                                    if ($status == 'completed' && $details['paymentdetails']['type'] == 'KLARNA') {
                                        $order->add_order_note(__("Klarna Reservation number: ") . $details['paymentdetails']['externaltransactionid']);
                                    }

                                    break;
                                case 'refunded':
                                    if ($order->get_total() == $amount) {
                                        $order->update_status('wc-refunded', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                                        $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    }
                                    $updated = true;
                                    break;
                                case 'uncleared' :
                                    $order->update_status('wc-on-hold');
                                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    $updated = true;
                                    break;
                                case 'reserved' :
                                case 'declined':
                                    $order->update_status('wc-failed', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    $updated = true;
                                    break;
                                case 'expired':
                                    $order->update_status('wc-failed', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    $updated = true;
                                    break;
                                case 'void' :
                                    $order->cancel_order();
                                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                                    $updated = true;
                                    break;
                            }
                        } else {
                            if ($status == 'shipped') {
                                $order->add_order_note(__('Klarna Invoice: ') . '<br /><a href="https://online.klarna.com/invoices/' . $details['paymentdetails']['externaltransactionid'] . '.pdf">https://online.klarna.com/invoices/' . $details['paymentdetails']['externaltransactionid'] . '.pdf</a>');
                            }
                        }
                        $return_url = $order->get_checkout_order_received_url();
                        $cancel_url = $order->get_cancel_order_url();
                        $view_order_url = $order->get_view_order_url();
                        $retry_payment_url = $order->get_checkout_payment_url();

                        if ($redirect) {
                            wp_redirect($return_url);
                            exit;
                        }

                        if ($initial_request) {
                            $location = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))));
                            echo '<a href=' . $location . '>Klik hier om terug te keren naar de website</a>';
                            exit;
                        } else {
                            header("Content-type: text/plain");
                            if ($updated == true) {
                                if (isset($_GET['cancel_order'])) {
                                    $order->cancel_order();
                                    $location = $woocommerce->cart->get_cart_url();
                                    wp_safe_redirect($location);
                                    exit();
                                } elseif (isset($_GET['order']) || isset($_GET['key'])) {
                                    $location = $order->get_checkout_order_received_url();
                                    wp_safe_redirect($location);
                                    exit();
                                } else {
                                    echo 'OK';
                                }
                            } else {
                                if (isset($_GET['cancel_order'])) {
                                    $order->cancel_order();
                                    $location = $woocommerce->cart->get_cart_url();
                                    wp_safe_redirect($location);
                                    exit();
                                } elseif (isset($_GET['order']) || isset($_GET['key'])) {
                                    $location = $order->get_checkout_order_received_url();
                                    wp_safe_redirect($location);
                                    exit();
                                } else {
                                    echo 'OK';
                                }
                            }
                            exit;
                        }
                    }



                    if ($initial_request) {
                        $location = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))));
                        echo '<a href=' . $location . '>Klik hier om terug te keren naar de website</a>';
                        exit;
                    } else {
                        echo 'OK';
                        exit;
                    }
                }
            }

            public static function MULTISAFEPAY_Add_Gateway($methods) {
                global $woocommerce;
                $methods[] = 'WC_MULTISAFEPAY';
                $settings = (array) get_option('woocommerce_multisafepay_settings');
                if (isset($settings['gateways'])) {
                    if ($settings['gateways'] == 'yes') {
                        $gateway_codes = array(
                            '0' => 'BABYGIFTCARD',
                            '1' => 'BOEKENBON',
                            '2' => 'VVVBON',
                            '3' => 'EROTIEKBON',
                            '4' => 'FIJNCADEAU',
                            '5' => 'PARFUMCADEAUKAART',
                            '6' => 'WEBSHOPGIFTCARD',
                            '7' => 'FASHIONCHEQUE',
                            '8' => 'GEZONDHEIDSBON',
                            '9' => 'LIEF',
                            '10' => 'GOODCARD',
                            '11' => 'WIJNCADEAU',
                            '12' => 'FASHIONGIFTCARD',
                            '13' => 'PODIUM',
                            '14' => 'SPORTENFIT',
                            '15' => 'YOURGIFT',
                            '16' => 'NATIONALETUINBON',
                            '17' => 'NATIONALEVERWENCADEAUBON',
                            '18' => 'BEAUTYANDWELLNESS',
                            '19' => 'FIETSBON',
                            '20' => 'WELLNESS-GIFTCARD',
                            '21' => 'WINKELCHEQUE',
                            '22' => 'GIVACARD',
                            '23' => 'BODYBUILDINGKLEDING',
                        );

                        $i = 0;
                        foreach ($gateway_codes as $pm) {
                            $methods[] = "WC_MULTISAFEPAY_Paymentmethod_{$i}";
                            $i++;
                        }
                    }
                }
                return $methods;
            }

        }

        // Start
        new WC_MULTISAFEPAY();
    }

}