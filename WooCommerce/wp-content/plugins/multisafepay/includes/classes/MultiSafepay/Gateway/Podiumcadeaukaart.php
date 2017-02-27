<?php

class MultiSafepay_Gateway_Podiumcadeaukaart extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_podiumcadeaukaart";
    }

    public static function getName()
    {
        return __('Podium-Cadeaukaart', 'multisafepay');
    }

	public static function getGatewayCode()
    {
        return "PODIUM";
    }

    public function getType()
    {
        return "redirect";
    }

}