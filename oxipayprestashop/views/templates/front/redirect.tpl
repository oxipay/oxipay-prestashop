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

<div class="oxipaymodal">

{* Check all this! *}
{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='oxipayprestashop'}">{l s='Checkout' mod='oxipayprestashop'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Oxipay' mod='oxipayprestashop'}
{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='oxipayprestashop'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning ">{l s='Your shopping cart is empty.' mod='oxipayprestashop'}</p>
{else}
    {*<p class="warning alert alert-error">{l s='Orders from outside Australia are not supported by Oxipay. Please select a different payment option.' mod='oxipayprestashop'}</p>*}
    {$form_query}        
    <p class="cart_navigation" id="cart_navigation">
    	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='bankwire'}</a>
    </p>
{/if}
<script type="text/javascript">
$body = $("body");
$(document).ready(function(){
   $body.addClass("oxipayloading");
   $('#oxipayload').submit(); 
});
</script>

{* Original code:
<div>
	<h3>{l s='Redirect your customer' mod='oxipayprestashop'}:</h3>
	<ul class="alert alert-info">
			<li>{l s='This action should be used to redirect your customer to the website of your payment processor' mod='oxipayprestashop'}.</li>
	</ul>
	
	<div class="alert alert-warning">
		{l s='You can redirect your customer with an error message' mod='oxipayprestashop'}:
		<a href="{$link->getModuleLink('oxipayprestashop', 'redirect', ['action' => 'error'], true)|escape:'htmlall':'UTF-8'}" title="{l s='Look at the error' mod='oxipayprestashop'}">
			<strong>{l s='Look at the error message' mod='oxipayprestashop'}</strong>
		</a>
	</div>
	
	<div class="alert alert-success">
		{l s='You can also redirect your customer to the confirmation page' mod='oxipayprestashop'}:
		<a href="{$link->getModuleLink('oxipayprestashop', 'confirmation', ['cart_id' => $cart_id, 'secure_key' => $secure_key], true)|escape:'htmlall':'UTF-8'}" title="{l s='Confirm' mod='oxipayprestashop'}">
			<strong>{l s='Go to the confirmation page' mod='oxipayprestashop'}</strong>
		</a>
	</div>
</div>
*}
