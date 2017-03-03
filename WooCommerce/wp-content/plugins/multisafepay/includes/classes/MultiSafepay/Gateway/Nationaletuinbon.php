<?php

class MultiSafepay_Gateway_Nationaletuinbon extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_nationaletuinbon";
    }

    public static function getName()
    {
        return __('Nationale-tuinbon', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "NATIONALETUINBON";
    }

    public function getType()
    {
        return "redirect";
    }
}