<?
class MultiSafepay_Helper_Helper {
    
    public static function write_log($log)
    {
        if (get_option('multisafepay_debugmode') == 'yes') {
            if (is_array($log) || is_object($log)) error_log(print_r($log, true));
            else error_log($log);
        }
    }
    
    public static function getApiKey()
    {
        return get_option('multisafepay_api_key');
    }

    public static function getTestMode()
    {
        return (get_option('multisafepay_testmode') == 'yes' ? true : false);
    }    
}
