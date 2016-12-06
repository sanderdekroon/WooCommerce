<?php

/*
  Plugin Name: Multisafepay PayPal
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
    add_action('plugins_loaded', 'WC_MULTISAFEPAY_PAYPAL_Load', 0);

    function WC_MULTISAFEPAY_PAYPAL_Load() {

        class WC_MULTISAFEPAY_PAYPAL extends WC_MULTISAFEPAY {

            public function __construct() {
                global $woocommerce;

                $this->multisafepay_settings = (array) get_option('woocommerce_multisafepay_settings');
                $this->debug    = parent::getDebugMode ($this->multisafepay_settings['debug']);

                $this->id                   = "multisafepay_paypal";
                $this->paymentMethodCode    = "PayPal";
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
                add_filter('woocommerce_payment_gateways', array('WC_MULTISAFEPAY_PAYPAL', 'MULTISAFEPAY_PAYPAL_Add_Gateway'));

                if (file_exists(dirname(__FILE__) . '/images/' . $this->paymentMethodCode . '.png')) {
                    $this->icon = apply_filters('woocommerce_multisafepay_icon', plugins_url('images/' . $this->paymentMethodCode . '.png', __FILE__));
                } else {
                    $this->icon = '';
                }

                $this->settings = (array) get_option("woocommerce_{$this->id}_settings");

                $this->title        = !empty($this->settings['pmtitle']) ? $this->settings['pmtitle'] : $this->paymentMethodCode;
                $this->method_title = $this->title;

                parent::GATEWAY_Forms();

                $this->description = $this->settings['description'];
                $this->enabled     = $this->settings['enabled'] == 'yes' ? 'yes': 'no';
            }

            public function process_payment($order_id) {
                $this->gateway = $this->paymentMethodCode;
                return parent::process_payment($order_id);
            }

            public static function MULTISAFEPAY_PAYPAL_Add_Gateway($methods) {
                global $woocommerce;
                $methods[] = 'WC_MULTISAFEPAY_PAYPAL';

                return $methods;
            }
        }

        // Start
        new WC_MULTISAFEPAY_PAYPAL();
    }

}
