<?php

class MultiSafepay_Gateway_Visa extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_visa";
    }

    public static function getName()
    {
        return __('Visa', 'multisafepay');
        
    }

	public static function getGatewayCode()
    {
        return "VISA";
    }
    
    public function getType()
    {
        return "redirect";            
    }
}