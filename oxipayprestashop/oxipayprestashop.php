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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__).'/common/OxipayCommon.php');

class Oxipayprestashop extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'oxipayprestashop';
        $this->tab = 'payments_gateways';
        $this->version = 'oxipay_plugin_version_placeholder';
        $this->author = 'Oxipay';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Oxipay prestashop');
        $this->description = $this->l('Accept payments for your products via Oxipay.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the Oxipay module?');

        $this->limited_countries = array('AU', 'NZ');
        $this->limited_currencies = array('AUD', 'NZD');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => '1.7.99.99');
    }

    /**
     * If we need to create update methods: http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if (in_array($iso_code, $this->limited_countries) == false)
        {
            $this->_errors[] = $this->l('This module is not available in your country');
            return false;
        }

        //Default values
        Configuration::updateValue('OXIPAY_TITLE', 'Oxipay');
        Configuration::updateValue('OXIPAY_DESCRIPTION', 'Breathe easy with Oxipay, an interest-free installment payment plan.');
        
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn');
    }

    public function uninstall()
    {
        Configuration::deleteByName('OXIPAY_TITLE');
        Configuration::deleteByName('OXIPAY_DESCRIPTION');
        Configuration::deleteByName('OXIPAY_GATEWAY_URL');
        Configuration::deleteByName('OXIPAY_MERCHANT_ID');
        Configuration::deleteByName('OXIPAY_API_KEY');
        Configuration::deleteByName('OXIPAY_LOGO');

        return parent::uninstall();
    }

	protected function _postValidation()
	{
		/**
         * If values have been submitted in the form, process.
         */
        $postErrors = array();
        if (((bool)Tools::isSubmit('submitOxipayprestashopModule')) == true)
        {
            if (!Tools::getValue('OXIPAY_TITLE'))
				$postErrors[] = $this->l('Title is required.');
            if (!Tools::getValue('OXIPAY_DESCRIPTION'))
				$postErrors[] = $this->l('Description is required.');
			if (!Tools::getValue('OXIPAY_MERCHANT_ID'))
				$postErrors[] = $this->l('Merchant ID is required.');
			if (!Tools::getValue('OXIPAY_API_KEY') && !Configuration::get('OXIPAY_API_KEY')) //read comment in postProcess() about the particularity of 'password' type input fields
				$postErrors[] = $this->l('API Key is required.');
            if(!Tools::getValue('OXIPAY_GATEWAY_URL'))
                $postErrors[] = $this->l('Oxipay Gateway URL is required');
		}
        return $postErrors;
	}

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If the 'Delete' image button was pressed
         */
        if(Tools::isSubmit('delete_logo') && Tools::getValue('delete_logo'))
        {
           unlink($this->_path.'/images/'.Configuration::get('OXIPAY_LOGO'));
           Configuration::updateValue('OXIPAY_LOGO','');
           Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
        }

        /**
         * If values have been submitted in the form ('Save' button), process.
         */
        $html = '';
        if (((bool)Tools::isSubmit('submitOxipayprestashopModule')) == true) {
			$postErrors = $this->_postValidation();
            $this->postProcess(); //we still want to save the correct settings, otherwise they'll be lost the first time
			if (!count($postErrors)) {
                $html .= $this->displayConfirmation($this->l('Oxipay settings updated.'));
            } else {
				foreach ($postErrors as $err) {
					$html .= $this->displayError($err);
                } 
            }
        } else {
			$html .= '<br />';
        } 

        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->fetch($this->local_path.'views/templates/admin/configure.tpl');

        $html .= $output.$this->renderForm();

        return $html;
    }

    /**
     * Create the form that will be displayed in the configuration of the module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitOxipayprestashopModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for our inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of the configuration form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Title'),
                        'name' => 'OXIPAY_TITLE',
                        'desc' => $this->l('This controls the title which the user sees during checkout.'),
                        'required' => true
                    ),
                    array(
						'type' => 'file',
						'label' => $this->l('Logo'),
						'name' => 'OXIPAY_LOGO',
                        'image' => Configuration::get('OXIPAY_LOGO') ? '<img src="'.$this->_path.'/images/'.Configuration::get('OXIPAY_LOGO').'" alt="'.$this->l('Logo').'" title="'.$this->l('Logo').'" />':null,
                        'delete_url' =>defined('PS_ADMIN_DIR')? 'index.php?controller=AdminModules&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&delete_logo=1&token='.Tools::getAdminToken('AdminModules'.(int)(Tab::getIdFromClassName('AdminModules')).(int)$this->context->employee->id):''
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Description'),
                        'prefix' => '<i class="icon icon-comment-alt"></i>',
                        'name' => 'OXIPAY_DESCRIPTION',
                        'desc' => $this->l('This controls the description which the user sees during checkout.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Oxipay Gateway URL'),
                        'prefix' => '<i class="icon icon-globe"></i>',
                        'name' => 'OXIPAY_GATEWAY_URL',
                        'desc' => $this->l('This is the base URL of the Oxipay payment sevices. Do not change this unless directed to by Oxipay staff.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Merchant ID'),
                        'prefix' => '<i class="icon icon-user"></i>',
                        'name' => 'OXIPAY_MERCHANT_ID',
                        'desc' => $this->l('This is the unique number that identifies you as a merchant to the Oxipay Payment Gateway.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'password',
                        'label' => $this->l('API Key'),
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'OXIPAY_API_KEY',
                        'desc' => $this->l('This is used to authenticate you as a merchant and to ensure that no one can tamper with the information sent as part of purchase orders.'),
                        'required' => true
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'OXIPAY_TITLE' => Configuration::get('OXIPAY_TITLE'),
            'OXIPAY_LOGO' => Configuration::get('OXIPAY_LOGO'),
            'OXIPAY_DESCRIPTION' => Configuration::get('OXIPAY_DESCRIPTION'),
            'OXIPAY_GATEWAY_URL' => Configuration::get('OXIPAY_GATEWAY_URL'),
            'OXIPAY_MERCHANT_ID' => Configuration::get('OXIPAY_MERCHANT_ID'),
            'OXIPAY_API_KEY' => Configuration::get('OXIPAY_API_KEY'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        //custom logo image upload processing (if necessary)
        //OXIPAY_LOGO config property is updated here
        $this->processCustomOxipayLogoUpload();

        //save the values for the rest of the configuration properties
        Configuration::updateValue('OXIPAY_TITLE', Tools::getValue('OXIPAY_TITLE'));
        Configuration::updateValue('OXIPAY_DESCRIPTION', Tools::getValue('OXIPAY_DESCRIPTION'));
        Configuration::updateValue('OXIPAY_GATEWAY_URL', Tools::getValue('OXIPAY_GATEWAY_URL'));
        Configuration::updateValue('OXIPAY_MERCHANT_ID', Tools::getValue('OXIPAY_MERCHANT_ID'));
        $apiKey = strval(Tools::getValue('OXIPAY_API_KEY'));
        if($apiKey) {
            //it seems that the 'password' type input fields were designed only to
            //change a password; they never display anything.
            //https://www.prestashop.com/forums/topic/347850-possible-bug-with-helperform-and-password-type-fields/
            //only save if a value was entered
            Configuration::updateValue('OXIPAY_API_KEY', $apiKey);
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        if ($this->cartValidationErrors($params['cart'])) {
            return;
        }

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name);
        $newOption->setCallToActionText($this->trans('Pay by Oxipay', array(), 'Modules.Oxipayprestashop.Admin'));
        $newOption->setAction($this->context->link->getModuleLink($this->name, 'redirect', array(), true));
        $newOption->setAdditionalInformation($this->fetch($this->local_path.'views/templates/hook/payment.tpl'));
        $newOption->setLogo(Media::getMediaPath($this->local_path.'images/oxipay-small.png'));

        return [$newOption];
    }

    /**
     * This hook is used to display the order confirmation page.
     * @param $params
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false)
            return;

        $order = $params['order'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $total = Tools::displayPrice($params['order']->getOrdersTotalPaid(), new Currency($params['order']->id_currency), false );
        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total' => $total,
            'shop_name' => $this->context->shop->name
        ));

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks the quote for validity
     * @throws Mage_Api_Exception
     */
    private function cartValidationErrors($cart)
    {
        $shippingAddress = new Address((int)$cart->id_address_delivery);
        $billingAddress = new Address((int)$cart->id_address_invoice);
        $currency = new Currency((int)$cart->id_currency);

        $billingCountryIsoCode = (new Country($billingAddress->id_country))->iso_code;
        $shippingCountryIsoCode = (new Country($shippingAddress->id_country))->iso_code;
        $currencyIsoCode = $currency->iso_code;

        if($cart->getOrderTotal() < 20) {
            return "Oxipay doesn't support purchases less than $20.";
        }

        $countryInfo = OxipayCommon::getCountryInfoFromGatewayUrl();

        if($billingCountryIsoCode != $countryInfo['countryCode'] || $currencyIsoCode != $countryInfo['currencyCode']) {
            return "Oxipay doesn't support purchases from outside ".($countryInfo['countryName']).".";
        }

        if($shippingCountryIsoCode != $countryInfo['countryCode']) {
            return "Oxipay doesn't support purchases shipped outside ".($countryInfo['countryName']).".";
        }

        return "";
    }


    private function processCustomOxipayLogoUpload() {
        $logoKey = 'OXIPAY_LOGO';
        $errors = NULL;
        if(isset($_FILES[$logoKey]['tmp_name']) && isset($_FILES[$logoKey]['name']) && $_FILES[$logoKey]['name'])
        {
            $salt = sha1(microtime());
            $type = Tools::strtolower(Tools::substr(strrchr($_FILES[$logoKey]['name'], '.'), 1));
            $imageName = $salt.'.'.$type;
            $fileName = dirname(__FILE__).'/images/'.$imageName;
            if(file_exists($fileName))
            {
                $errors[] = $this->l('Logo already exists. Try to rename the file then reupload');
            }
            else
            {
                $imagesize = @getimagesize($_FILES[$logoKey]['tmp_name']);

                if (!$errors && isset($_FILES[$logoKey]) &&
                    !empty($_FILES[$logoKey]['tmp_name']) &&
                    !empty($imagesize) &&
                    in_array($type, array('jpg', 'gif', 'jpeg', 'png'))
                )
                {
                    $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
                    if ($error = ImageManager::validateUpload($_FILES[$logoKey]))
                        $errors[] = $error;
                    elseif (!$temp_name || !move_uploaded_file($_FILES[$logoKey]['tmp_name'], $temp_name))
                        $errors[] = $this->l('Can not upload the file');
                    elseif (!ImageManager::resize($temp_name, $fileName, null, null, $type))
                        $errors[] = $this->displayError($this->l('An error occurred during the image upload process.'));
                    if (isset($temp_name))
                        @unlink($temp_name);
                    if(!$errors)
                    {
                        if(Configuration::get($logoKey)!='')
                        {
                            $oldImage = dirname(__FILE__).'/images/'.Configuration::get($logoKey);
                            if(file_exists($oldImage))
                                @unlink($oldImage);
                        }
                        Configuration::updateValue($logoKey, $imageName,true);
                    }
                }
            }
        }
    }
}
