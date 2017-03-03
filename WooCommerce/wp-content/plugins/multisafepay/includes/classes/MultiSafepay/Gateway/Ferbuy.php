<?php

class MultiSafepay_Gateway_Ferbuy extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_ferbuy";
    }

    public static function getName()
    {
        return __('FerBuy', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "FERBUY";
    }

    public function getType()
    {
        return "redirect";
    }
}