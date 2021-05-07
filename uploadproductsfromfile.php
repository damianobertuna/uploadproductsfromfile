<?php
/**
* 2007-2021 PrestaShop
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
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(dirname(__FILE__).'/classes/Utils.php');

class Uploadproductsfromfile extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'uploadproductsfromfile';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'DAMIANO BERTUNA';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;
        $this->_html = '';

        parent::__construct();

        $this->displayName = $this->trans('Upload products from file');
        $this->description = $this->trans('This module lets you to create products on PrestaShop uploading a csv file');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('UPLOADPRODUCTSFROMFILE_PRODUCTS_FILE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('UPLOADPRODUCTSFROMFILE_PRODUCTS_FILE');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        
        if (((bool)Tools::isSubmit('submitUploadproductsfromfileModule')) == true) {
            //$this->postProcess();
            
            //file upload code
			if (isset($_FILES['UPLOADPRODUCTSFROMFILE_PRODUCTS_FILE'])) {
				$target_dir = _PS_UPLOAD_DIR_;
                $filename = basename($_FILES['UPLOADPRODUCTSFROMFILE_PRODUCTS_FILE']["name"]);
                $target_file = $target_dir . $filename;                
                $tmpFile = $_FILES['UPLOADPRODUCTSFROMFILE_PRODUCTS_FILE']["tmp_name"];
				$uploadStatus = true;
                $fileType = pathinfo($target_file,PATHINFO_EXTENSION);

                if ($fileType != "csv") {
                    $this->_html .= $this->displayError($this->trans('Only CSV files are supported', array(), 'Admin.Notifications.Error'));
					$uploadStatus = false;
				}

                if ($uploadStatus) {
                    if (move_uploaded_file($tmpFile, $target_file)) {						
						$file_location = basename($tmpFile);
                        $productsNumberCreated = $this->processCsvFile($target_dir . $filename);

                        if ($productsNumberCreated) {
                            $this->_html .= $this->displayConfirmation($this->trans('File uploaded successfully, created '.$productsNumberCreated.' products', array(), 'Admin.Notifications.Success'));                            
                        } else {
                            $this->_html .= $this->displayError($this->trans('Columns in file not as expected', array(), 'Admin.Notifications.Error'));
                        }

                        
					} else {
						$this->_html .= $this->displayError($this->trans('Problem uplaoding the file', array(), 'Admin.Notifications.Error'));
					}
                }                
            }

        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        $output .= $this->_html;
        $output .= $this->renderForm();
        
        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
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
        $helper->submit_action = 'submitUploadproductsfromfileModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->trans('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 6,
                        'type' => 'file',
                        'prefix' => '<i class="icon icon-file"></i>',
                        'desc' => $this->trans('Upload a valid CSV file'),
                        'name' => 'UPLOADPRODUCTSFROMFILE_PRODUCTS_FILE',
                        'label' => $this->trans('CSV File'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Upload and Create'),
                    'class' => 'btn btn-default pull-left'
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
            'UPLOADPRODUCTSFROMFILE_PRODUCTS_FILE' => Configuration::get('UPLOADPRODUCTSFROMFILE_PRODUCTS_FILE', ''),            
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            $res = Configuration::updateValue($key, Tools::getValue($key));

            if (!$res) {
                return $res;
            }
        }
        return $res;
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

    private function processCsvFile($csvFile) {
        $utilsObj = new Utils();
        $row = 0;
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {     
                if (count($data) != 9) {
                    return false;
                }
                
                if ($row > 0) {
                    $productName                            = [$this->context->language->id => $data[0]];
                    $productLinkRewrite                     = [$this->context->language->id => Tools::str2url($data[0])];                    
                    $productReference                       = $data[1];
                    $productEan13                           = $data[2];
                    $productCostPrice                       = floatval($data[3]);
                    $productRetailPrice                     = floatval($data[4]);
                    $productTaxRate                         = floatval($data[5]);
                    $productQuantity                        = intval($data[6]);
                    $productCategories                      = $data[7];
                    $categoriesIdArray                      = $utilsObj->createCategories($productCategories, $this->context->language->id);
                    $productManufacturer                    = $data[8];

                    $productObj                             = new Product();
                    $productObj->name                       = $productName;
                    $productObj->link_rewrite               = $productLinkRewrite;
                    $productObj->reference                  = $productReference;
                    $productObj->price                      = $productRetailPrice;                    
                    $productObj->ean13                      = $productEan13;
                    $productObj->wholesale_price            = $productCostPrice;
                    $productObj->quantity                   = $productQuantity;
                    $taxId                                  = $utilsObj->getTaxId($productTaxRate, $this->context->language->id);
                    if ($taxId) {
                        $productObj->id_tax_rules_group     = $taxId;
                    }

                    if (count($categoriesIdArray) == 0) {
                        $categoriesIdArray[]                = 2;
                    } else {
                        array_unshift($categoriesIdArray , 2);
                    }

                    $productObj->id_category            = $categoriesIdArray;
                    $productObj->id_category_default    = $categoriesIdArray[count($categoriesIdArray)-1];

                    if ($productManufacturer != "") {
                        $manufacturerId                         = $utilsObj->getManufacturerId($productManufacturer);                    
                        $productObj->id_manufacturer            = $manufacturerId;
                    }

                    if ($productObj->add()) {
                        $productObj->updateCategories($categoriesIdArray);
                        StockAvailable::setQuantity((int)$productObj->id, 0, $productObj->quantity, Context::getContext()->shop->id);                        
                    }
                }
                $row++;                
            }
            fclose($handle);
            return $row-1;
        }
    }
}
