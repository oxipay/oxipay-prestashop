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

if (!defined('_PS_VERSION_'))
	exit;

class Oxipay_prestashop extends PaymentModule
{
	protected $_html = '';
	protected $_postErrors = array();

	public $details;
	public $owner;
	public $address;
	public $extra_mail_vars;
	public function __construct()
	{
		$this->name = 'oxipay_prestashop';
		$this->tab = 'payments_gateways';
		$this->version = '1.1.0';
		$this->author = 'PrestaShop';
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Oxipay prestashop');
		$this->description = $this->l('Accept payments for your products via Oxipay.');
		$this->confirmUninstall = $this->l('Are you sure about removing these details?');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7.99.99');
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment') || ! $this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn'))
			return false;
        $languages = Language::getLanguages(false);
        $title = array();
        $description = array();
        foreach($languages as $language)
        {
            $title[$language['id_lang']] = $this->l('Oxipay');
            $description[$language['id_lang']] = $this->l('Breathe easy with Oxipay, an interest-free installment payment plan.');
        }
        Configuration::updateValue('OXIPAY_TITLE',$title);
        Configuration::updateValue('OXIPAY_DESCRIPTION',$description);
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('OXIPAY_TEST_MODE')
				|| !Configuration::deleteByName('OXIPAY_MERCHANT_ID')
				|| !Configuration::deleteByName('OXIPAY_API_KEY')
                || !Configuration::deleteByName('OXIPAY_CARDS_ACCEPTED')
                || !Configuration::deleteByName('OXIPAY_TITLE')
                || !Configuration::deleteByName('OXIPAY_DESCRIPTION')
                || !Configuration::deleteByName('OXIPAY_GATEWAY_URL')
                || !Configuration::deleteByName('OXIPAY_SANDBOX_GATEWAY_URL')
                || !Configuration::deleteByName('OXIPAY_LOGO')
				|| !parent::uninstall())
			return false;
		return true;
	}

	protected function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('OXIPAY_MERCHANT_ID'))
				$this->_postErrors[] = $this->l('Merchant Number is required.');
			if (!Tools::getValue('OXIPAY_API_KEY'))
				$this->_postErrors[] = $this->l('Encryption Key is required.');
