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
class MultiSafepay_Gateway_Einvoice extends MultiSafepay_Gateway_Abstract
{

    public function __construct()
    {
        add_action('woocommerce_order_status_completed', array($this, 'setToShipped'), 13);
        parent::__construct();
    }

    public static function getCode()
    {
        return "multisafepay_einvoice";
    }

    public static function getName()
    {
        return __('E-Invoice', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_einvoice_settings');
    }

    public static function getTitle()
    {
        $settings = self::getSettings();
        if (!isset ($settings['title']))
            $settings['title'] = '';

        return ($settings['title']);
    }

    public static function getGatewayCode()
    {
        return "EINVOICE";
    }

    public function getType()
    {
        $settings = get_option('woocommerce_multisafepay_einvoice_settings');

        if ($settings['direct'] == 'yes')
            return "direct";
        else
            return "redirect";
    }

    public function init_settings($form_fields = array())
    {
        $this->form_fields = array();

        $warning = $this->getWarning();

        if (is_array($warning))
            $this->form_fields['warning'] = $warning;

        $this->form_fields['direct'] = array(
            'title' => __('Direct', 'multisafepay'),
            'type' => 'checkbox',
            'label' => sprintf(__('Direct %s', 'multisafepay'), $this->getName()),
            'default' => 'no');


        $this->form_fields['direct'] = array('title' => __('Enable', 'multisafepay'),
            'type' => 'checkbox',
            'label' => sprintf(__('Direct %s', 'multisafepay'), $this->getName()),
            'description' => __('If enable extra credentials can be filled in checkout form, otherwise an extra form will be used.', 'multisafepay'),
            'default' => 'yes');


        $this->form_fields['minamount'] = array(
            'title' => __('Minimal order amount', 'multisafepay'),
            'type' => 'text',
            'description' => __('The minimal order amount in euro\'s  for an order to use this payment method', 'multisafepay'),
            'css' => 'width: 100px;');

        $this->form_fields['maxamount'] = array(
            'title' => __('Maximal order amount', 'multisafepay'),
            'type' => 'text',
            'description' => __('The maximal order amount in euro\'s  for an order to use this payment method', 'multisafepay'),
            'css' => 'width: 100px;');

        parent::init_settings($this->form_fields);
    }

    public function payment_fields()
    {
        $description = '';
        $settings = (array) get_option("woocommerce_multisafepay_einvoice_settings");

        if ($settings['direct'] == 'yes') {

            $description = '<p class="form-row form-row-wide  validate-required">
                                <label for="msp_birthday" class="">' . __('Birthday', 'multisafepay') .
                    '<abbr class="required" title="required">*</abbr>
                                </label>
                                <input type="text" class="input-text" name="einvoice_birthday" id="einvoice_birthday" placeholder="dd-mm-yyyy"/>
                            </p>

                            <p class="form-row form-row-wide  validate-required">
                                <label for="msp_account" class="">' . __('Account', 'multisafepay') .
                    '<abbr class="required" title="required">*</abbr>
                                </label>
                                <input type="text" class="input-text" name="einvoice_account" id="einvoice_account" placeholder=""/>
                            </p>

                            <p class="form-row form-row-wide">' . __('By confirming this order you agree with the ', 'multisafepay') .
                    '<a href="http://www.multifactor.nl/consument-betalingsvoorwaarden-2/" target="_blank">Terms and conditions of MultiFactor</a>
                            </p>';
        }
        $description_text = $this->get_option('description');
        if (!empty($description_text))
            $description .= '<p>' . $description_text . '</p>';

        echo $description;
    }

    public function validate_fields()
    {
        return true;
    }

    public static function einvoice_filter_gateways($gateways)
    {
        global $woocommerce;

        $settings = (array) get_option("woocommerce_multisafepay_einvoice_settings");

        if (!empty($settings['minamount']) && $woocommerce->cart->total < $settings['minamount'])
            unset($gateways['multisafepay_einvoice']);

        if (!empty($settings['maxamount']) && $woocommerce->cart->total > $settings['maxamount'])
            unset($gateways['multisafepay_einvoice']);

        // Compatiblity Woocommerce 2.x and 3.x
        $billingCountry  = (method_exists($woocommerce->customer,'get_billing_country'))  ? $woocommerce->customer->get_billing_country() : $woocommerce->customer->get_country();
 
        if (isset ($woocommerce->customer) && $billingCountry != 'NL')
            unset($gateways['multisafepay_einvoice']);

        return $gateways;
    }

    public function process_payment($order_id)
    {
        $this->type = $this->getType();
        $this->GatewayInfo = $this->getGatewayInfo($order_id);

        return parent::process_payment($order_id);
    }

    function setToShipped($order_id)
    {
        $msp = new Client();

        $msp->setApiKey($this->getApiKey());
        $msp->setApiUrl($this->getTestMode());

        try {
            $msg = null;
            $transactie = $msp->orders->get($order_id, 'orders', array(), false);
        } catch (Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            $this->write_log($msg);
        }

        if ($msp->error) {
            return new WP_Error('multisafepay', 'Can\'t receive transaction data to update correct information at MultiSafepay:' . $msp->error_code . ' - ' . $msp->error);
        }

        $endpoint = 'orders/' . $order_id;
        $setShipping = array("tracktrace_code" => null,
            "carrier" => null,
            "ship_date" => date('Y-m-d H:i:s'),
            "reason" => 'Shipped');

        try {
            $msg = null;
            $response = $msp->orders->patch($setShipping, $endpoint);
        } catch (Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            $this->write_log($msg);
        }

        if ($msp->error) {
            return new WP_Error('multisafepay', 'Transaction status can\'t be updated:' . $msp->error_code . ' - ' . $msp->error);
        } else {
            return true;
        }
    }

}
