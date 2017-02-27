<?php

class MultiSafepay_Gateway_Nationaleverwencadeaubon extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_nationaleverwencadeaubon";
    }

    public static function getName()
    {
        return __('Nationale-Verwencadeaubon', 'multisafepay');
    }

	public static function getGatewayCode()
    {
        return "NATIONALEVERWENCADEAUBON";
    }

    public function getType()
    {
        return "redirect";
    }

}