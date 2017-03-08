<?php

class MultiSafepay_Gateway_Banktrans extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_banktrans";
    }

    public static function getName()
    {
        return __('Banktransfer', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_banktrans_settings');
    }

    public static function getGatewayCode()
    {
        return "BANKTRANS";
    }

    public function getType()
    {
        $settings = get_option('woocommerce_multisafepay_banktrans_settings');

        if ($settings['direct'] == 'yes')
            return "direct";
        else
            return "redirect";
    }

    public function init_settings($form_fields = array())
    {
        $this->form_fields = array();

        $warning = $this->getWarning();

        if (is_array($warning)) $this->form_fields['warning'] = $warning;

        $this->form_fields['direct'] = array(
                'title'     => __('Direct', 'woocommerce'),
                'type'      => 'checkbox',
                'label'     => sprintf(__('Direct %s', 'multisafepay'), $this->getName()),
                'default'   => 'no' );

        parent::init_settings($this->form_fields);
    }
}