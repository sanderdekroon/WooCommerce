<?php

class MultiSafepay_Gateway_Yourgift extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_yourgift";
    }

    public static function getName()
    {
        return __('Yourgift', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "YOURGIFT";
    }

    public function getType()
    {
        return "redirect";
    }
}