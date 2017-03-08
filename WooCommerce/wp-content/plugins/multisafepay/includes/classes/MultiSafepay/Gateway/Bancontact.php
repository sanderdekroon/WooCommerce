<?php

class MultiSafepay_Gateway_Bancontact extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_bancontact";
    }

    public static function getName()
    {
        return __('Bancontact', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_bancontact_settings');
    }

    public static function getGatewayCode()
    {
        return "BANCONTACT";
    }

    public function getType()
    {
        return "redirect";
    }
}