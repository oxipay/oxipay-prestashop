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
class Oxipay_prestashopValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
	   $post = Tools::jsonEncode($_POST);
       $get = Tools::jsonEncode($_GET);
	     /*file_put_contents(dirname(__FILE__).'/text.txt', time());
       file_put_contents(dirname(__FILE__).'/post.txt', $post);
       file_put_contents(dirname(__FILE__).'/get.txt', $get);*/
       $query = array(
            'x_account_id'=>Tools::getValue('x_account_id'),
            'x_reference'=>Tools::getValue('x_reference'),
            'x_currency' =>Tools::getValue('x_currency'),
            'x_test'=>Tools::getValue('x_test'),
            'x_amount' => Tools::getValue('x_amount'),
            'x_gateway_reference'=>Tools::getValue('x_gateway_reference'),
            'x_timestamp' => Tools::getValue('x_timestamp'),
            'x_result' =>Tools::getValue('x_result'),
       );
       $signature = $this->oxipay_sign($query, Configuration::get('OXIPAY_API_KEY'));
       if($signature==Tools::getValue('x_signature') && Tools::getValue('x_result')=='completed')
       {
            $id_cart= (int)Tools::getValue('x_reference');
            $cart= new Cart($id_cart);
            $total = Tools::getValue('x_amount');
            $customer = new Customer($cart->id_customer);
            $this->module->validateOrder($cart->id, 2, $total, $this->module->displayName, NULL, NULL, (int)$cart->id_currency, false, $customer->secure_key);
       }
	}
    public function oxipay_sign($query, $api_key )
    {
        $clear_text = '';
        ksort($query);
        foreach ($query as $key => $value) {
            if (substr($key, 0, 2) === "x_") {
                $clear_text .= $key . $value;
            }
        }
        $hash = hash_hmac( "sha256", $clear_text, $api_key);
        return str_replace('-', '', $hash);
    }
}
