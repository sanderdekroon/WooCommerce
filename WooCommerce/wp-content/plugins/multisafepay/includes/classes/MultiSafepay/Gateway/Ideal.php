<?php

class MultiSafepay_Gateway_Ideal extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_ideal";
    }

    public static function getName()
    {
        return __('iDEAL', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "IDEAL";
    }

    public function getType()
    {
        $settings = get_option('woocommerce_multisafepay_ideal_settings');

        if ($settings['direct'] == 'yes' && isset($_POST['ideal_issuer']))
            return "direct";
        else
            return "redirect";
    }

    public function getGatewayInfo($order_id)
    {
        if (isset($_POST['ideal_issuer']))
            return (array("issuer_id" => $_POST['ideal_issuer']));
        else
            return ('');
    }

    public function init_settings($form_fields = array())
    {
        $this->form_fields = array();

        $warning = $this->getWarning();

        if (is_array($warning))
            $this->form_fields['warning'] = $warning;

        $this->form_fields['direct'] = array(   'title'     => __('Direct', 'multisafepay'),
                                                'type'      => 'checkbox',
                                                'label'     => sprintf(__('Direct %s', 'multisafepay'), $this->getName()),
                                                'default'   => 'no' );

        parent::init_settings($this->form_fields);
    }

    public function payment_fields()
    {
        $description = '';

        $description_text = $this->get_option('description');
        if (!empty($description_text))
            $description .= '<p>'.$description_text.'</p>';

        $settings = get_option('woocommerce_multisafepay_ideal_settings');

        if ($settings['direct'] == 'yes') {
            $msp = new Client();

            $msp->setApiKey($this->getApiKey());
            $msp->setApiUrl($this->getTestMode());

            try {
                $issuers = $msp->issuers->get();
            } catch (Exception $e) {

                $msg = 'Error: '.htmlspecialchars($e->getMessage());
                echo $msg;
            }

            $description .= __('Choose your bank', 'multisafepay').'<br/>';
            $description .= '<select id="ideal_issuer" name="ideal_issuer" class="required-entry">';
            $description .= '<option value="">'.__('Please choose...', 'multisafepay').'</option>';
            foreach ($issuers as $issuer) {
                $description .= '<option value="'.$issuer->code.'">'.$issuer->description.'</option>';
            }
            $description .= '</select>';
            $description .= '</p>';
        }

        echo $description;
    }

    public function validate_fields()
    {
        $settings = get_option('woocommerce_multisafepay_ideal_settings');

        if ($settings['direct'] == 'yes' && empty($_POST['ideal_issuer'])) {
            wc_add_notice(__('Error: ', 'multisafepay').' '.__('Please select an issuer.'), 'error');
            return false;
        }
        return true;
    }

    public function process_payment($order_id)
    {
        $this->type        = $this->getType();
        $this->GatewayInfo = $this->getGatewayInfo($order_id);

        return parent::process_payment($order_id);
    }
}