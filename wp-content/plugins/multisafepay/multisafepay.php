<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2017 MultiSafepay, Inc. (http://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */


//Autoloader laden en registreren
require_once dirname(__FILE__) . '/includes/classes/Autoload.php';
require_once dirname(__FILE__) . '/includes/classes/MultiSafepay/api/Autoload.php';


//plugin functies inladen
require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

//textdomain inladen
load_plugin_textdomain('multisafepay', false, plugin_basename(dirname(__FILE__)) . "/languages");

function error_woocommerce_not_active()
{
    echo '<div class="error"><p>' . __('To use the Multisafepay plugin it is required that woocommerce is active', 'multisafepay') . '</p></div>';
}

function error_curl_not_installed()
{
    echo '<div class="error"><p>' . __('Curl is not installed.<br />In order to use the MultiSafepay plug-in, you must install CURL.<br />Ask your system administrator to install php_curl', 'multisafepay') . '</p></div>';
}


// Curl is niet geinstalleerd. foutmelding weergeven
if (!function_exists('curl_version')) {
    add_action('admin_notices', __('error_curl_not_installed', 'multisafepay'));
}


if (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active_for_network('woocommerce/woocommerce.php')) {

    //Autoloader registreren
    MultiSafepay_Autoload::register();
    API_Autoload::register();

    //MultiSafepay gateways aan woocommerce koppelen
    MultiSafepay_Gateways::register();
} else {
    // Woocommerce is niet actief. foutmelding weergeven
    add_action('admin_notices', error_woocommerce_not_active);
}