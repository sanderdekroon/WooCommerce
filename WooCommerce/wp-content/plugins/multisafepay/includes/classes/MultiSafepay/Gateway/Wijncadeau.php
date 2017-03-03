<?php

class MultiSafepay_Gateway_Wijncadeau extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_wijncadeau";
    }

    public static function getName()
    {
        return __('WijnCadeau', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "WIJNCADEAU";
    }

    public function getType()
    {
        return "redirect";
    }
}