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

if (!function_exists('is_plugin_active_for_network'))
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    add_action('plugins_loaded', 'WC_MULTISAFEPAY_IDEAL_Load', 0);

    function WC_MULTISAFEPAY_IDEAL_Load() {

        class WC_MULTISAFEPAY_IDEAL extends WC_MULTISAFEPAY {

            public function __construct() {
                global $woocommerce;

                $this->multisafepay_settings = (array) get_option('woocommerce_multisafepay_settings');
                $this->debug    = parent::getDebugMode ($this->multisafepay_settings['debug']);

//                $this->init_settings();
//                $this->settings2 = (array) get_option('woocommerce_multisafepay_settings');

                $this->id                   = "multisafepay_ideal";
                $this->paymentMethodCode    = "IDEAL";
                $this->has_fields           = true;
                $this->supports             = array(
                                                'refunds',
                                                /* 'subscriptions',
                                                  'products',
                                                  'subscription_cancellation',
                                                  'subscription_reactivation',
                                                  'subscription_suspension',
                                                  'subscription_amount_changes',
                                                  'subscription_payment_method_change',
                                                  'subscription_date_changes',
                                                  'default_credit_card_form',
                                                  'pre-orders' */
                                                );

                add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
                add_action("woocommerce_update_options_payment_gateways_multisafepay_ideal", array($this, 'process_admin_options'));
                add_filter('woocommerce_payment_gateways', array('WC_MULTISAFEPAY_IDEAL', 'MULTISAFEPAY_IDEAL_Add_Gateway'));

                if (file_exists(dirname(__FILE__) . '/images/' . $this->paymentMethodCode . '.png')) {
                    $this->icon = apply_filters('woocommerce_multisafepay_icon', plugins_url('images/' . $this->paymentMethodCode . '.png', __FILE__));
                } else {
                    $this->icon = '';
                }

                $this->settings = (array) get_option("woocommerce_{$this->id}_settings");

                if (!empty($this->settings['pmtitle'])) {
                    $this->title        = $this->settings['pmtitle'];
                    $this->method_title = $this->settings['pmtitle'];
                } else {
                    $this->title        = $this->paymentMethodCode;
                    $this->method_title = $this->paymentMethodCode;
                }

                $this->IDEAL_Forms();

                if (isset($this->settings['description'])) {
                    if ($this->settings['description'] != '') {
                        $this->description = $this->settings['description'];
                    }
                }

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

                $msp   = new Client();

                $api  = $this->multisafepay_settings['apikey'];
                $mode = $this->multisafepay_settings['testmode'];

                $msp->setApiKey($api);
                $msp->setApiUrl($mode);

                try {
                    $issuers = $msp->issuers->get();
                } catch (Exception $e) {

                    $msg = 'Error: ' . htmlspecialchars($e->getMessage());
                    echo $msg;
                }

                $output  = "<select name='IDEAL_issuer' style='width:164px; padding: 2px; margin-left: 7px;'>";
                $output .= '<option value="">Kies uw bank</option>';

                foreach ($issuers as $issuer) {
                    $output .= '<option value="'.$issuer->code.'">'.$issuer->description.'</option>';
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
                        'title'         => __('Gateway Setup', 'multisafepay'),
                        'type'          => 'title'
                    ),
                    'enabled' => array(
                        'title'         => __('Enable this gateway', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __('Enable transaction by using this gateway', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('When enabled it will show on during checkout', 'multisafepay'),
                    ),
                    'pmtitle' => array(
                        'title'         => __('Title', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('Optional:overwrites the title of the payment method during checkout', 'multisafepay'),
                        'css'           => 'width: 300px;'
                    ),

                    /*'issuers' => array(
                        'title'         => __('Enable iDEAL issuers', 'multisafepay'),
                        'type'          => 'checkbox',
                        'label'         => __('Enable bank selection on website', 'multisafepay'),
                        'default'       => 'yes',
                        'description'   => __('Enable of disable the selection of the preferred bank within the website.', 'multisafepay'),
                    ),*/
                    'description' => array(
                        'title'         => __('Gateway Description', 'multisafepay'),
                        'type'          => 'text',
                        'description'   => __('This will be shown when selecting the gateway', 'multisafepay'),
                        'css'           => 'width: 300px;'
                    ),
                );
            }

            public function process_payment($order_id) {

                if (isset($_POST['IDEAL_issuer'])) {
                    $this->type = 'direct';
                    $this->gatewayInfo = array( "issuer_id" => $_POST['IDEAL_issuer']);
                } else {
                    $this->type = 'redirect';
                    $this->gatewayInfo = '';
                }

                $this->gateway = 'IDEAL';

                return parent::process_payment($order_id);                
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
