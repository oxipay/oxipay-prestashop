<?php
/*
* 2007-2016 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
class Oxipay_prestashopPaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $address_billing = new Address($cart->id_address_invoice);	
        $address_shipping = new Address($cart->id_address_delivery);
        $country_billing = new Country($address_shipping->id_country);
        $country_shipping = new Country($address_shipping->id_country);
        $query =Array
        (
            'x_reference' => $cart->id,
            'x_account_id' => Configuration::get('OXIPAY_MERCHANT_ID'),
            'x_amount' => $cart->getOrderTotal(true, Cart::BOTH),
            'x_currency' => $this->context->currency->iso_code,
            'x_url_callback' => $this->context->link->getModuleLink('oxipay_prestashop','validation'),
            'x_url_complete' => $this->context->link->getPageLink('order-confirmation',null,null,array('id_cart'=>$cart->id,'id_module'=>$this->module->id,'key'=>$customer->secure_key)),
            'x_url_cancel' => $this->context->link->getPageLink('order', true, NULL, "step=3"),
            'x_test' => true,
            'x_shop_country' => 'AU',
            'x_shop_name' =>Configuration::get('PS_SHOP_NAME'), 
            'x_customer_first_name' => $customer->firstname,
            'x_customer_last_name' => $customer->lastname,
            'x_customer_email' => $customer->email,
            'x_customer_phone' => $customer->phone_mobile?$customer->phone_mobile:$customer->phone,
            'x_customer_billing_country' => $country_billing->iso_code ,
            'x_customer_billing_city' => $address_billing->city,
            'x_customer_billing_address1' => $address_billing->address1,
            'x_customer_billing_address2' => $address_billing->address2,
            'x_customer_billing_state' => 'ACT',
            'x_customer_billing_zip' => $address_billing->postcode,
            'x_customer_shipping_country' => $country_shipping->iso_code,
            'x_customer_shipping_city' => $address_shipping->city,
            'x_customer_shipping_address1' => $address_shipping->address1,
            'x_customer_shipping_address2'=> $address_shipping->address2,
            'x_customer_shipping_state' => '',
            'x_customer_shipping_zip' => $address_shipping->postcode,
            'gateway_url' => $this->getUrlGateway(),
        );
        $signature = $this->oxipay_sign($query, Configuration::get('OXIPAY_API_KEY'));
		$query['x_signature'] = $signature; 
          
		$this->context->smarty->assign(array(
			'nbProducts' => $cart->nbProducts(),
			'cust_currency' => $cart->id_currency,
			'currencies' => $this->module->getCurrency((int)$cart->id_currency),
			'total' => $cart->getOrderTotal(true, Cart::BOTH),
			'this_path' => $this->module->getPathUri(),
			'this_path_bw' => $this->module->getPathUri(),
            'form_query' =>$this->generate_processing_form($query),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		$this->setTemplate('payment_execution.tpl');
	}
    
    
}
