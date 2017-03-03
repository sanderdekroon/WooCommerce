<?php

class MultiSafepay_Gateway_Erotiekbon extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_erotiekbon";
    }

    public static function getName()
    {
        return __('Erotiekbon', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "EROTIEKBON";
    }

    public function getType()
    {
        return "redirect";
    }
}