<?php

class MultiSafepay_Gateway_Boekenbon extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_boekenbon";
    }

    public static function getName()
    {
        return __('Boekenbon', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "BOEKENBON";
    }

    public function getType()
    {
        return "redirect";
    }
}