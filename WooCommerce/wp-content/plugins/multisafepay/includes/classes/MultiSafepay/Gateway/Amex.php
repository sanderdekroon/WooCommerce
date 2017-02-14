<?php

class MultiSafepay_Gateway_Amex extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_amex";
    }

    public static function getName()
    {
        return __('Amex', 'multisafepay');
    }
		
	public static function getGatewayCode()
    {
        return "AMEX";
    }

    public function getType()
    {
        return "redirect";
    }
	
}