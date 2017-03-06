<?php
class MultiSafepay_Gateways
{
    public static function register()
    {
        add_option( 'multisafepay_version', '3.0.0', '', 'yes' );

        add_filter('woocommerce_payment_gateways'               , array(__CLASS__, '_getGateways'));
        add_filter('woocommerce_payment_gateways_settings'      , array(__CLASS__, '_addGlobalSettings'),1);

        add_action('init'                                       , array(__CLASS__, 'MultiSafepay_Response'));
        add_action('init'                                       , array(__CLASS__, 'addFCO'));
        add_action('woocommerce_api_' . strtolower(get_class()) , array(__CLASS__, 'doFastCheckout'));

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

        $woocommerce_tables = "CREATE TABLE {$wpdb->prefix}woocommerce_multisafepay
                                (   id bigint(20) NOT NULL auto_increment,
                                    trixid varchar(200) NOT NULL,
                                    orderid varchar(200) NOT NULL,
                                    status varchar(200) NOT NULL,
                                    PRIMARY KEY  (id)
                                ) $collate;";
        dbDelta($woocommerce_tables);
    }


    public static function _getGateways($arrDefault)
    {
        $paymentOptions = array(
              'MultiSafepay_Gateway_Amex'
            , 'MultiSafepay_Gateway_Bancontact'
            , 'MultiSafepay_Gateway_Banktrans'
            , 'MultiSafepay_Gateway_Creditcard'
            , 'MultiSafepay_Gateway_Dirdeb'
            , 'MultiSafepay_Gateway_Dotpay'
            , 'MultiSafepay_Gateway_Einvoice'
            , 'MultiSafepay_Gateway_Eps'
            , 'MultiSafepay_Gateway_Ferbuy'
            , 'MultiSafepay_Gateway_Giropay'
            , 'MultiSafepay_Gateway_Ideal'
            , 'MultiSafepay_Gateway_Klarna'
            , 'MultiSafepay_Gateway_Maestro'
            , 'MultiSafepay_Gateway_Mastercard'
            , 'MultiSafepay_Gateway_Payafter'
            , 'MultiSafepay_Gateway_Paypal'
            , 'MultiSafepay_Gateway_Paysafecard'
            , 'MultiSafepay_Gateway_Sofort'
            , 'MultiSafepay_Gateway_Visa');

    $giftCards = array(
              'MultiSafepay_Gateway_Babygiftcard'
            , 'MultiSafepay_Gateway_Beautyandwellness'
            , 'MultiSafepay_Gateway_Boekenbon'
            , 'MultiSafepay_Gateway_Erotiekbon'
            , 'MultiSafepay_Gateway_Fashioncheque'
            , 'MultiSafepay_Gateway_Fashiongiftcard'
            , 'MultiSafepay_Gateway_Fietsbon'
            , 'MultiSafepay_Gateway_Fijncadeau'
            , 'MultiSafepay_Gateway_Gezondheidsbon'
            , 'MultiSafepay_Gateway_Givacard'
            , 'MultiSafepay_Gateway_Goodcard'
            , 'MultiSafepay_Gateway_Liefcadeaukaart'
            , 'MultiSafepay_Gateway_Nationaletuinbon'
            , 'MultiSafepay_Gateway_Parfumcadeaukaart'
            , 'MultiSafepay_Gateway_Podiumcadeaukaart'
            , 'MultiSafepay_Gateway_Sportenfit'
            , 'MultiSafepay_Gateway_VVVBon'
            , 'MultiSafepay_Gateway_Webshopgiftcard'
            , 'MultiSafepay_Gateway_Wellnessgiftcard'
            , 'MultiSafepay_Gateway_Wijncadeau'
            , 'MultiSafepay_Gateway_Winkelcheque'
            , 'MultiSafepay_Gateway_Yourgift' );


        $giftcards_enabled = get_option("multisafepay_giftcards_enabled") == 'yes' ? true : false;
        if ($giftcards_enabled){
            $paymentOptions = array_merge($paymentOptions, $giftCards);
        }
        $paymentOptions = array_merge($arrDefault, $paymentOptions);

        return $paymentOptions;
    }

