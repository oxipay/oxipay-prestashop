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
<div class="row">
    {if $error_oxipay}
        <p style="background: rgb(255, 109, 109) none repeat scroll 0% 0%; margin-right: 15px; margin-left: 15px; padding: 15px 20px; color: white; font-weight: bold; border-radius: 10px;">{l s='The Payment provider rejected the transaction. Please try again.' mod='oxipayprestashop'}</p>
    {/if}
    <div class="col-xs-12">
        <p class="payment_module">
        	<a href="#oxipayprestashop" id="oxipayprestashop" title="{$oxipay_title}" class="oxipay" style="padding-left: 20px;">
                <img src="{$this_path_ssl}images/{if $oxipay_logo}{$oxipay_logo}{else}oxipay.png{/if}" style="width: 120px;margin-right: 10px;" />
        		{if $oxipay_title}{$oxipay_title}{else}{l s='Oxipay' mod='oxipayprestashop'}{/if} <span>({if isset($oxipay_description) && $oxipay_description}{$oxipay_description}{else}{l s='Breathe easy with Oxipay, an interest-free installment payment plan' mod='oxipayprestashop'}{/if})</span>
        </p>
    </div>
    {$form_query}
</div>
<script type="text/javascript">
    $(document).on('click', '#oxipayprestashop', function(e){
		e.preventDefault();
		$('#oxipayload').submit();
	});
</script>