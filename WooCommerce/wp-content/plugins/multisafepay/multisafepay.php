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
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    wp_insert_term(__('Awaiting Payment', 'multisafepay'), 'shop_order_status');
}

if (!function_exists('is_plugin_active_for_network'))
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    add_action('plugins_loaded', 'WC_MULTISAFEPAY_Load', 0);

    function WC_MULTISAFEPAY_Load() {

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
                            id      bigint(20)   NOT NULL auto_increment,
                            trixid  varchar(200) NOT NULL,
                            orderid varchar(200) NOT NULL,
                            status  varchar(200) NOT NULL,
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
//                    $status = $msp->getStatus();
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

            public function doFastCheckout() {
                global $woocommerce;
				
				$settings = (array) get_option('woocommerce_multisafepay_settings');
                $debug    = $this->getDebugMode ($settings['debug']);
                   
                $msp   = new Client();

                $api  = $this->settings['apikey'];
                $mode = $this->settings['testmode'];

                $msp->setApiKey($api);
                $msp->setApiUrl($mode);

                $order_id = uniqid();

                $my_order =
                    array(
                        "type"        		    => 'checkout',
                        "order_id"              => $order_id,
                        "currency"              => get_woocommerce_currency(),
                        "amount"                => round(WC()->cart->subtotal * 100),
                        "description"           => 'Order #' . $order_id,
                        "var1"                  => '',
                        "var2"                  => '',
                        "var3"                  => '',
                        "items"                 => $this->setItemListFCO(),
                        "manual"                => false,
                        "gateway"               => '',
                        "seconds_active"        => $this->setSecondsActive($this->settings),
                        "payment_options"       => array(
                            "notification_url"  => $this->settings['notifyurl'] . '&type=initial',
                            "redirect_url"      => $this->settings['notifyurl'] . '&type=redirect',
                            "cancel_url"		=> WC()->cart->get_cart_url() . 'index.php?type=cancel&cancel_order=true',
                            "close_window"      => true
                        ),
                        "customer"              => $this->setCustomer($msp, ''),
                        "delivery"              => $this->setDelivery($msp, ''),
                        "google_analytics"      => $this->setGoogleAnalytics(),                    
                        "plugin"                => $this->setPlugin($woocommerce),
                //      "gateway_info"          => $this->gatewayInfo,
                        "shopping_cart"         => $this->setCart(),
                        "checkout_options"      => $this->setCheckoutOptions(),
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

                if (isset($msp->error)) {
                    wc_add_notice(__('Payment error:', 'multisafepay') . ' ' . $msp->error, 'error');
                } else {
                  wp_redirect($url);
                }
                exit();
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

                    'apikey' => array(
                        'title'         => __('API Key', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('Copy the API-Key from your MultiSafepay account', 'multisafepay'),
                        'desc_tip'      => false,
                        'css'           => 'width: 300px;'
                    ),

                  'testmode' => array(
                        'title'         => __('Test-account', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __(' ', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('Use Live-account the API-Key is from your MultiSafepay LIVE-account.<br/>Use Test -account if the API-Key is from your MultiSafepay TEST-account.', 'multisafepay'),
                        'desc_tip'      => false,
                    ),

                    'enabled' => array(
                        'title'         => __('Enable Multisafepay', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __(' ', 'multisafepay'),
                        'default'       => 'no',
                        'description'   => __('Only enable if you want to select the payment method on the website from Multisafepay instead of youre own chackout page.', 'multisafepay'),
                        'desc_tip'      => false,
                    ),

                    'pmtitle' => array(
                        'title'         => __('Title', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('Optional:overwrites the title of the payment method during checkout', 'multisafepay'),
                        'desc_tip'      => false,
                        'css'           => 'width: 300px;'
                    ),

                    'description' => array(
                        'title'         => __('Gateway Description', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('This will be shown when selecting the gateway', 'multisafepay'),
                        'css'           => 'width: 300px;',
                        'desc_tip'      => false,
                    ),

              
                    'time_active' => array(
                        'title'         => __('Time an order stays active', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('Time before unfinished order is set to expired', 'multisafepay'),
                        'desc_tip'      => false,
                        'css'           => 'width: 50px;'
                    ),

                    'time_label' => array(
                        'title'         => __(' ', 'multisafepay'),
                        'type'          => 'select',
                        'css'           => 'width: 300px;',
                        'options'     => array(
                                            'days'      => __('days', 'multisafepay' ),
                                            'hours'     => __('hours', 'multisafepay' ),
                                            'seconds'   => __('seconds', 'multisafepay' ),
                                        )                        
                    ),
                    
                    'send_invoice' => array(
                        'title'         => __('Send invoice after completed transaction', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __(' ', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('The invoice will be sent when a transaction is completed', 'multisafepay'),
                        'desc_tip'      => false,
                    ),
                    
                    'send_confirmation' => array(
                        'title'         => __('Sent order confirmation', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __(' ', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('Select this to sent the order confirmation before the transaction', 'multisafepay'),
                        'desc_tip'      => false,
                    ),
                    
                    'gateways' => array(
                        'title'         => __('Coupons', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __(' ', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('This will enable the coupons available within your MultiSafepay account', 'multisafepay'),
                        'desc_tip'      => false,
                    ),
                    'enablefco' => array(
                        'title'         => __('FastCheckout', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __(' ', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('This will enable the FastCheckout button in checkout', 'multisafepay'),
                        'desc_tip'      => false,
                    ),
                    
                    'debug' => array(
                        'title'         => __('Enable debugging', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __(' ', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('When enabled (and wordpress debug is enabled it will log transactions)', 'multisafepay'),
                        'desc_tip'      => false,
                    ),

                    'notifyurl' => array(
                        'title'         => __('Notification url', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('Copy&Paste this URL to your website configuration Notification-URL at your Multisafepay dashboard.', 'multisafepay'),
                        'desc_tip'      => false,
                        'css'           => 'width: 800px;',
                    ),                    
                );
            }
				
            public function GATEWAY_Forms() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable this gateway', 'multisafepay'),
                        'type' => 'checkbox',
                        'label' => __('Enable transaction by using this gateway', 'multisafepay'),
                        'default' => 'no',
                        'description' => __('When enabled it will show on during checkout', 'multisafepay'),
                    ),
                    'pmtitle' => array(
                        'title' => __('Title', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('Optional: Overwrites the title of the payment method during checkout', 'multisafepay'),
                        'css' => 'width: 300px;'
                    ),
                    'description' => array(
                        'title' => __('Gateway Description', 'multisafepay'),
                        'type' => 'text',
                        'description' => __('Optional: This will be shown when selecting the gateway', 'multisafepay'),
                        'css' => 'width: 300px;'
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

                global $woocommerce;

                $settings = (array) get_option('woocommerce_multisafepay_settings');
                $debug = $this->getDebugMode ($settings['debug']);

                if ($debug)
                    $this->write_log('MSP->Process payment start.');
                
                $this->OptionalSendConfirmationMail($settings['send_confirmation'], $order_id);

                $order = new WC_Order($order_id);

                $msp   = new Client();

				$api  = $settings['apikey'];
				$mode = $settings['testmode'];

				$msp->setApiKey($api);
				$msp->setApiUrl($mode);

                
                if ($debug)
                    $this->write_log('MSP->Process billing name1.' . print_r ($order->billing_first_name, true));

                $this->type = isset ($this->type) ? $this->type : 'redirect';

                $my_order = 
                    array(
                        "type"        		    => $this->type,
                        "order_id"              => $order->get_order_number(),
                        "currency"              => get_woocommerce_currency(),
                        "amount"                => round($order->get_total() * 100),
                        "description"           => 'Order #' . $order->get_order_number(),
                        "var1"                  => $order->order_key,
                        "var2"                  => $order_id,
                        "var3"                  => '',
                        "items"                 => $this->itemList ($order->get_items()),
                        "manual"                => false,
                        "gateway"               => isset ($this->gateway) ? $this->gateway : '',
                        "seconds_active"        => $this->setSecondsActive($settings),
                        "payment_options"       => array(
                            "notification_url"  => $settings['notifyurl'] . '&type=initial',
                            "redirect_url"      => add_query_arg('utm_nooverride', '1', $this->get_return_url($order)),
                            "cancel_url"		=> htmlspecialchars_decode(add_query_arg('key', $order->id, $order->get_cancel_order_url())),
                            "close_window"      => true
                        ),
                        "customer"              => $this->setCustomer($msp, $order),
                        "delivery"              => $this->setDelivery($msp, $order),
                        "google_analytics"      => $this->setGoogleAnalytics(),                    
                        "plugin"                => $this->setPlugin($woocommerce),
                        
                        "gateway_info"          => isset ($this->gatewayInfo) ? $this->gatewayInfo : '',
                //      "shopping_cart"         => (isset ($this->shopping_cart)    ? $this->shopping_cart    : array()),
                //      "checkout_options"      => (isset ($this->checkout_options) ? $this->checkout_options : array()),

                    );

                if ($debug)
                    $this->write_log('MSP->transactie.' . print_r ($my_order, true));
                
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

                if (!$msp->error) {
                    return array(
                        'result'    => 'success',
                        'redirect'  => $url                        
                    );
                }else{
                    wc_add_notice(__('Payment error:', 'multisafepay') . ' ' . $msp->error . 'error');                 
                }
            }

            public function Multisafepay_Response() {
                global $wpdb, $woocommerce;

                $settings = (array) get_option('woocommerce_multisafepay_settings');
                $debug    = $this->getDebugMode ($settings['debug']);
                
                $redirect        = false;
                $initial_request = false;
                
                if (isset($_GET['transactionid'])) {

                    if (isset($_GET['type'])) {
                        if ($_GET['type'] == 'initial') {
                            $initial_request = true;
                        } elseif ($_GET['type'] == 'redirect') {
                            $redirect = true;
                        } elseif ($_GET['type'] == 'cancel') {
                            return true;
                        } else {
                            $initial_request = false;
                            $redirect        = false;
                        }
                    }

                    $transactionid = filter_input(INPUT_GET, 'transactionid', FILTER_SANITIZE_STRIPPED);

                    $msp = new Client();

                    $api  = $this->settings['apikey'];
                    $mode = $this->settings['testmode'];

                    $msp->setApiKey($api);
                    $msp->setApiUrl($mode);

                    // Get the transaction.
                    try {
                        $this->transactie = $msp->orders->get($transactionid, 'orders', array(), false);
                    } catch (Exception $e) {

                        $msg = "Unable to get transaction. Error: " . htmlspecialchars($e->getMessage());
                        echo $msg;
                        if ($debug) {
                            $this->write_log($msg);
                        }
                    }
                            
                    $updated        = false;
                    $status         = $this->transactie->status;
                    $amount         = $this->transactie->amount / 100;
                    $transactie_id  = $this->transactie->transaction_id;
                    $order_id       = $this->transactie->order_id;

                    $order          = new WC_Order($order_id);
                    $results        = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'woocommerce_multisafepay WHERE trixid = \'' . $transactie_id . '\'', OBJECT);

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
                            $wpdb->query("INSERT INTO " . $wpdb->prefix . 'woocommerce_multisafepay' . " ( trixid, orderid, status ) VALUES ( '" . $transactie_id . "', '" . $order->id . "', '" . $status . "'  )");

                            $billing_address = array();
                            $billing_address['first_name']  = $this->transactie->customer->first_name;
                            $billing_address['last_name']   = $this->transactie->customer->last_name;

                            $billing_address['address_1']   = $this->transactie->customer->address1 . ' ' . $this->transactie->customer->house_number;
                            $billing_address['address_2']   = $this->transactie->customer->address2;
                            $billing_address['city']        = $this->transactie->customer->city;
                            $billing_address['state']       = $this->transactie->customer->state;
                            $billing_address['postcode']    = $this->transactie->customer->zip_code;
                            $billing_address['country']     = $this->transactie->customer->country;
                            $billing_address['phone']       = $this->transactie->customer->phone1;
                            $billing_address['email']       = $this->transactie->customer->email;

                            $shipping_address = array();
                            $shipping_address['first_name']  = $this->transactie->delivery->first_name;
                            $shipping_address['last_name']   = $this->transactie->delivery->last_name;

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

                            $shipping = array();
                            
                            foreach ($woocommerce->shipping->load_shipping_methods() as $shipping_method) {

                                if ($shipping_method->method_title === $this->transactie->order_adjustment->shipping->flat_rate_shipping->name ) {
                                    $shipping['method_title'] = $this->transactie->order_adjustment->shipping->flat_rate_shipping->name;
                                    $shipping['total']        = $this->transactie->order_adjustment->shipping->flat_rate_shipping->cost;
                                    $rate = new WC_Shipping_Rate($shipping_method->id, 
                                                                isset($shipping['method_title']) ? $shipping['method_title']    : '', 
                                                                isset($shipping['total'])        ? floatval($shipping['total']) : 0,
                                                                array(),
                                                                $shipping_method->id);
                                }
                            }
                        
                            $order->add_shipping($rate);
                            $order->add_order_note($transactie_id);

                            $gateways = new WC_Payment_Gateways();
                            $all_gateways = $gateways->get_available_payment_gateways();

                            foreach ($all_gateways as $gateway) {
                                if ($gateway->id === "multisafepay") {
                                    $selected_gateway = $gateway;
                                    break;
                                }
                            }
                            $order->set_payment_method($selected_gateway);

                            $return_url         = $order->get_checkout_order_received_url();
                            $cancel_url         = $order->get_cancel_order_url();
                            $view_order_url     = $order->get_view_order_url();
                            $retry_payment_url  = $order->get_checkout_payment_url();

                            foreach ($this->transactie->shopping_cart->items as $product) {
                                
                                $sku = json_decode($product->merchant_item_id);
                                $applied_discount_tax = 0;
                                
                                if (!empty($sku->sku)) {
                                    $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku->sku));
                                    $product_item = new WC_Product($product_id);
                                    $product_item->qty = $product->quantity;
                                    $order->add_product($product_item, $product->quantity);
                                } elseif (!empty($sku->cartcoupon)) {
                                    $code = $sku->cartcoupon;
                                    $amount = (float) str_replace('-', '', $product->unit-price);
                                    update_post_meta($order->id, '_cart_discount', $amount);
                                    update_post_meta($order->id, '_order_total', $this->transactie->amount / 100);
                                    $tax_percentage = ( ($this->transactie->amount / 100) - ($this->transactie->order_total - $this->transactie->order_adjustment->total_tax + $this->transactie->order_adjustment->shipping->flat_rate_shipping->cost)) /
                                                        ($this->transactie->order_total - $this->transactie->order_adjustment->total_tax + $this->transactie->order_adjustment->shipping->flat_rate_shipping->cost);
                                    $applied_discount_tax = round(($amount * (1 + $tax_percentage)) - $amount, 2);
                                    update_post_meta($order->id, '_cart_discount_tax', $applied_discount_tax);
                                    $order->calculate_taxes();
                                    $order_data = get_post_meta($order->id);
                                    $new_order_tax = round($order_data['_order_tax'][0] - (($amount * (1 + $tax_percentage)) - $amount), 2);
                                    update_post_meta($order->id, '_order_tax', $new_order_tax);
                                    $id = $order->add_coupon($code, $amount, $applied_discount_tax);
                                } elseif (!empty($sku->ordercoupon)) {
                                    $code = $sku->ordercoupon;
                                    $amount = (float) str_replace('-', '', $product->unit-price);
                                    update_post_meta($order->id, '_cart_discount', $amount);
                                    update_post_meta($order->id, '_order_total', $this->transactie->amount / 100);
                                    $tax_percentage = ( ($this->transactie->amount / 100) - ($this->transactie->order_total - $this->transactie->order_adjustment->total_tax + $this->transactie->order_adjustment->shipping->flat_rate_shipping->cost)) /
                                                        ($this->transactie->order_total - $this->transactie->order_adjustment->total_tax + $this->transactie->order_adjustment->shipping->flat_rate_shipping->cost);

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
                            update_post_meta($order->id, '_order_total', $this->transactie->amount / 100);
                            $order->calculate_taxes();

                            foreach ($order->get_items('tax') as $key => $value) {
                                $data = wc_get_order_item_meta($key, 'tax_amount');
                                wc_update_order_item_meta($key, 'tax_amount', $data - $applied_discount_tax);
                            }

                            $amount = $this->transactie->amount / 100;
            
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
                                case 'reserved':
                                case 'declined':
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
                            $location = $order->get_checkout_order_received_url();
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

                                    if ($status == 'completed' && $gateway == 'KLARNA') {
                                        $order->add_order_note(__("Klarna Reservation number: ") . $this->transactie->payment_details->externaltransactionid);
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
                                case 'reserved':
                                case 'declined':
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
                                $order->add_order_note(__('Klarna Invoice: ') . '<br /><a href="https://online.klarna.com/invoices/' . $this->transactie->payment_details->type->externaltransactionid . '.pdf">https://online.klarna.com/invoices/' . $this->transactie->payment_details->type->externaltransactionid . '.pdf</a>');
                            }
                        }
                        
                        $return_url         = $order->get_checkout_order_received_url();
                        $cancel_url         = $order->get_cancel_order_url();
                        $view_order_url     = $order->get_view_order_url();
                        $retry_payment_url  = $order->get_checkout_payment_url();

                        if ($redirect) {
                            wp_redirect($return_url);
                            exit();
                        }

                        if ($initial_request) {
//                          $location = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))));
//                            $location = WC_Order::get_checkout_order_received_url();
                              $location = $order->get_checkout_order_received_url();

                            
                            echo '<a href=' . $location . '>Klik hier om terug te keren naar de website</a>';
                            //exit;
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
//                            exit;
                        }
                    }
                        
                    if ($initial_request) {
//                      $location = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))));
                        $location = WC_Order::get_checkout_order_received_url();
                        echo '<a href=' . $location . '>Klik hier om terug te keren naar de website</a>';
                        exit;
                    } else {
                        echo 'OK';
                        exit;
                    }
                }
            }

            public function getDebugMode ($setDebug = false){
                return ($setDebug == 'yes' ? true : false);
            }
            
            public function getLocale() {
                return (str_replace('-', '_', get_bloginfo('language')));
            }

            public function OptionalSendConfirmationMail($sendMail, $order_id){
                global $wp_version, $woocommerce;
                if ($sendMail == 'yes') {
                    $mailer = $woocommerce->mailer();
                    $email = $mailer->emails['WC_Email_New_Order'];
                    $email->trigger($order_id);

                    $mailer = $woocommerce->mailer();
                    $email = $mailer->emails['WC_Email_Customer_Processing_Order'];
                    $email->trigger($order_id);
                }
            }

            public function itemList ($items){
                $list = '<ul>';
                foreach ($items as $item) {
                    $list .= '<li>' . $item['qty'] . ' x ' . $item['name'] . '</li>';        
                }
                $list .= '</ul>';
                return ($list);
            }
            
            public function setDelivery ($msp, $order) {

		$address = isset ($order->shipping_address_1) ? $order->shipping_address_1 : '';
		list ($street, $houseNumber) = $msp->parseCustomerAddress($address);

                $delivery = array(
                            "locale"          => $this->getLocale(),
                            "ip_address"      => $_SERVER['REMOTE_ADDR'],
                            "forwarded_ip"    => '',
                            "referrer"        => $_SERVER['HTTP_REFERER'],
                            "user_agent"      => $_SERVER['HTTP_USER_AGENT'],
                            "first_name"      => isset($order->shipping_first_name) ? $order->shipping_first_name 	: '',
                            "last_name"       => isset($order->shipping_last_name)  ? $order->shipping_last_name 	: '',
                            "address1"        => $street,	
                            "address2"        => '',
                            "house_number"    => $houseNumber,
                            "zip_code"        => isset($order->shipping_postcode)  	? $order->shipping_postcode 	: '',
                            "city"            => isset($order->shipping_city)  		? $order->shipping_city 		: '',
                            "state"           => isset($order->shipping_state)  	? $order->shipping_state 		: '',
                            "country"         => isset($order->shipping_country)  	? $order->shipping_country 		: '',
                            "phone"           => isset($order->shipping_phone)  	? $order->shipping_phone 		: '',
                            "birthday"        => '',
                            "email"           => isset($order->shipping_email)  	? $order->shipping_email 		: '');
                return ($delivery);
            }

            public function setCustomer ($msp, $order) {

		$address = isset ($order->billing_address_1) ? $order->billing_address_1 : '';
		list ($street, $houseNumber) = $msp->parseCustomerAddress($address);

                $customer = array(
                            "locale"          => $this->getLocale(),
                            "ip_address"      => $_SERVER['REMOTE_ADDR'],
                            "forwarded_ip"    => '',
                            "referrer"        => $_SERVER['HTTP_REFERER'],
                            "user_agent"      => $_SERVER['HTTP_USER_AGENT'],
                            "first_name"      => isset($order->billing_first_name)	? $order->billing_first_name 	: '',
                            "last_name"       => isset($order->billing_last_name)	? $order->billing_last_name 	: '',
                            "address1"        => $street,	
                            "address2"        => '',
                            "house_number"    => $houseNumber,
                            "zip_code"        => isset($order->billing_postcode)	? $order->billing_postcode 		: '',
                            "city"            => isset($order->billing_city)  		? $order->billing_city 			: '',
                            "state"           => isset($order->billing_state)		? $order->billing_state 		: '',
                            "country"         => isset($order->billing_country)  	? $order->billing_country 		: '',
                            "phone"           => isset($order->billing_phone)		? $order->billing_phone 		: '',
                            "birthday"        => '',
                            "email"           => isset($order->billing_email)		? $order->billing_email 		: '');
                return ($customer);
            }
        
            public function setGoogleAnalytics () {
                
                $google_analytics = array(
                                        "account" => "UA-XXXXXXXXX",
                                     );
                return ($google_analytics);
            }
                      
            public function setPlugin ($woocommerce) {
                $plugin = array(
                            "shop"            => "WooCommerce",
                            "shop_version"    => 'WooCommerce '. $woocommerce->version,
                            "plugin_version"  => '(3.0.0)',
                            "partner"         => '',
                            "shop_root_url"   => '',
                          );
                return ($plugin);
            }

            public function setItemListFCO () {
                $itemList = '<ul>';

                foreach (WC()->cart->get_cart() as $values) {
                    $_product = $values['data'];
                
                    $name   = html_entity_decode($_product->get_title(), ENT_NOQUOTES, 'UTF-8');
                    $qty    = absint($values['quantity']);
                    $itemList .= '<li>' . $qty . ' x ' . $name . '</li>';        
                }
                $itemList .= '</ul>';

                return ( $itemList);
            }
                    
            public function setCart() {

                $shopping_cart = ARRAY();
                foreach (WC()->cart->get_cart() as $values) {

                    /*
                     * Get product data from WooCommerce
                     */
                    $_product = $values['data'];
                    
                    $qty    = absint($values['quantity']);
                    $sku    = $_product->get_sku();
                    $name   = html_entity_decode($_product->get_title(), ENT_NOQUOTES, 'UTF-8');
                    $descr  = html_entity_decode(get_post($_product)->post->post_content, ENT_NOQUOTES, 'UTF-8');

                    if ($_product->product_type == 'variation') {
                        $meta = WC()->cart->get_item_data($values, true);

                        if (empty($sku))
                            $sku = $_product->parent->get_sku();

                        if (!empty($meta))
                            $name .= " - " . str_replace(", \n", " - ", $meta);
                    }

                    $product_price       = number_format($_product->get_price_excluding_tax(), 4, '.', '');
                    $product_tax_applied = $values['line_tax'] / $qty;
                    $percentage          = $product_tax_applied / $product_price * 100;

                    $json_array = array();
                    $json_array['sku'] = $sku;

                    $shopping_cart['items'][] = array (
                        'name'  			 => $name,
                        'description' 		 => $descr,
                        'unit_price'  		 => $product_price,
                        'quantity'    		 => $qty,
                        'merchant_item_id' 	 => json_encode($json_array),
                        'tax_table_selector' => 'Tax-'. $percentage,
                        'weight' 			 => array ('unit'=> '',  'value'=> 'KG')
                    );
                }
                
                /**
                 * Add custom Woo cart fees as line items
                 */                
                foreach (WC()->cart->get_fees() as $fee) {
                    if ($fee->tax > 0)
                        $fee_tax_percentage = round($fee->tax / $fee->amount, 2);
                    else
                        $fee_tax_percentage = 0;
                        
                    $json_array = array();
                    $json_array['fee'] = $fee->name;

                    $shopping_cart['items'][] = array (
                        'name'  			 => $fee->name,
                        'description' 		 => $fee->name,
                        'unit_price'  		 => number_format($fee->amount, 2, '.', ''),
                        'quantity'    		 => 1,
                        'merchant_item_id' 	 => json_encode($json_array),
                        'tax_table_selector' => 'Tax-'. $fee_tax_percentage,
                        'weight' 			 => array ('unit'=> '',  'value'=> 'KG')
                    );
                }

                /*
                 * Get discount(s)
                 */
                if (WC()->cart->get_cart_discount_total()) {
                    $tax_percentage = 0;

                    foreach (WC()->cart->get_coupons('cart') as $code) {
                        $json_array = array();
                        $json_array['cartcoupon'] = $code; 

                        $shopping_cart['items'][] = array (
                            'name'  			 => 'Cart Discount ' . $code,
                            'description' 		 => 'Cart Discount ' . $code,
                            'unit_price'  		 => -number_format(WC()->cart->coupon_discount_amounts[$code], 2, '.', ''),
                            'quantity'    		 => 1,
                            'merchant_item_id' 	 => json_encode($json_array),
                            'tax_table_selector' => 'Tax-'. $tax_percentage,
                            'weight' 			 => array ('unit'=> '',  'value'=> 'KG')
                        );
                    }
                }

                return ($shopping_cart);
        }

            public function setCheckoutOptions(){

		$checkout_options = array ();
		$checkout_options['no_shipping_method'] = false;
                $checkout_options['tax_tables']['alternate'] = array ();
                $checkout_options['tax_tables']['default'] = array ('name' => 'Tax-21', 'rules' => array (array ('rate' => 0.21 )));

                foreach (WC()->cart->get_cart() as $values) {
                    /* Get product-tax */
                    $_product = $values['data'];
                    
                    $qty                 = absint($values['quantity']);
                    $product_price       = number_format($_product->get_price_excluding_tax(), 4, '.', '');
                    $product_tax_applied = $values['line_tax'] / $qty;
                    $percentage          = $product_tax_applied / $product_price * 100;

                    array_push($checkout_options['tax_tables']['alternate'], array ('name' => 'Tax-'. $percentage, 'rules' => array (array ('rate' => $percentage/100 ))));
                }
                
                /* Get CartFee tax */                
                foreach (WC()->cart->get_fees() as $fee) {
                    if ($fee->tax > 0)
                        $fee_tax_percentage = round($fee->tax / $fee->amount, 2);
                    else
                        $fee_tax_percentage = 0;
                        
                    array_push($checkout_options['tax_tables']['alternate'], array ('name' => 'Tax-'. $fee_tax_percentage, 'rules' => array (array ('rate' => $fee_tax_percentage/100 ))));
                }

                /*Get discount(s) tax    */
                if (WC()->cart->get_cart_discount_total()) {
                    $tax_percentage = 0;
                    array_push($checkout_options['tax_tables']['alternate'], array ('name' => 'Tax-'. $tax_percentage, 'rules' => array (array ('rate' => $tax_percentage/100 ))));
                }
                
		$chosen_methods = WC()->session->get('chosen_shipping_methods');
//                $chosen_shipping = $chosen_methods[0];
                WC()->shipping->calculate_shipping($this->get_shipping_packages());


//                $tax_shipping 	= false;
//                $shipping_taxes = array();

                foreach (WC()->shipping->packages[0]['rates'] as $rate) {
                    $checkout_options['shipping_methods']['flat_rate_shipping'][] = array(  "name"  => $rate->label,
                                                                                            "price" => number_format($rate->cost, '2', '.', ''));
                }
               
                return ($checkout_options);
            }

            public function setSecondsActive($settings){
                switch ($settings['time_label']){
                    case 'days':
                        $seconds_active = $settings['time_active']*24*60*60;
                        break;
                      case 'hours':
                        $seconds_active = $settings['time_active']*60*60;
                        break;
                    case 'seconds':
                        $seconds_active = $settings['time_active'];
                        break;
                }
                return ($seconds_active);
            }
            
            public static function MULTISAFEPAY_Add_Gateway($methods) {
                $methods[] = 'WC_MULTISAFEPAY';
                $settings = (array) get_option('woocommerce_multisafepay_settings');
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

//                  $i = 0;
                    foreach ($gateway_codes as $pm) {
                        $methods[] = "WC_MULTISAFEPAY_Paymentmethod_{$pm}";
//                      $i++;
                    }
                }
                return $methods;
            }

        }

        class WC_MULTISAFEPAY_Paymentmethod extends WC_MULTISAFEPAY {
            public function __construct() {
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

                parent::GATEWAY_Forms();

                if (isset($this->settings['description'])) {
                    if ($this->settings['description'] != '') {
                        $this->description = $this->settings['description'];
                    }
                }
                $this->description .= $output;


                $this->enabled = $this->settings['enabled'] == 'yes' ? 'yes' : 'no';

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

            public function process_payment($order_id) {

                $this->type = 'redirect';
                $this->gatewayInfo = '';
              
                $paymentMethod = explode('_', $order->payment_method);
                $this->gateway = $this->paymentMethodCode;

    
                return parent::process_payment($order_id);                
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

        // Start 
        new WC_MULTISAFEPAY();
    }

}