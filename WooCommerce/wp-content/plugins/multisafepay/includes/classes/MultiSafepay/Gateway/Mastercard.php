<?php

class MultiSafepay_Gateway_Mastercard extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_mastercard";
    }

    public static function getName()
    {
        return __('Mastercard', 'multisafepay');
    }
		
	public static function getGatewayCode()
    {
        return "MASTERCARD";
    }

    public function getType()
    {
        return "redirect";
    }
	
}