	public static function _addGlobalSettings($settings)
    {

        $updatedSettings = array();
        $addedSettings   = array();

        $addedSettings[] = array(
            'title'     => __('MultiSafepay settings', 'multisafepay'),
            'type'      => 'title',
            'desc'      => '<p>' . __('The following options are needed to make use of the MultiSafepay plug-in', 'multisafepay') . '</p>',
            'id'        => 'multisafepay_general_settings'
        );
        $addedSettings[] = array(
            'name'      => __('API key', 'multisafepay'),
            'type'      => 'text',
            'desc_tip'  => __('Copy the API-Key from your MultiSafepay account', 'multisafepay'),
            'id'        => 'multisafepay_api_key',
   			'css'       => 'min-width:350px;',

        );
        $addedSettings[] = array(
            'name'      => __('Test Mode', 'multisafepay'),
            'desc'      => sprintf(__('Activate %s', 'multisafepay'), __('Test Mode', 'multisafepay')),
            'type'      => 'checkbox',
            'default'   => 'yes',
            'desc_tip'   => __('Only enable if the API-Key is from a MultiSafepay Test-account.', 'multisafepay'),
            'id'        => 'multisafepay_testmode'
        );

        $addedSettings[] = array(
            'name'      => __('FastCheckout', 'multisafepay'),
            'desc'      =>  sprintf(__('Activate %s', 'multisafepay'), __('FastCheckout', 'multisafepay')),
            'type'      => 'checkbox',
            'default'   => 'no',
            'desc_tip'  => sprintf(__('When enabled %s will be available during checkout.', 'multisafepay'), __('FastCheckout', 'multisafepay')),
            'id'        => 'multisafepay_fco_enabled'
        );

        $addedSettings[] = array(
            'name'      => __('GiftCards', 'multisafepay'),
            'desc'      => sprintf(__('Activate %s', 'multisafepay'), __('GiftCards', 'multisafepay')),
            'type'      => 'checkbox',
            'default'   => 'no',
            'desc_tip'  => sprintf(__('When enabled %s will be available during checkout.', 'multisafepay'), __('GiftCards', 'multisafepay')),
            'id'        => 'multisafepay_giftcards_enabled'
        );

        $addedSettings[] = array(
            'name'      => __('Expire order', 'multisafepay'),
            'type'      => 'number',
            'default'   => 30,
            'desc_tip'  => __('Time before unfinished order is set to expired', 'multisafepay'),
            'id'        => 'multisafepay_time_active',
			'css'       => 'max-width:80px;',

        );
        $addedSettings[] = array(
//            'name'      => __('', 'multisafepay'),
            'type'      => 'select',
            'options'   => array(   'days'      => __('days',    'multisafepay'),
                                    'hours'     => __('hours',   'multisafepay'),
                                    'seconds'   => __('seconds', 'multisafepay')),
            'id'        => 'multisafepay_time_unit',
        );
        $addedSettings[] = array(
            'name'      => __('Images', 'multisafepay'),
            'desc'      => __('Show gateway images', 'multisafepay'),
            'type'      => 'checkbox',
            'default'   => 'yes',
            'id'        => 'multisafepay_show_images',
            'desc_tip'  => sprintf(__('%s during checkout.', 'multisafepay'), __('Show gateway images', 'multisafepay'))
        );

        $addedSettings[] = array(
            'name'      => __('Invoice', 'multisafepay'),
            'desc'      => __('Send Invoice', 'multisafepay'),
            'type'      => 'checkbox',
            'default'   => 'yes',
            'desc_tip'  => __('When enabled an invoice is send after a transaction is completed', 'multisafepay'),
            'id'        => 'multisafepay_send_invoice',
        );

        $addedSettings[] = array(
            'name'      => __('Debug', 'multisafepay'),
            'desc'      => __('Activate debug mode', 'multisafepay'),
            'type'      => 'checkbox',
            'default'   => 'no',
            'desc_tip'  => __('When enabled (and wordpress debug is enabled it will log transactions)', 'multisafepay'),
            'id'        => 'multisafepay_debugmode',
        );
        $addedSettings[] = array(
            'name'      => __('Notification-URL', 'multisafepay'),
            'type'      => 'text',
            'default'   => sprintf('%s/index.php?page=multisafepaynotify', get_option('siteurl')),
            'desc'      => __('Copy&Paste this URL to your website configuration Notification-URL at your Multisafepay dashboard.', 'multisafepay'),
            'id'        => 'multisafepay_nurl',
            'desc_tip'  => true,
   			'css'       => 'min-width:800px;',
        );


        $addedSettings[] = array(
            'type'  => 'sectionend',
            'id'    => 'multisafepay_general_settings',
        );
        foreach ($settings as $setting) {
            if (isset($setting['id']) && $setting['id'] == 'payment_gateways_options' && $setting['type'] != 'sectionend') {
                $updatedSettings = array_merge($updatedSettings, $addedSettings);
            }
            $updatedSettings[] = $setting;
        }

        return $updatedSettings;
    }

