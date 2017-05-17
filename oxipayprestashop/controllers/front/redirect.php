<?php
/**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2017 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once(dirname(__FILE__).'/../../common/OxipayCommon.php');

class OxipayprestashopRedirectModuleFrontController extends ModuleFrontController
{
    /**
     * Do whatever you have to before redirecting the customer on the website of your payment processor.
     */
    public function postProcess()
    {

        /**
         * Oops, an error occured.
         */
        // if (Tools::getValue('action') == 'error') {
        //     return $this->displayError('An error occurred while trying to redirect the customer');
        // } else {...

        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $address_billing = new Address($cart->id_address_invoice);	
        $address_shipping = new Address($cart->id_address_delivery);
        $country_billing = new Country($address_shipping->id_country);
        $country_shipping = new Country($address_shipping->id_country);
        // $customerPhone = $address_billing->$phone_mobile?$address_billing->$phone_mobile:($address_billing->$phone?$address_billing->$phone:'');
        $query = array(
            'x_currency' => $this->context->currency->iso_code,
            'x_url_callback' => $this->context->link->getModuleLink('oxipayprestashop','confirmation'),
            'x_url_complete' => $this->context->link->getModuleLink('oxipayprestashop','confirmation'),
            'x_url_cancel' => $this->context->link->getPageLink('order', true, NULL, "step=3"),
            'x_shop_name' => Configuration::get('PS_SHOP_NAME'),
            'x_shop_country' => $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT')),
            'x_account_id' => Configuration::get('OXIPAY_MERCHANT_ID'),
            'x_reference' => "{$cart->id}-{$customer->secure_key}",
            'x_invoice' => $cart->id,
            'x_amount' => $cart->getOrderTotal(true, Cart::BOTH),
            'x_customer_first_name' => $customer->firstname,
            'x_customer_last_name' => $customer->lastname,
            'x_customer_email' => $customer->email,
            // 'x_customer_phone' => $customerPhone,
            'x_customer_billing_address1' => $address_billing->address1,
            'x_customer_billing_address2' => $address_billing->address2,
            'x_customer_billing_city' => $address_billing->city,
            'x_customer_billing_state' => 'ACT',
            'x_customer_billing_zip' => $address_billing->postcode,
            'x_customer_billing_country' => $country_billing->iso_code,
            'x_customer_shipping_address1' => $address_shipping->address1,
            'x_customer_shipping_address2'=> $address_shipping->address2,
            'x_customer_shipping_city' => $address_shipping->city,
            'x_customer_shipping_state' => '',
            'x_customer_shipping_zip' => $address_shipping->postcode,
            'x_customer_shipping_country' => $country_shipping->iso_code,
            'x_test' => 'false'
        );
        $signature = OxipayCommon::generateSignature($query, Configuration::get('OXIPAY_API_KEY'));
		$query['x_signature'] = $signature; 
          
		$this->context->smarty->assign(array(
			'nbProducts' => $cart->nbProducts(),
			'cust_currency' => $cart->id_currency,
			'currencies' => $this->module->getCurrency((int)$cart->id_currency),
			'total' => $cart->getOrderTotal(true, Cart::BOTH),
			'this_path' => $this->module->getPathUri(),
			'this_path_bw' => $this->module->getPathUri(),
            'form_query' =>$this->generate_processing_form(Configuration::get('OXIPAY_GATEWAY_URL'), $query),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

        return $this->setTemplate('redirect.tpl');
    }

    protected function displayError($message, $description = false)
    {
        /**
         * Create the breadcrumb for your ModuleFrontController.
         */
        $this->context->smarty->assign('path', '
			<a href="'.$this->context->link->getPageLink('order', null, null, 'step=3').'">'.$this->module->l('Payment').'</a>
			<span class="navigation-pipe">&gt;</span>'.$this->module->l('Error'));

        /**
         * Set error message and description for the template.
         */
        array_push($this->errors, $this->module->l($message), $description);

        return $this->setTemplate('error.tpl');
    }

    function generate_processing_form($checkoutUrl, $query) {
    
        $html ="<form style='display:none;' id='oxipayload' method='post' action='$checkoutUrl'>";
    
        foreach ($query as $item => $value) {
            if (substr($item, 0, 2) === "x_") {
                $html .= "<input id='$item' name='$item' value='".htmlspecialchars($value, ENT_QUOTES)."' type='hidden'/>";
            }
        }
    
        $html .= "</form>";
        return $html;
    }

}
