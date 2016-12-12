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
class Oxipay_prestashopConfirmationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
	   $id_cart= Tools::getValue('x_reference');
       $cart = new Cart($id_cart);
       $customer= new Customer($cart->id_customer);
       if(Context::getContext()->cart->id=$id_cart)
            Tools::redirect(Context::getContext()->link->getPageLink('order', true, NULL, "step=3&error_oxipay=1"));
       else
            Tools::redirect(Context::getContext()->link->getPageLink('order-confirmation',null,null,array('id_cart'=>$cart->id,'id_module'=>$this->module->id,'key'=>$customer->secure_key)));  
	}
}
