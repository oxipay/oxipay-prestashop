{*
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
*}

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='oxipay_prestashop'}">{l s='Checkout' mod='oxipay_prestashop'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Oxipay' mod='oxipay_prestashop'}
{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='oxipay_prestashop'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning ">{l s='Your shopping cart is empty.' mod='oxipay_prestashop'}</p>
{else}
    {*<p class="warning alert alert-error">{l s='Orders from outside Australia are not supported by Oxipay. Please select a different payment option.' mod='oxipay_prestashop'}</p>*}
    {$form_query}        
    <p class="cart_navigation" id="cart_navigation">
    	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='bankwire'}</a>
    </p>
{/if}
<script type="text/javascript">
$(document).ready(function(){
   $('#oxipayload').submit(); 
});
</script>