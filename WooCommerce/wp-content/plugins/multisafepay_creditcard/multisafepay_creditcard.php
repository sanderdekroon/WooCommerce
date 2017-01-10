<?php

/*
  Plugin Name: Multisafepay Bundled CreditCards
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
    add_action('plugins_loaded', 'WC_MULTISAFEPAY_CREDITCARD_Load', 0);

    function WC_MULTISAFEPAY_CREDITCARD_Load() {

        class WC_MULTISAFEPAY_CREDITCARD extends WC_MULTISAFEPAY {

            public function __construct() {

                $this->multisafepay_settings    = (array) get_option('woocommerce_multisafepay_settings');
                $this->debug                    = parent::getDebugMode ($this->multisafepay_settings['debug']);

                $this->id                       = "multisafepay_creditcard";
                $this->gateway                  = "CREDITCARDS";
                $this->paymentDescription       = "Creditcards";

                $this->has_fields               = true;
                $this->supports                 = array('refunds');

                add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
                add_action("woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options'));
                add_filter('woocommerce_payment_gateways', array('WC_MULTISAFEPAY_CREDITCARD', 'MULTISAFEPAY_CREDITCARD_Add_Gateway'));

                if (file_exists(dirname(__FILE__) . '/images/' . $this->gateway . '.png')) {
                    $this->icon = apply_filters('woocommerce_multisafepay_icon', plugins_url('images/' . $this->gateway . '.png', __FILE__));
                } else {
                    $this->icon = '';
                }

                $this->settings     = (array) get_option("woocommerce_{$this->id}_settings");
                $this->title        = !empty($this->settings['pmtitle']) ? $this->settings['pmtitle'] : $this->paymentDescription;
                $this->method_title = $this->title;

                parent::GATEWAY_Forms();

                $this->description = $this->settings['description'];
                $this->enabled     = $this->settings['enabled'] == 'yes' ? 'yes': 'no';
            }

            public function process_payment($order_id) {
                $this->gateway = $_POST['CC_issuer'];
                return parent::process_payment($order_id);
            }

            public static function MULTISAFEPAY_CREDITCARD_Add_Gateway($methods) {
                $methods[] = 'WC_MULTISAFEPAY_CREDITCARD';
                return $methods;
            }

            public function payment_fields() {

                $msp   = new Client();

                $api  = $this->multisafepay_settings['apikey'];
                $mode = $this->multisafepay_settings['testmode'];

                $msp->setApiKey($api);
                $msp->setApiUrl($mode);

                try {
                    $gateways = $msp->gateways->get();
                } catch (Exception $e) {

                    $msg = 'Error: ' . htmlspecialchars($e->getMessage());
                    echo $msg;
                }

                $output .= "<select name='CC_issuer' style='width:200px; padding: 2px; margin-left: 7px;'>";
                $output .= '<option value="">Select CreditCard</option>';

                foreach ($gateways as $gateway) {
                    switch ($gateway->id) {
                        case 'VISA':
                        case 'AMEX':
                        case 'MAESTRO':
                        case 'MASTERCARD':
                            $output .= '<option value="'. $gateway->id .'">'.$gateway->description.'</option>';
                    }
                }

                $output .= '</select>';
                echo $output;
            }

            public function validate_fields() {
                if (empty($_POST['CC_issuer'])) {
                    wc_add_notice(__('Fout: ', 'multisafepay') . ' ' . 'U heeft nog geen CreditCard geselecteerd.', 'error');
                    return false;
                }
                return true;
            }
        }

        // Start
        new WC_MULTISAFEPAY_CREDITCARD();
    }

}
