<?php

class MultiSafepay_Gateway_Paysafecard extends MultiSafepay_Gateway_Abstract
{
    public static function getCode()
    {
        return "multisafepay_paysafecard";
    }

    public static function getName()
    {
        return __('Paysafecard', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "PSAFECARD";
    }
    
    public function getType()
    {
        return "redirect";            
    }
}