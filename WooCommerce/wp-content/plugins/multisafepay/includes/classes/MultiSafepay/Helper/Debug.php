<?
class MultiSafepay_Helper_Debug {
    
    public static function write_log($log)
    {
        if (get_option('multisafepay_debugmode') == 'yes') {
            if (is_array($log) || is_object($log)) error_log(print_r($log, true));
            else error_log($log);
        }
    }
}