    public function validate_fields() {
        return false;
    }

    public function Multisafepay_Response() {

        global $wpdb, $wp_version, $woocommerce;

        $redirect        = false;
        $initial_request = false;

        if (isset($_GET['type'])) {
            if ($_GET['type'] == 'initial')
                $initial_request = true;

            if ($_GET['type'] == 'redirect')
                $redirect = true;

            if ($_GET['type'] == 'cancel')
                return true;

            if ($_GET['type'] == 'feeds'){
                require_once dirname(__FILE__) . '/Helper/Feeds.php';
                return true;
            }
        }

        // If no transaction-id there is nothing to process..
        if (!isset($_GET['transactionid'])) {
            return;
        }

        $transactionid = $_GET['transactionid'];

        $msp = new Client();

        $msp->setApiKey(get_option('multisafepay_api_key'));
        $msp->setApiUrl(get_option('multisafepay_testmode'));

        try {
            $transactie = $msp->orders->get($transactionid, 'orders', array(), false);
        } catch (Exception $e) {

            $msg = __('Unable to get transaction. Error: ', 'multisafepay') . htmlspecialchars($e->getMessage());
        }

        $updated        = false;
        $status         = $transactie->status;
        $amount         = $transactie->amount /100;
        $orderid        = $transactie->order_id;
        $gateway        = $transactie->payment_details->type;

        $results = $wpdb->get_results('SELECT orderid FROM ' . $wpdb->prefix . 'woocommerce_multisafepay WHERE trixid = \'' . $transactionid . '\'', OBJECT);
        if (!empty($results)) {
            $order  = new WC_Order( current($results));
        }else{
            $order  = new WC_Order($orderid);
        }

        if ($transactie->fastcheckout == 'YES' && empty($results)) {
            // No correct transaction, go back to checkout-page.
            if (empty($transactie->transaction_id)) {
                wp_safe_redirect($woocommerce->cart->get_cart_url());
                exit();
            }

            $amount = $transactie->amount / 100;

            if (!empty($transactie->shopping_cart)) {

                $order = wc_create_order();

                $wpdb->query("INSERT INTO " . $wpdb->prefix . woocommerce_multisafepay . " (trixid, orderid, status) VALUES ('" . $transactionid . "', '" . $order->id . "', '" . $status . "'  )");

                $billing_address = array();
                $billing_address['firstname']   = $transactie->customer->firstname;
                $billing_address['lastname']    = $transactie->customer->lastname;
                $billing_address['address_1']   = $transactie->customer->address1 . $transactie->customer->housenumber;
                $billing_address['address_2']   = $transactie->customer->address2;
                $billing_address['city']        = $transactie->customer->city;
                $billing_address['state']       = $transactie->customer->state;
                $billing_address['postcode']    = $transactie->customer->zipcode;
                $billing_address['country']     = $transactie->customer->country;
                $billing_address['phone']       = $transactie->customer->phone1;
                $billing_address['email']       = $transactie->customer->email;

                $shipping_address['firstname']  = $transactie->delivery->firstname;
                $shipping_address['lastname']   = $transactie->delivery->lastname;
                $shipping_address['address_1']  = $transactie->delivery->address1 . $transactie->delivery->housenumber;
                $shipping_address['address_2']  = $transactie->delivery->address2;
                $shipping_address['city']       = $transactie->delivery->city;
                $shipping_address['state']      = $transactie->delivery->state;
                $shipping_address['postcode']   = $transactie->delivery->zipcode;
                $shipping_address['country']    = $transactie->delivery->country;

                $order->set_address($billing_address,  'billing');
                $order->set_address($shipping_address, 'shipping');

                // Add shipping method
                foreach ($woocommerce->shipping->load_shipping_methods() as $shipping_method) {

                    if ($shipping_method->method_title == $transactie->order_adjustment->shipping->flat_rate_shipping->name) {
                        $shipping['method_title']   = $transactie->order_adjustment->shipping->flat_rate_shipping->name;
                        $shipping['total']          = $transactie->order_adjustment->shipping->flat_rate_shipping->cost;

                        $rate = new WC_Shipping_Rate(   $shipping_method->id,
                                                        isset($shipping['method_title']) ? $shipping['method_title'] : '',
                                                        isset($shipping['total']) ? floatval($shipping['total']) : 0,
                                                        array(),
                                                        $shipping_method->id);
                        break;
                    }
                }
                $order->add_shipping($rate);
                $order->add_order_note($transactie->transaction_id);


                // Add payment method
                $gateways = new WC_Payment_Gateways();
                $all_gateways = $gateways->get_available_payment_gateways();

                // Set default
                $selected_gateway = 'MultiSafepay';
                foreach ($all_gateways as $gateway) {
                    if ($gateway->id == strtolower ( 'multisafepay_' . $transactie->payment_details->type)) {
                        $selected_gateway = $gateway;
                        break;
                    }
                }
                $order->set_payment_method($selected_gateway);

                // Temp array needed for tax calculating coupons etc...
                $tmp_tax = array();
                foreach ($transactie->checkout_options->alternate as $tax){
                    $tmp_tax[$tax->name] = $tax->rules[0]->rate;
                }

                // TODO: Check if products are filled correctly
                foreach ($transactie->shopping_cart->items as $product) {

                    $sku = json_decode($product->merchant_item_id);

                    // Product
                    if (!empty($sku->sku)) {
                        $product_id         = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku->sku));
                        $product_item       = new WC_Product($product_id);
                        $product_item->qty  = $product->quantity;
                        $order->add_product($product_item, $product->quantity);
                    }

                    // CartCoupon
                    if (!empty($sku->{'Coupon-code'})){

                        $code   = $sku->Coupon-code;
                        $unit_price = (float) str_replace('-', '', $product->unit_price);
                        update_post_meta($order->id, '_cart_discount', $unit_price);
                        update_post_meta($order->id, '_order_total', $amount);


                        $applied_discount_tax   = 0;
                        update_post_meta($order->id, '_cart_discount_tax', 0);

                        $order->calculate_taxes();
                        $order_data = get_post_meta($order->id);
                        $new_order_tax = round($order_data['_order_tax'][0] - (($unit_price * (1 + $tax_percentage)) - $unit_price), 2);
                        update_post_meta($order->id, '_order_tax', $new_order_tax);
                        $id = $order->add_coupon($code, $unit_price, $applied_discount_tax);
                    }


/*
                    // Ordercoupon
                    if (!empty($sku->ordercoupon)) {
                        $code = $sku->ordercoupon;
                        $amount = (float) str_replace('-', '', $product['unit_price']);
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
                    }

                    // Cart Fee
                    if (!empty($sku->fee)) {
                        //TODO PROCESS CART FEE
                    }
*/
                }


                update_post_meta($order->id, '_order_total', $transactie->amount / 100);
                $order->calculate_taxes();

                foreach ($order->get_items('tax') as $key => $value) {
                    $data = wc_get_order_item_meta($key, 'tax_amount');
                    wc_update_order_item_meta($key, 'tax_amount', $data - $applied_discount_tax);
                }
            }
        }

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
                        if ($redirect) {
                            $return_url = $order->get_checkout_order_received_url();
                            wp_redirect($return_url);
                            exit;
                        }
                    }
                }

                if ($order->status != 'processing' && $order->status != 'completed' && $order->status != 'wc-completed') {
                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                    $order->payment_complete();
                    $woocommerce->cart->empty_cart();
                } else {
                    $updated = true;
                }

                if ($status == 'completed' && $gateway == 'KLARNA') {
                    $order->add_order_note(__('Klarna Reservation number: ', 'multisafepay') . $transactie->payment_details->external_transaction_id);
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
            case 'shipped' :
                $order->add_order_note(__('Klarna Invoice: ') . '<br /><a href="https://online.klarna.com/invoices/' . $transactie->payment_details->external_transaction_id . '.pdf">https://online.klarna.com/invoices/' . $transactie->payment_details->external_transaction_id . '.pdf</a>');
                break;

        }

        $return_url         = $order->get_checkout_order_received_url();
        $cancel_url         = $order->get_cancel_order_url();
        $view_order_url     = $order->get_view_order_url();
        $retry_payment_url  = $order->get_checkout_payment_url();


        if ($redirect) {
            wp_redirect($return_url);
            exit;
        }

        if ($initial_request) {
            $location = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))));
            echo '<a href=' . $location . '>' . __('Return to website', 'multisafepay') . '</a>';
            exit;
        } else {
            header("Content-type: text/plain");
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
            exit;
        }
    }


    public function addFCO() {
        global $woocommerce;

        if (!empty($woocommerce->fco_added))
            return;

        if (get_option('multisafepay_fco_enabled') == "yes") {
            $woocommerce->fco_added = true;
            add_action('woocommerce_proceed_to_checkout',         array(__CLASS__, 'getButtonFCO'), 12);
            add_action('woocommerce_review_order_after_submit',   array(__CLASS__, 'getButtonFCO'), 12);
        }
    }

    public function getButtonFCO() {

        if (get_woocommerce_currency() != 'EUR')        
            return;

//        $button_locale_code = get_locale();
//        $image = plugins_url('/Images/' . $button_locale_code . '/button.png', __FILE__);
        $image = plugins_url('/Images/button.png', __FILE__);

        echo '<div id="msp_fastcheckout" >';
        echo '<a class="checkout-button"  style="width:219px;border:none;margin-bottom:15px;" href="' . add_query_arg('action', 'doFastCheckout', add_query_arg('wc-api', 'MultiSafepay_Gateways', home_url('/'))) . '">';
        echo "<img src='" . $image . "' style='border:none;vertical-align: center;width: 219px;border-radius: 0px;box-shadow: none;padding: 0px;' border='0' alt='" . __('Pay with FastCheckout', 'multisafepay') . "'/>";
        echo "</a>";
        echo '</div>';
    }

    public function doFastCheckout() {

        global $woocommerce;
        $fco = new MultiSafepay_Gateways();
        $msp = new Client();

        $msp->setApiKey(Multisafepay_Gateway_Abstract::getApiKey());
        $msp->setApiUrl(Multisafepay_Gateway_Abstract::getTestMode());



        $order_id = uniqid();

        $my_order =
            array(
                "type"        		    => 'checkout',
                "order_id"              => $order_id,
                "currency"              => get_woocommerce_currency(),
                "amount"                => round(WC()->cart->subtotal * 100),
                "description"           => 'Order #' . $order_id,
                "items"                 => Multisafepay_Gateway_Abstract::setItemList ($fco->setItemsFCO()),
                "manual"                => false,
                "seconds_active"        => Multisafepay_Gateway_Abstract::getTimeActive(),
                "payment_options"       => array(
                    "notification_url"  => Multisafepay_Gateway_Abstract::getNurl() . '&type=initial',
                    "redirect_url"      => Multisafepay_Gateway_Abstract::getNurl() . '&type=redirect',
                    "cancel_url"		=> WC()->cart->get_cart_url() . 'index.php?type=cancel&cancel_order=true',
                    "close_window"      => true
                ),
                "google_analytics"      => Multisafepay_Gateway_Abstract::setGoogleAnalytics(),
                "plugin"                => Multisafepay_Gateway_Abstract::setPlugin($woocommerce),
                "gateway_info"          => '',
                "shopping_cart"         => $fco->setCartFCO(),
                "checkout_options"      => $fco->setCheckoutOptionsFCO(),
         );

        try {
            $msp->orders->post($my_order);
            $url = $msp->orders->getPaymentLink();
        } catch (Exception $e) {

            $msg = 'Error: ' . htmlspecialchars($e->getMessage());
            Multisafepay_Gateway_Abstract::write_log($msg);


        }

        Multisafepay_Gateway_Abstract::write_log('MSP->transactiondata');
        Multisafepay_Gateway_Abstract::write_log($msp);
        Multisafepay_Gateway_Abstract::write_log('MSP->transaction URL');
        Multisafepay_Gateway_Abstract::write_log($url);
        Multisafepay_Gateway_Abstract::write_log('MSP->End debug');
        Multisafepay_Gateway_Abstract::write_log('--------------------------------------');

        if (isset($msp->error)) {
            wc_add_notice(__('Payment error:', 'multisafepay') . ' ' . $msp->error, 'error');
        } else {
          wp_redirect($url);
        }
        exit();

    }

    public function setItemsFCO () {
        $items = array();
        foreach (WC()->cart->get_cart() as $values) {
            $items[] = array ( 'name' => $values['data']->get_title(), 'qty' => $values['quantity'] );
        }
        return ($items);
    }

    public function setCartFCO() {

        $shopping_cart = array();
        foreach (WC()->cart->get_cart() as $values) {

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

            $product_price       = $values['line_subtotal'] / $qty;
            $percentage          = round ($values['line_subtotal_tax'] /$values['line_subtotal'] ,2);

            $json_array = array();
            $json_array['sku'] = $sku;

            $shopping_cart['items'][] = array (
                'name'  			 => $name,
                'description' 		 => $descr,
                'unit_price'  		 => $product_price,
                'quantity'    		 => $qty,
                'merchant_item_id' 	 => json_encode($json_array),
                'tax_table_selector' => 'Tax-'. $percentage,
                'weight' 			 => array ('unit'=> '0',  'value'=> 'KG')
            );
        }


        // Add custom Woo cart fees as line items
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

        // Get discount(s)
        foreach (WC()->cart->applied_coupons as $code) {

            $unit_price     = WC()->cart->coupon_discount_amounts[$code];
            $unit_price_tax = WC()->cart->coupon_discount_tax_amounts[$code];
            $percentage     = round ($unit_price_tax/$unit_price ,2);

            $json_array = array();
            $json_array['Coupon-code'] = $code;

            $shopping_cart['items'][] = array (
                'name'  			 => 'Discount Code: ' . $code,
                'description' 		 => '',
                'unit_price'  		 => -round ($unit_price, 5),
                'quantity'    		 => 1,
                'merchant_item_id' 	 => json_encode($json_array),
                'tax_table_selector' => 'Tax-'. ($percentage*100),
                'weight' 			 => array ('unit'=> '',  'value'=> 'KG')
            );
        }

        return ($shopping_cart);
    }

    private function setCheckoutOptionsFCO(){

        $checkout_options = array ();
        $checkout_options['no_shipping_method']         = false;
        $checkout_options['tax_tables']['alternate']    = array ();
        $checkout_options['tax_tables']['default']      = array ('shipping_taxed'=> 'true', 'rate' => '0.21');

        foreach (WC()->cart->get_cart() as $values) {
            $percentage = round ($values['line_subtotal_tax'] /$values['line_subtotal'] ,2);
            array_push($checkout_options['tax_tables']['alternate'], array ('name' => 'Tax-'. $percentage, 'rules' => array (array ('rate' => $percentage ))));
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
            array_push($checkout_options['tax_tables']['alternate'], array ('name' => 'Tax-0', 'rules' => array (array ('rate' => '0.00' ))));
        }


        WC()->shipping->calculate_shipping($this->get_shipping_packagesFCO());
        foreach (WC()->shipping->packages[0]['rates'] as $rate) {
            $checkout_options['shipping_methods']['flat_rate_shipping'][] = array(  "name"  => $rate->label,
                                                                                    "price" => number_format($rate->cost, '2', '.', ''));
        }

        return ($checkout_options);
    }

    private function get_shipping_packagesFCO() {
        // Packages array for storing 'carts'
        $packages = array();
        $packages[0]['contents']                = WC()->cart->cart_contents;            // Items in the package
        $packages[0]['contents_cost']           = 0;                                    // Cost of items in the package, set below
        $packages[0]['applied_coupons']         = WC()->session->applied_coupon;
        $packages[0]['destination']['country']  = WC()->customer->get_shipping_country();
        $packages[0]['destination']['state']    = WC()->customer->get_shipping_state();
        $packages[0]['destination']['postcode'] = WC()->customer->get_shipping_postcode();
        $packages[0]['destination']['city']     = WC()->customer->get_shipping_city();
        $packages[0]['destination']['address']  = WC()->customer->get_shipping_address();
        $packages[0]['destination']['address_2']= WC()->customer->get_shipping_address_2();

        foreach (WC()->cart->get_cart() as $item)
            if ($item['data']->needs_shipping())
                if (isset($item['line_total']))
                    $packages[0]['contents_cost'] += $item['line_total'];

        return apply_filters('woocommerce_cart_shipping_packages', $packages);
    }
}
