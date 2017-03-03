<?php

class MultiSafepay_Gateway_GoodCard extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_goodcard";
    }

    public static function getName()
    {
        return __('GoodCard', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "GOODCARD";
    }

    public function getType()
    {
        return "redirect";
    }
}