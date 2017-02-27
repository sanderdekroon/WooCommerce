<?php

class MultiSafepay_Gateway_Bodybuildkleding extends MultiSafepay_Gateway_Abstract
{
	public static function getCode()
    {
        return "multisafepay_Bodybuildkleding";
    }

    public static function getName()
    {
        return __('Bodybuildkleding', 'multisafepay');
    }

	public static function getGatewayCode()
    {
        return "BODYBUILDINGKLEDING";
    }

    public function getType()
    {
        return "redirect";
    }

}