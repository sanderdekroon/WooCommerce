<?php

class MultiSafepay_Gateway_Fashioncheque extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_fashioncheque";
    }

    public static function getName()
    {
        return __('Fashion-Cheque', 'multisafepay');
    }

	public static function getGatewayCode()
    {
        return "FASHIONCHEQUE";
    }

    public function getType()
    {
        return "redirect";
    }

}