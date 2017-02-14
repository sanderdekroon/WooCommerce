<?php

class MultiSafepay_Gateway_Eps extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_eps";
    }

    public static function getName()
    {
        return __('EPS', 'multisafepay');
    }
		
	public static function getGatewayCode()
    {
        return "EPS";
    }

    public function getType()
    {
        return "redirect";
    }
	
}