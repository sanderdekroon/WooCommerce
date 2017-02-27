<?php

class MultiSafepay_Gateway_Winkelcheque extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_winkelcheque";
    }

    public static function getName()
    {
        return __('Winkelcheque', 'multisafepay');
    }

	public static function getGatewayCode()
    {
        return "WINKELCHEQUE";
    }

    public function getType()
    {
        return "redirect";
    }

}