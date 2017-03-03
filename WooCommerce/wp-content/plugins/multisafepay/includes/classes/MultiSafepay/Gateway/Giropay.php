<?php

class MultiSafepay_Gateway_Giropay extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_giropay";
    }

    public static function getName()
    {
        return __('GiroPay', 'multisafepay');
    }

    public static function getGatewayCode()
    {
        return "GIROPAY";
    }

    public function getType()
    {
        return "redirect";
    }
}