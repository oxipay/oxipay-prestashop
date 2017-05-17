{*
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
*}
<div class="row">
	<div class="col-xs-12">
		<p class="payment_module" id="oxipayprestashop_payment_button">
			{if $oxipay_validation_errors }
				<a href="">
					<img src="{$this_path_ssl}images/{if $oxipay_logo}{$oxipay_logo}{else}oxipay.png{/if}" style="width: 120px;margin-right: 10px;" alt="{l s='Pay with my payment module' mod='oxipayprestashop'}" />
					{$oxipay_validation_errors}
				</a>
			{else}
				<a href="{$link->getModuleLink('oxipayprestashop', 'redirect', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with my payment module' mod='oxipayprestashop'}">
					<img src="{$this_path_ssl}images/{if $oxipay_logo}{$oxipay_logo}{else}oxipay.png{/if}" style="width: 120px;margin-right: 10px;" />
					{if $oxipay_title}{$oxipay_title}{else}{l s='Oxipay' mod='oxipayprestashop'}{/if} <span>({if isset($oxipay_description) && $oxipay_description}{$oxipay_description}{else}{l s='Breathe easy with Oxipay, an interest-free installment payment plan' mod='oxipayprestashop'}{/if})</span>
				</a>
			{/if}
		</p>
	</div>
</div>
