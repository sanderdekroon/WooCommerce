<?php

class MultiSafepay_Gateway_Maestro extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_maestro";
    }

    public static function getName()
    {
        return __('Maestro', 'multisafepay');
    }
		
	public static function getGatewayCode()
    {
        return "MAESTRO";
    }

    public function getType()
    {
        return "redirect";
    }
	
}