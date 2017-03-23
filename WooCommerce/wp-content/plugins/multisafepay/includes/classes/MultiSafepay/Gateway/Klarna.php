<?php

class MultiSafepay_Gateway_Klarna extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_klarna";
    }

    public static function getName()
    {
        return __('Klarna', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_klarna_settings');
    }

    public static function getGatewayCode()
    {
        return "KLARNA";
    }

    public function getType()
    {
        $settings = get_option('woocommerce_multisafepay_klarna_settings');

        if ($settings['direct'] == 'yes')
            return "direct";
        else
            return "redirect";
    }

    public function init_settings($form_fields = array())
    {
        $this->form_fields = array();

        $warning = $this->getWarning();

        if (is_array($warning))
            $this->form_fields['warning'] = $warning;

        $this->form_fields['direct'] = array(
            'title'         => __('Direct', 'multisafepay'),
            'type'          => 'checkbox',
            'label'         => sprintf(__('Direct %s', 'multisafepay'), $this->getName()),
            'default'       => 'no');

        $this->form_fields['eid'] = array(
            'title'         => __('Merchant-ID', 'multisafepay'),
            'type'          => 'text',
            'description'   => __('The EID from Klarna (Merchant-ID)', 'multisafepay'),
            'default'       => 'no');

        $this->form_fields['minamount'] = array(
            'title'         => __('Minimal order amount', 'multisafepay'),
            'type'          => 'text',
            'description'   => __('The minimal order amount in euro\'s  for an order to use this payment method', 'multisafepay'),
            'css'           => 'width: 100px;');

        $this->form_fields['maxamount'] = array(
            'title'         => __('Maximal order amount', 'multisafepay'),
            'type'          => 'text',
            'description'   => __('The maximal order amount in euro\'s  for an order to use this payment method', 'multisafepay'),
            'css'           => 'width: 100px;');

        parent::init_settings($this->form_fields);
    }

    public function payment_fields()
    {

        $settings = (array) get_option("woocommerce_multisafepay_klarna_settings");
        $klarna_eid = $settings['eid'] ? $settings['eid'] : 1;

        $description =
        '<p class="form-row form-row-wide  validate-required">
            <label for="msp_birthday" class="">'.__('Birthday', 'multisafepay').
                '<abbr class="required" title="required">*</abbr>
            </label>
            <input type="text" class="input-text" name="msp_birthday" id="msp_birthday" placeholder="dd-mm-yyyy"/>
        </p><div class="clear"></div>';

        $description .=
        '<p class="form-row form-row-wide  validate-required">
            <label for="msp_gender" class="">'.__('Gender', 'multisafepay').
                '<abbr class="required" title="required">*</abbr>
            </label> '.
            '<input style="display:inline !important"  type="radio" name="msp_gender" id="msp_gender" value="male"/> '  .__("Male", "multisafepay").'<br/>'.
            '<input style="display:inline !important"  type="radio" name="msp_gender" id="msp_gender" value="female"/> '.__("Female", "multisafepay").'<br/>'.
        '</p><div class="clear"></div>';

        $description .= '<p class="form-row form-row-wide">'.__('By submitting this form I hereby agree with the Terms and conditions for Klarna ', 'multisafepay');
        $description .= sprintf(   '<p><script src="https://cdn.klarna.com/public/kitt/core/v1.0/js/klarna.min.js"></script>
                                    <script src="https://cdn.klarna.com/public/kitt/toc/v1.1/js/klarna.terms.min.js"></script>
                                    <script type="text/javascript">
                                        new Klarna.Terms.Account({  el: "MSP_Klarna",
                                                                    eid: "%s",
                                                                    locale: "%s",
                                                                    })
                                    </script></p>
                                    <span id="MSP_Klarna"></span>', $klarna_eid, get_locale());


        $description_text = $this->get_option('description');

        if (!empty($description_text)) $description .= '<p>'.$description_text.'</p>';

        echo $description;
    }

    public function validate_fields()
    {
        return true;
    }

    public static function klarna_filter_gateways($gateways)
    {

        global $woocommerce;

        $settings = (array) get_option("woocommerce_multisafepay_klarna_settings");

        if ( !empty($settings['minamount']) && $woocommerce->cart->total < $settings['minamount'])
            unset($gateways['multisafepay_klarna']);

        if ( !empty($settings['maxamount']) && $woocommerce->cart->total > $settings['maxamount'])
            unset($gateways['multisafepay_klarna']);



        return $gateways;
    }

    public function process_payment($order_id)
    {
        $this->type        = $this->getType();
        $this->GatewayInfo = $this->getGatewayInfo($order_id);
        list ($this->shopping_cart, $this->checkout_options) = $this->getCart($order_id);

        return parent::process_payment($order_id);
    }

    function setToShipped($order_id)
    {
        $msp = new Client();

        $msp->setApiKey($this->getApiKey());
        $msp->setApiUrl($this->getTestMode());

        $order = new WC_Order($order_id);

        try {
            $msg = null;
            $transactie = $msp->orders->get($order_id, 'orders', array(), false);
        } catch (Exception $e) {

            $msg = htmlspecialchars($e->getMessage());
            $this->write_log($msg);
        }

        if ($msp->error) {
            return new WP_Error('multisafepay',
                'Can\'t receive transaction data to update correct information at MultiSafepay:'.$msp->error_code.' - '.$msp->error);
        }

        $ext_trns_id = $transactie->payment_details->externaltransactionid;

        $endpoint    = 'orders/'.$order_id;
        $setShipping = array("tracktrace_code" => null,
            "carrier" => null,
            "ship_date" => date('Y-m-d H:i:s'),
            "reason" => 'Shipped');

        try {
            $msg = null;
            $response = $msp->orders->patch($setShipping, $endpoint);
        } catch (Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            $this->write_log($msg);
        }

        if ($msp->error) {
            return new WP_Error('multisafepay', 'Transaction status can\'t be updated:'.$msp->error_code.' - '.$msp->error);
        } else {
            $order->add_order_note(__('Klarna Invoice: ').'<br /><a href="https://online.klarna.com/invoices/'.$ext_trns_id.'.pdf">https://online.klarna.com/invoices/'.$ext_trns_id.'.pdf</a>');
            echo '<div class="updated"><p>Transaction updated to status shipped.</p></div>';
            return true;
        }
    }
}
?>
