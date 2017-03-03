<?php

class MultiSafepay_Gateway_Fashiongiftcard extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_fashiongiftcard";
    }

    public static function getName()
    {
        return __('Fashion-Giftcard', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "FASHIONGIFTCARD";
    }

    public function getType()
    {
        return "redirect";
    }
}