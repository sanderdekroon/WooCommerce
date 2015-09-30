<?php

/*
  Plugin Name: Multisafepay
  Plugin URI: http://www.multisafepay.com
  Description: Multisafepay Payment Plugin
  Author: Multisafepay
  Author URI:http://www.multisafepay.com
  Version: 2.2.1

  Copyright: � 2012 Multisafepay(email : techsupport@multisafepay.com)
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


load_plugin_textdomain('multisafepay', false, dirname(plugin_basename(__FILE__)) . '/');

if (!class_exists('MultiSafepay')) {
  require(realpath(dirname(__FILE__)) . '/MultiSafepay.combined.php');
}

register_activation_hook(__FILE__, 'MULTISAFEPAY_register');

function MULTISAFEPAY_register() {
  global $wpdb, $woocommerce;
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  wp_insert_term(__('Awaiting Payment', 'multisafepay'), 'shop_order_status');
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
  add_action('plugins_loaded', 'WC_MULTISAFEPAY_Load', 0);

  function WC_MULTISAFEPAY_Load() {

    class WC_MULTISAFEPAY_Paymentmethod extends WC_Payment_Gateway {

      public function __construct() {
        global $woocommerce;
        $gateway_info = array(
            'BABYGIFTCARD' => 'Baby giftcard',
            'BOEKENBON' => 'Boekenbon',
            'E-BON' => 'E-bon',
            'EROTIEKBON' => 'Erotiekbon',
            'FIJNCADEAU' => 'Fijncadeau',
            'PARFUMCADEAUKAART' => 'Parfum cadeaukaart',
            'PARFUMNL' => 'Parfum nl',
            'WEBSHOPGIFTCARD' => 'Webshop giftcard',
            'FASHIONCHEQUE' => 'Fashion Cheque',
            'GEZONDHEIDSBON' => 'Gezondheidsbon',
            'LIEF' => 'Lief cadeaukaart',
            'DEGROTESPEELGOEDWINKEL' => 'De grote speelgoed winkel',
        );
        $gateway_codes = array(
            '0' => 'BABYGIFTCARD',
            '1' => 'BOEKENBON',
            '2' => 'E-BON',
            '3' => 'EROTIEKBON',
            '4' => 'FIJNCADEAU',
            '5' => 'PARFUMCADEAUKAART',
            '6' => 'PARFUMNL',
            '7' => 'WEBSHOPGIFTCARD',
            '8' => 'FASHIONCHEQUE',
            '9' => 'GEZONDHEIDSBON',
            '10' => 'LIEF',
            '11' => 'DEGROTESPEELGOEDWINKEL',
        );

        $this->init_settings();
        $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');
        $this->id = "MULTISAFEPAY_" . $gateway_codes[$this->pmCode];
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
        $msp->plugin['plugin_version'] = '2.2.1';
        $msp->plugin['partner'] = '';
        $msp->version = '(2.2.1)';
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

    class WC_MULTISAFEPAY_Paymentmethod_0 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 0;

    }

    class WC_MULTISAFEPAY_Paymentmethod_1 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 1;

    }

    class WC_MULTISAFEPAY_Paymentmethod_2 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 2;

    }

    class WC_MULTISAFEPAY_Paymentmethod_3 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 3;

    }

    class WC_MULTISAFEPAY_Paymentmethod_4 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 4;

    }

    class WC_MULTISAFEPAY_Paymentmethod_5 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 5;

    }

    class WC_MULTISAFEPAY_Paymentmethod_6 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 6;

    }

    class WC_MULTISAFEPAY_Paymentmethod_7 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 7;

    }

    class WC_MULTISAFEPAY_Paymentmethod_8 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 8;

    }

    class WC_MULTISAFEPAY_Paymentmethod_9 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 9;

    }

    class WC_MULTISAFEPAY_Paymentmethod_10 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 10;

    }

    class WC_MULTISAFEPAY_Paymentmethod_11 extends WC_MULTISAFEPAY_Paymentmethod {

      protected $pmCode = 11;

    }

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
        add_action('woocommerce_update_options_payment_gateways_MULTISAFEPAY', array($this, 'process_admin_options'));
        add_action('init', array($this, 'MULTISAFEPAY_Response'), 8);

        $this->id = 'MULTISAFEPAY';
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
        $msp->transaction['amount'] = WC()->cart->subtotal * 100;
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
          echo '<a class="checkout-button"  style="width:219px;float: right;clear: both;border:none;margin-bottom:5px;margin-top:5px;" href="' . add_query_arg('action', 'doFastCheckout', add_query_arg('wc-api', 'WC_MULTISAFEPAY', home_url('/'))) . '">';
          echo "<img src='" . $image . "' style='border:none;vertical-align: center;width: 219px;border-radius: 0px;box-shadow: none;padding: 0px;' border='0' alt='" . __('Pay with FastCheckout', 'multisafepay') . "'/>";
          echo "</a>";
          echo '</div>';
        }
      }

      // MSP Form
      public function MULTISAFEPAY_Form() {
        $this->form_fields = array(
            'stepone' => array(
                'title' => __('Set-up configuration', 'multisafepay'),
                'type' => 'title'
            ),
            'enabled' => array(
                'title' => __('Enable Multisafepay', 'multisafepay'),
                'type' => 'checkbox',
                'label' => __('Enable Multisafepay for processing transactions', 'multisafepay'),
                'default' => 'yes',
                'description' => __('Payments will be possible when active', 'multisafepay'),
            ),
            'notifyurl' => array(
                'title' => __('Notification url', 'multisafepay'),
                'type' => 'text',
                'description' => __('Copy-Paste this URL to your website configuration return url at your Multisafepay dashboard.', 'multisafepay'),
                'css' => 'width: 100%',
            ),
            'pmtitle' => array(
                'title' => __('Title', 'multisafepay'),
                'type' => 'text',
                'description' => __('Optional:overwrites the title of the payment method during checkout', 'multisafepay'),
                'css' => 'width: 300px;'
            ),
            'accountid' => array(
                'title' => __('Account ID', 'multisafepay'),
                'type' => 'text',
                'description' => __('Copy the Account ID from your Multisafepay account.', 'multisafepay'),
                'css' => 'width: 300px;'
            ),
            'siteid' => array(
                'title' => __('Site id', 'multisafepay'),
                'type' => 'text',
                'description' => __('Copy the Site ID from your Multisafepay account.', 'multisafepay'),
                'css' => 'width: 300px;'
            ),
            'securecode' => array(
                'title' => __('Secure code', 'multisafepay'),
                'type' => 'text',
                'description' => __('Copy the Secure code form your Multisafepay account', 'multisafepay'),
                'css' => 'width: 300px;'
            ),
            'apikey' => array(
                'title' => __('API Key', 'multisafepay'),
                'type' => 'text',
                'description' => __('Copy the API Key form your Multisafepay account', 'multisafepay'),
                'css' => 'width: 300px;'
            ),
            /* 'testing' 	=> 	array(
              'title' 		=> 	__( 'Gateway Testing', 'multisafepay' ),
              'type' 			=> 	'title',
              'description' 	=> 	'',
              ), */
            'testmode' => array(
                'title' => __('Multisafepay sandbox', 'multisafepay'),
                'type' => 'checkbox',
                'label' => __('Enable Multisafepay sandbox', 'multisafepay'),
                'default' => 'yes',
                'description' => __('Use this if you want to test transactions (You need a MultiSafepay test account for this.)', 'multisafepay'),
            ),
            'gateways' => array(
                'title' => __('Coupons', 'multisafepay'),
                'type' => 'checkbox',
                'label' => __('Enable MultiSafepay coupons', 'multisafepay'),
                'default' => 'yes',
                'description' => __('This will add the coupons available within your MultiSafepay account', 'multisafepay'),
            ),
            'enablefco' => array(
                'title' => __('FastCheckout', 'multisafepay'),
                'type' => 'checkbox',
                'label' => __('Enable FastCheckout', 'multisafepay'),
                'default' => 'yes',
                'description' => __('This will enable the FastCheckout button in checkout', 'multisafepay'),
            ),
            'description' => array(
                'title' => __('Gateway Description', 'multisafepay'),
                'type' => 'text',
                'description' => __('This will be shown when selecting the gateway', 'multisafepay'),
                'css' => 'width: 300px;'
            ),
            'send_invoice' => array(
                'title' => __('Send invoice after completed transaction', 'multisafepay'),
                'type' => 'checkbox',
                'label' => __('Select to send the invoice', 'multisafepay'),
                'default' => 'yes',
                'description' => __('The invoice will be sent when a transaction is completed', 'multisafepay'),
            ),
            'send_confirmation' => array(
                'title' => __('Sent order confirmation', 'multisafepay'),
                'type' => 'checkbox',
                'label' => __('Sent the order confirmation', 'multisafepay'),
                'default' => 'yes',
                'description' => __('Select this to sent the order confirmation before the transaction', 'multisafepay'),
            ),
            'debug' => array(
                'title' => __('Enable debugging', 'multisafepay'),
                'type' => 'checkbox',
                'label' => __('Enable debugging', 'multisafepay'),
                'default' => 'yes',
                'description' => __('When enabled (and wordpress debug is enabled it will log transactions)', 'multisafepay'),
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

        $msp = new MultiSafepay();
        $msp->test = $mspurl;
        $msp->merchant['account_id'] = $this->settings['accountid'];
        $msp->merchant['site_id'] = $this->settings['siteid'];
        $msp->merchant['site_code'] = $this->settings['securecode'];
        $msp->merchant['notification_url'] = $this->settings['notifyurl'] . '&type=initial';
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
        $msp->plugin_name = 'WooCommerce';
        $msp->plugin['shop_version'] = $woocommerce->version;
        $msp->plugin['plugin_version'] = '2.2.0';
        $msp->plugin['partner'] = '';
        $msp->version = '(2.2.0)';
        $msp->transaction['items'] = $html;
        $msp->transaction['var1'] = $order->order_key;
        $msp->transaction['var2'] = $order_id;
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

      // MSP Response
      public function Multisafepay_Response() {
        global $wpdb, $wp_version, $woocommerce;
        $redirect = false;
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
              $redirect = false;
            }
          }
          $order_number = $_GET['transactionid'];


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
          $msp->transaction['id'] = $order_number;
          $status = $msp->getStatus();
          $details = $msp->details;
          $amount = $details['transaction']['amount'] / 100;
          $orderid = $details['transaction']['id'];
          $updated = false;
          $ordernumber = $details['transaction']['var2'];

          $order = new WC_Order($ordernumber);
          $results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'woocommerce_multisafepay WHERE trixid = \'' . $msp->transaction['id'] . '\'', OBJECT);

          if (!empty($results)) {
            $order = new WC_Order($results[0]->orderid);
          }

          if ($details['ewallet']['fastcheckout'] == 'YES' && empty($results)) {
            if (empty($details['ewallet']['id'])) {
              $location = $woocommerce->cart->get_cart_url();
              wp_safe_redirect($location);
              exit();
            }

            if (!empty($details['shopping-cart'])) {
              $order = wc_create_order();
              $wpdb->query("INSERT INTO " . $wpdb->prefix . woocommerce_multisafepay . " ( trixid, orderid, status ) VALUES ( '" . $msp->transaction['id'] . "', '" . $order->id . "', '" . $status . "'  )");

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
                  $id = $order->add_coupon($code, $amount);
                  $order->set_total($amount, 'cart_discount');
                } elseif (!empty($sku->ordercoupon)) {
                  $code = $sku->ordercoupon;
                  $amount = (float) str_replace('-', '', $product['unit-price']);
                  $id = $order->add_coupon($code, $amount);
                  $order->set_total($amount, 'order_discount');
                } elseif (!empty($sku->fee)) {
                  //TODO PROCESS CART FEE
                }
              }

              $billing_address = array();
              $billing_address['address_1'] = $details['customer']['address1'] . $details['customer']['housenumber'];
              $billing_address['address_2'] = $details['customer']['address2'];
              $billing_address['city'] = $details['customer']['city'];
              $billing_address['state'] = $details['customer']['state'];
              $billing_address['postalcode'] = $details['customer']['zipcode'];
              $billing_address['country'] = $details['customer']['country'];
              $billing_address['phone'] = $details['customer']['phone1'];
              $billing_address['email'] = $details['customer']['email'];

              $shipping_address['address_1'] = $details['customer-delivery']['address1'] . $details['customer-delivery']['housenumber'];
              $shipping_address['address_2'] = $details['customer-delivery']['address2'];
              $shipping_address['city'] = $details['customer-delivery']['city'];
              $shipping_address['state'] = $details['customer-delivery']['state'];
              $shipping_address['postalcode'] = $details['customer-delivery']['zipcode'];
              $shipping_address['country'] = $details['customer-delivery']['country'];
              $shipping_address['name'] = $details['customer-delivery']['firstname'] . ' ' . $details['customer-delivery']['lastname'];

              $order->set_address($billing_address, 'billing');
              $order->set_address($shipping_address, 'shipping');

              foreach ($woocommerce->shipping->load_shipping_methods() as $shipping_method) {
                if ($shipping_method->title === $details['shipping']['name']) {
                  $shipping['method_title'] = $details['shipping']['name'];
                  $shipping['total'] = $details['shipping']['cost'];
                  $rate = new WC_Shipping_Rate($shipping->id, isset($shipping['method_title']) ? $shipping['method_title'] : '', isset($shipping['total']) ? floatval($shipping['total']) : 0, array(), $shipping->id);
                }
              }

              $order->add_shipping($rate);
              $order->add_order_note($details['ewallet']['id']);

              $gateways = new WC_Payment_Gateways();
              $all_gateways = $gateways->get_available_payment_gateways();

              foreach ($all_gateways as $gateway) {
                if ($gateway->id == "MULTISAFEPAY") {
                  $selected_gateway = $gateway;
                }
              }

              $order->set_payment_method($selected_gateway);
              $return_url = $order->get_checkout_order_received_url();
              $cancel_url = $order->get_cancel_order_url();
              $view_order_url = $order->get_view_order_url();
              $retry_payment_url = $order->get_checkout_payment_url();

              $amount = $details['transaction']['amount'] / 100;



              /* if ($order->calculate_totals() != $amount) {
                $order->update_status('wc-on-hold', sprintf(__('Validation error: Multisafepay amounts do not match (gross %s).', 'multisafepay'), $amount));
                echo 'ok';
                exit;
                } */

              $order->calculate_totals();

              switch ($status) {
                case 'cancelled':
                  $order->cancel_order();
                  $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                  $updated = true;
                  break;
                case 'initialized':
                  $order->update_status('wc-pending');
                  $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                  $updated = true;
                  break;
                case 'completed':
                  if ($order->get_total() != $amount) {
                    if ($order->status != 'processing') {
                      $order->update_status('wc-on-hold', sprintf(__('Validation error: Multisafepay amounts do not match (gross %s).', 'multisafepay'), $amount));
                      echo 'ok';
                      exit;
                    }
                  }

                  if ($order->status != 'processing' && $order->status != 'completed' && $order->status != 'wc-completed') {
                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                    //$order->update_status('wc-processing');
                    //$order->reduce_order_stock();
                    $woocommerce->cart->empty_cart();
                    $order->payment_complete();

                    $mailer = $woocommerce->mailer();
                    if ($this->settings['send_confirmation'] == 'no') {
                      $email = $mailer->emails['WC_Email_New_Order'];
                      $email->trigger($order->id);
                    }
                    $email = $mailer->emails['WC_Email_Customer_Processing_Order'];
                    $email->trigger($order->id);
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
                case 'reserved' :
                case 'declined':
                  $order->update_status('wc-failed', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                  $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                  $updated = true;
                case 'expired':
                  $order->update_status('wc-failed', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                  $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                  $updated = true;
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
                  $order->update_status('wc-pending');
                  $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                  $updated = true;
                  break;
                case 'completed':
                  if ($order->get_total() != $amount) {
                    if ($order->status != 'processing') {
                      $order->update_status('wc-on-hold', sprintf(__('Validation error: Multisafepay amounts do not match (gross %s).', 'multisafepay'), $amount));
                      echo 'ng';
                      exit;
                    }
                  }

                  if ($order->status != 'processing' && $order->status != 'completed' && $order->status != 'wc-completed') {

                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                    //$order->update_status('wc-processing');
                    $order->payment_complete();
                    //$order->reduce_order_stock();
                    $woocommerce->cart->empty_cart();
                    $mailer = $woocommerce->mailer();
                    if ($this->settings['send_confirmation'] == 'no') {
                      $email = $mailer->emails['WC_Email_New_Order'];
                      $email->trigger($order->id);
                    }
                    $email = $mailer->emails['WC_Email_Customer_Processing_Order'];
                    $email->trigger($order->id);
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
                  if ($order->get_total() == $amount) {
                    $order->update_status('wc-refunded', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                  }
                  $updated = true;
                  break;
                case 'uncleared' :
                case 'reserved' :
                case 'declined':
                  $order->update_status('wc-failed', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                  $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                  $updated = true;
                case 'expired':
                  $order->update_status('wc-failed', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                  $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                  $updated = true;
                case 'void' :
                  $order->cancel_order();
                  $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                  $updated = true;
                  break;
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
                '2' => 'E-BON',
                '3' => 'EROTIEKBON',
                '4' => 'FIJNCADEAU',
                '5' => 'PARFUMCADEAUKAART',
                '6' => 'PARFUMNL',
                '7' => 'WEBSHOPGIFTCARD',
                '8' => 'FASHIONCHEQUE',
                '9' => 'GEZONDHEIDSBON',
                '10' => 'LIEF',
                '11' => 'DEGROTESPEELGOEDWINKEL',
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
