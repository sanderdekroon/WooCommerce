<?php

class MultiSafepay_Gateway_Dirdeb extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_dirdeb";
    }

    public static function getName()
    {
        return __('DirectDebit', 'multisafepay');
    }
		
	public static function getGatewayCode()
    {
        return "DIRDEB";
    }

    public function getType()
    {
        return "redirect";
    }
	
}