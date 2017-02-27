<?php

class MultiSafepay_Gateway_Parfumcadeaukaart extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_Parfumcadeaukaart";
    }

    public static function getName()
    {
        return __('Parfum-cadeaukaart', 'multisafepay');
    }

	public static function getGatewayCode()
    {
        return "PARFUMCADEAUKAART";
    }

    public function getType()
    {
        return "redirect";
    }

}