//            if (!Tools::getValue('OXIPAY_CARDS_ACCEPTED'))
//				$this->_postErrors[] = $this->l('Cards Accepted is required.');
            if(!Tools::getValue('OXIPAY_GATEWAY_URL'))
                $this->_postErrors[] = $this->l('Oxipay gateway url is required');
            if(!Tools::getValue('OXIPAY_SANDBOX_GATEWAY_URL'))
                $this->_postErrors[] = $this->l('Oxipay sandbox gateway url is required');
            $languages = Language::getLanguages(false);
            foreach($languages as $language)
            {
                if(!Tools::getValue('OXIPAY_TITLE_'.$language['id_lang']))
                    $this->_postErrors[]= $this->l('Title  '.$language['name'].' is required');
                if(!Tools::getValue('OXIPAY_DESCRIPTION_'.$language['id_lang']))
                    $this->_postErrors[]= $this->l('Description '.$language['name'].' is required');
            }
		}
	}

	protected function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
            $languages = Language::getLanguages(false);
            $title = array();
            $description = array();
            foreach($languages as $language)
            {
                $title[$language['id_lang']] = Tools::getValue('OXIPAY_TITLE_'.$language['id_lang']);
                $description[$language['id_lang']] =Tools::getValue('OXIPAY_DESCRIPTION_'.$language['id_lang']);
            }
            $key=trim('OXIPAY_LOGO');
            if(isset($_FILES[$key]['tmp_name']) && isset($_FILES[$key]['name']) && $_FILES[$key]['name'])
            {
                $salt = sha1(microtime());
                $type = Tools::strtolower(Tools::substr(strrchr($_FILES[$key]['name'], '.'), 1));
                $imageName = $salt.'.'.$type;
                $fileName = dirname(__FILE__).'/images/'.$imageName;
                if(file_exists($fileName))
                {
                    $errors[] = $this->l('Logo already exists. Try to rename the file then reupload');
                }
                else
                {

        			$imagesize = @getimagesize($_FILES[$key]['tmp_name']);

                    if (!$errors && isset($_FILES[$key]) &&
        				!empty($_FILES[$key]['tmp_name']) &&
        				!empty($imagesize) &&
        				in_array($type, array('jpg', 'gif', 'jpeg', 'png'))
        			)
        			{
        				$temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
        				if ($error = ImageManager::validateUpload($_FILES[$key]))
        					$errors[] = $error;
        				elseif (!$temp_name || !move_uploaded_file($_FILES[$key]['tmp_name'], $temp_name))
        					$errors[] = $this->l('Can not upload the file');
        				elseif (!ImageManager::resize($temp_name, $fileName, null, null, $type))
        					$errors[] = $this->displayError($this->l('An error occurred during the image upload process.'));
        				if (isset($temp_name))
        					@unlink($temp_name);
                        if(!$errors)
                        {
                            if(Configuration::get($key)!='')
                            {
                                $oldImage = dirname(__FILE__).'/images/'.Configuration::get($key);
                                if(file_exists($oldImage))
                                    @unlink($oldImage);
                            }
                            Configuration::updateValue($key, $imageName,true);
                        }
                    }
                }
            }
            Configuration::updateValue('OXIPAY_TITLE',$title);
            Configuration::updateValue('OXIPAY_DESCRIPTION',$description);
            Configuration::updateValue('OXIPAY_GATEWAY_URL',Tools::getValue('OXIPAY_GATEWAY_URL'));
            Configuration::updateValue('OXIPAY_SANDBOX_GATEWAY_URL',Tools::getValue('OXIPAY_SANDBOX_GATEWAY_URL'));
			Configuration::updateValue('OXIPAY_TEST_MODE', Tools::getValue('OXIPAY_TEST_MODE'));
			Configuration::updateValue('OXIPAY_MERCHANT_ID', Tools::getValue('OXIPAY_MERCHANT_ID'));
			Configuration::updateValue('OXIPAY_API_KEY', Tools::getValue('OXIPAY_API_KEY'));
           // Configuration::updateValue('OXIPAY_CARDS_ACCEPTED', Tools::getValue('OXIPAY_CARDS_ACCEPTED'));
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}

	protected function _displayBankWire()
	{
		return $this->display(__FILE__, 'infos.tpl');
	}

	public function getContent()
	{
        $languages = Language::getLanguages(false);
        if(Tools::isSubmit('delete_logo') && Tools::getValue('delete_logo'))
        {
           unlink($this->_path.'/images/'.Configuration::get('OXIPAY_LOGO'));
           Configuration::updateValue('OXIPAY_LOGO','');
           Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
        }
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}
		else
			$this->_html .= '<br />';

		$this->_html .= $this->_displayBankWire();
		$this->_html .= $this->renderForm();

		return $this->_html;
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;
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
            'x_amount' => (float)Tools::ps_round((float)$this->context->cart->getOrderTotal(true, Cart::BOTH), 2),
            'x_currency' => $this->context->currency->iso_code,
            'x_url_callback' => $this->context->link->getModuleLink('oxipay_prestashop','validation',array('id_cart'=>$cart->id,'id_module'=>$this->id,'key'=>$customer->secure_key,'id_customer'=>$customer->id)),
            'x_url_complete' => $this->context->link->getModuleLink('oxipay_prestashop','confirmation'),
            'x_url_cancel' => $this->context->link->getModuleLink('oxipay_prestashop','cancel'),
            'x_test' => true,
            'x_shop_country' => 'AU',
            'x_shop_name' =>Configuration::get('PS_SHOP_NAME'), 
            'x_customer_first_name' => $customer->firstname,
            'x_customer_last_name' => $customer->lastname,
            'x_customer_email' => $customer->email,
            'x_customer_phone' => $address_shipping->phone_mobile?$address_shipping->phone_mobile:$address_shipping->phone,
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
		$this->smarty->assign(array(
			'this_path' => $this->_path,
            'error_oxipay'=>Tools::getValue('error_oxipay',0),
			'this_path_bw' => $this->_path,
            'oxipay_title' => Configuration::get('OXIPAY_TITLE',$this->context->language->id),
            'oxipay_logo' => Configuration::get('OXIPAY_LOGO'),
            'oxipay_description' => Configuration::get('OXIPAY_DESCRIPTION',$this->context->language->id),
            'form_query' =>$this->generate_processing_form($query),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));
		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookDisplayPaymentEU($params)
	{
		if (!$this->active)
			return;

		if (!$this->checkCurrency($params['cart']))
			return;

		$payment_options = array(
			'cta_text' => $this->l('Pay by Oxipay'),
			'logo' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/bankwire.jpg'),
			'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
		);

		return $payment_options;
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;

		$state = $params['objOrder']->getCurrentState();
		if (in_array($state, array(Configuration::get('PS_OS_BANKWIRE'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
		{
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'bankwireDetails' => Tools::nl2br($this->details),
				'bankwireAddress' => Tools::nl2br($this->address),
				'bankwireOwner' => $this->owner,
				'status' => 'ok',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Configure'),
					'icon' => 'icon-envelope'
				),
				'input' => array(
                    array(
						'type' => 'text',
						'label' => $this->l('Title'),
						'name' => 'OXIPAY_TITLE',
                        'lang' =>true,
						'desc' => $this->l('This controls the title which the user sees during checkout.'),
						'required' => true
					),
                    array(
						'type' => 'file',
						'label' => $this->l('Logo'),
						'name' => 'OXIPAY_LOGO',
                        'image' => Configuration::get('OXIPAY_LOGO') ? '<img src="'.$this->_path.'/images/'.Configuration::get('OXIPAY_LOGO').'" alt="'.$this->l('Logo').'" title="'.$this->l('Logo').'" />':null,
                        'delete_url' =>defined('PS_ADMIN_DIR')? 'index.php?controller=AdminModules&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&delete_logo=1&token='.Tools::getAdminToken('AdminModules'.(int)(Tab::getIdFromClassName('AdminModules')).(int)$this->context->employee->id):'',
					),
                    array(
						'type' => 'text',
						'label' => $this->l('Description'),
						'name' => 'OXIPAY_DESCRIPTION',
                        'lang'=>true,
						'desc' => $this->l('This controls the description which the user sees during checkout.'),
						'required' => true
					),
                    array(
						'type' => 'text',
						'label' => $this->l('Oxipay Gateway URL'),
						'name' => 'OXIPAY_GATEWAY_URL',
						'desc' => $this->l('This is the base URL of the Oxipay payment sevices. Do not change this unless directed to by Oxipay staff.'),
						'required' => true
					),
                    array(
						'type' => 'text',
						'label' => $this->l('Oxipay Sandbox Gateway URL'),
						'name' => 'OXIPAY_SANDBOX_GATEWAY_URL',
						'desc' => $this->l('This is the base URL of the Oxipay sandbox sevices.If this test mode is enabled, and this is set- the sandbox will be used. If this not set, with test mode enabled, the sandbox will not be used, but a test flag will still be sent.'),
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('Merchant ID'),
						'name' => 'OXIPAY_MERCHANT_ID',
						'desc' => $this->l('This is the unique number that identifies you as a merchant to the Oxipay Payment Gateway.'),
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('API Key'),
						'name' => 'OXIPAY_API_KEY',
                        'desc' => $this->l('This is used to authenticate you as a merchant and to ensure that no one can tamper with the information sent as part of purchase orders.'),
						'required' => true
					),
                    array(
						'type' => 'switch',
						'label' => $this->l('Test Mode'),
						'name' => 'OXIPAY_TEST_MODE',
                        'desc'=> $this->l('diagnosing issues. In test mode, all Oxipay transactions are simulated and cards are not charged'),
                        'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Yes')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('No')
							)
						),
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		$fields= array(
			'OXIPAY_TEST_MODE' => Tools::getValue('OXIPAY_TEST_MODE', Configuration::get('OXIPAY_TEST_MODE')),
			'OXIPAY_MERCHANT_ID' => Tools::getValue('OXIPAY_MERCHANT_ID', Configuration::get('OXIPAY_MERCHANT_ID')),
			'OXIPAY_API_KEY' => Tools::getValue('OXIPAY_API_KEY', Configuration::get('OXIPAY_API_KEY')),
            'OXIPAY_CARDS_ACCEPTED' => Tools::getValue('OXIPAY_CARDS_ACCEPTED', Configuration::get('OXIPAY_CARDS_ACCEPTED')),
            'OXIPAY_GATEWAY_URL' => Tools::getValue('OXIPAY_GATEWAY_URL',Configuration::get('OXIPAY_GATEWAY_URL')),
            'OXIPAY_SANDBOX_GATEWAY_URL' => Tools::getValue('OXIPAY_SANDBOX_GATEWAY_URL',Configuration::get('OXIPAY_SANDBOX_GATEWAY_URL')),
		);
        $languages = Language::getLanguages(false);
        foreach($languages as $language)
        {
            $fields['OXIPAY_TITLE'][$language['id_lang']]=  Tools::getValue('OXIPAY_TITLE_'.$language['id_lang'],Configuration::get('OXIPAY_TITLE',$language['id_lang']));
            $fields['OXIPAY_DESCRIPTION'][$language['id_lang']] = Tools::getValue('OXIPAY_DESCRIPTION_'.$language['id_lang'],Configuration::get('OXIPAY_DESCRIPTION',$language['id_lang']));
        }
        return $fields;
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
    public function getUrlGateway()
    {
        if(Configuration::get('OXIPAY_TEST_MODE'))
            return Configuration::get('OXIPAY_SANDBOX_GATEWAY_URL');
        else
            return Configuration::get('OXIPAY_GATEWAY_URL');
    }
    function generate_processing_form($query) {
        $url = $query["gateway_url"];
    
        $html ="<form style='display:none;' id='oxipayload' method='post' action='$url'>";
    
        foreach ($query as $item => $value) {
            if (substr($item, 0, 2) === "x_") {
                $html .= "<input id='$item' name='$item' value='$value' type='hidden'/>";
            }
        }
    
        $html .= "</form>";
        return $html;
    }
}
