<?php

class MultiSafepay_Gateway_Fijncadeau extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_fijncadeau";
    }

    public static function getName()
    {
        return __('FijnCadeau', 'multisafepay');
    }

	public static function getGatewayCode()
    {
        return "FIJNCADEAU";
    }

    public function getType()
    {
        return "redirect";
    }

}