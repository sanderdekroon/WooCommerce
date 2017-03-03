<?php

class MultiSafepay_Gateway_Babygiftcard extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_babygiftcard";
    }

    public static function getName()
    {
        return __('BABYGIFTCARD', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "Baby-Giftcard";
    }

    public function getType()
    {
        return "redirect";
    }
}