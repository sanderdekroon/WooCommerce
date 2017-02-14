<?php

class MultiSafepay_Gateway_Sofort extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_sofort";
    }

    public static function getName()
    {
        return __('Sofort', 'multisafepay');
    }

	public static function getGatewayCode()
    {
        return "SOFORT";
    }
    
    public function getType()
    {
        return "redirect";            
    }
}