<?php

class MultiSafepay_Gateway_Beautyandwellness extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_Beautyandwellness";
    }

    public static function getName()
    {
        return __('Beauty and wellness', 'multisafepay');
    }

	public static function getGatewayCode()
    {
        return "BEAUTYANDWELLNESS";
    }

    public function getType()
    {
        return "redirect";
    }

}