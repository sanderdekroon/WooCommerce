<?php

/*
  Plugin Name: Multisafepay Banktransfer
  Plugin URI: http://www.multisafepay.com
  Description: Multisafepay Payment Plugin
  Author: Multisafepay
  Author URI:http://www.multisafepay.com
  Version: 2.2.2

  Copyright: � 2012 Multisafepay(email : techsupport@multisafepay.com)
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


load_plugin_textdomain('multisafepay', false, dirname(plugin_basename(__FILE__)) . '/');
if (!class_exists('MultiSafepay')) {
  require(realpath(dirname(__FILE__)) . '/../multisafepay/MultiSafepay.combined.php');
}
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || is_plugin_active_for_network('woocommerce/woocommerce.php')) {
  add_action('plugins_loaded', 'WC_MULTISAFEPAY_BANKTRANSFER_Load', 0);

  function WC_MULTISAFEPAY_BANKTRANSFER_Load() {

    class WC_MULTISAFEPAY_Banktransfer extends WC_Payment_Gateway {

      public function __construct() {
        global $woocommerce;

        $this->init_settings();
        $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');

        $this->id = "MULTISAFEPAY_BANKTRANS";
        $this->has_fields = false;
        $this->paymentMethodCode = "BANKTRANS";
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
        add_filter('woocommerce_payment_gateways', array('WC_MULTISAFEPAY_Banktransfer', 'MULTISAFEPAY_BANKTRANS_Add_Gateway'));

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
          $this->title = 'BankTransfer';
          $this->method_title = 'BankTransfer';
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

      public function process_refund($order_id, $amount = null, $reason = '') {

        $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');
        if ($this->settings2['testmode'] == 'yes'):
          $mspurl = true;
        else :
          $mspurl = false;
        endif;

        $order = new WC_Order($order_id);
        $currency = $order->get_order_currency();

        $msp = new MultiSafepay();
        $msp->test = $mspurl;
        $msp->merchant['account_id'] = $this->settings2['accountid'];
        $msp->merchant['site_id'] = $this->settings2['siteid'];
        $msp->merchant['site_code'] = $this->settings2['securecode'];
        $msp->merchant['api_key'] = $this->settings2['apikey'];
        $msp->transaction['id'] = $order_id;
        $msp->transaction['currency'] = $currency;
        $msp->transaction['amount'] = $amount * 100;
        $msp->signature = sha1($this->settings2['siteid'] . $this->settings2['securecode'] . $order_id);

        $response = $msp->refundTransaction();


        if ($msp->error) {
          return new WP_Error('multisafepay_ideal', 'Order can\'t be refunded:' . $msp->error_code . ' - ' . $msp->error);
        } else {
          return true;
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
        $msp->transaction['id'] = $ordernumber; //$order_id; 
        $msp->transaction['currency'] = get_woocommerce_currency();
        $msp->transaction['amount'] = $order->get_total() * 100;
        $msp->transaction['description'] = 'Order ' . __('#', '', 'multisafepay') . $ordernumber . ' : ' . get_bloginfo();
        $msp->transaction['gateway'] = $gateway;
        $msp->plugin_name = 'WooCommerce';
        $msp->plugin['shop'] = 'WooCommerce';
        $msp->plugin['shop_version'] = $woocommerce->version;
        $msp->plugin['plugin_version'] = '2.2.2';
        $msp->plugin['partner'] = '';
        $msp->version = '(2.2.2)';
        $msp->transaction['items'] = $html;
        $msp->transaction['var1'] = $order->order_key;
        $msp->transaction['var2'] = $order_id;
        $issuerName = sprintf('%s_issuer', $paymentMethod[1]);
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

      public static function MULTISAFEPAY_BANKTRANS_Add_Gateway($methods) {
        global $woocommerce;
        $methods[] = 'WC_MULTISAFEPAY_Banktransfer';

        return $methods;
      }

    }

    // Start 
    new WC_MULTISAFEPAY_Banktransfer();
  }

}
