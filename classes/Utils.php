<?php

class Utils {
    
    public function __construct() {
    }

    public function createCategories($categories, $languageId) {
        // create array with all categories
        $categoriesArray = explode(";", $categories);
        $categoriesId = array();

        $idParentCategory = "";
        // for each category check if exist otherwise create it
        foreach ($categoriesArray as $categoryName) {
            if ( $categoryName == "") {
                continue;
            }

            $categoryObj = new Category();
            //echo "<pre>";
            //var_dump($categoryName);
            $categoryExists = $categoryObj->searchByName($languageId, $categoryName, true, true);

            if ($categoryExists != false && count($categoryExists) > 0) {
                $categoriesId[]                 = $categoryExists["id_category"];
                $idParentCategory               = $categoryExists["id_category"];
            } else { // create category           
                $categoryObj = new Category();     
                $link                           = Tools::link_rewrite($categoryName);
                $categoryObj->active            = 1;                
                $categoryObj->name              = array();
                $categoryObj->name[1]           = $categoryName;
                $categoryObj->name[2]           = $categoryName;
                $categoryObj->link_rewrite      = array();
                $category->link_rewrite[1]      = $link;
                $categoryObj->link_rewrite[2]   = $link;                
                
                $categoryObj->id_parent    = $idParentCategory;
                if ($idParentCategory == "") {
                    $categoryObj->id_parent     = 2;                    
                }

                $res                            = $categoryObj->add();
                $categoriesId[]                 = intval($categoryObj->id);
                $idParentCategory               = intval($categoryObj->id);
            }
        }            
        return $categoriesId;
    }

    public function getTaxId($productTaxRate, $languageId) {

        $name_tax = 'Import module tax ('. $productTaxRate .'%)';

        $tax = new Tax();

        $id_tax = $tax->getTaxIdByName($name_tax);

        if(!$id_tax){
            $tax->name =  array($languageId => $name_tax);
            $tax->rate = floatval($productTaxRate);
            $tax->active = 1;
            if($tax->id ){
                $tax->setFieldsToUpdate($tax->getFieldsShop());
            }
            $tax->save();
            
            $tax_rule_group = new TaxRulesGroup();
            $tax_rule_group->name = $name_tax; //array($languageId => $name_tax);
            $tax_rule_group->active = 1;
            if($tax_rule_group->id ){
                $tax_rule_group->setFieldsToUpdate($tax_rule_group->getFieldsShop());
            }
            $tax_rule_group->save();


            $this->createRule($tax->id, $tax_rule_group->id, $languageId);
            return $tax_rule_group->id;
        } else {
            $db = Db::getInstance();
            $sql = 'SELECT id_tax_rules_group FROM `' . _DB_PREFIX_ . 'tax_rule` WHERE id_tax = '.pSQL(intval($id_tax)).' LIMIT 1';            
            $taxRulesGroupId = $db->executeS($sql);
            
            return $taxRulesGroupId[0]["id_tax_rules_group"];
        }







        $sql = 'SELECT id_tax FROM `' . _DB_PREFIX_ . 'tax` WHERE rate = "'.pSQL($productTaxRate).'.000" LIMIT 1';
        $db = Db::getInstance();
        $taxId = $db->executeS($sql);
        $name_tax = 'Import module tax ('. $productTaxRate .'%)';
        
        if (count($taxId) > 0) {
            $taxId = $taxId[0]["id_tax"];

            $sql = 'SELECT id_tax_rules_group FROM `' . _DB_PREFIX_ . 'tax_rule` WHERE id_tax = '.pSQL(intval($taxId)).' LIMIT 1';
            $taxRulesGroupId = $db->executeS($sql);
            echo "<br />";
            var_dump($taxRulesGroupId);
            exit();
            return $taxId;
        } else {
            return false;
            /*$name_tax = 'Import module tax ('. $productTaxRate .'%)';
            var_dump($name_tax);
            exit();
            $tax = new Tax();
            $tax->name =  $name_tax;
            $tax->rate = floatval($productTaxRate);
            $tax->active = 1;
            if( $this->_idShop == null && $tax->id ){
              $tax->setFieldsToUpdate($tax->getFieldsShop());
            }
            $tax->save();
            $tax_rule_group = new TaxRulesGroup();
            $tax_rule_group->name =  $name_tax;
            $tax_rule_group->active = 1;
            if( $this->_idShop == null && $tax_rule_group->id ){
              $tax_rule_group->setFieldsToUpdate($tax_rule_group->getFieldsShop());
            }
            $tax_rule_group->save();
            return $tax->id;*/
        }
    }

    public function getManufacturerId($productManufacturer) {
        $manufacturerObj            = new Manufacturer();
        $manufacturerId             = $manufacturerObj->getIdByName($productManufacturer);

        if ($manufacturerId === false) {            
            $manufacturerObj->name = $productManufacturer;            
            $manufacturerObj->active = 1;
            $manufacturerObj->save();
            $manufacturerId = $manufacturerObj->id;
        }
        return $manufacturerId;
    }



    private function createRule($id_tax, $id_tax_rules_group, $languageId) {
        $zip_code = 0;
        $id_rule = (int)0;
        $behavior = (int)0;
        $description = "";
        $errors = array();

        $countries = Country::getCountries($languageId);
        $selected_countries = array();
        foreach ($countries as $country) {
            $selected_countries[] = (int)$country['id_country'];
        }

        if (empty($selected_states) || count($selected_states) == 0) {
            $selected_states = array(0);
        }

        $tax_rules_group = new TaxRulesGroup((int)$id_tax_rules_group);
        foreach ($selected_countries as $id_country) {
            $first = true;
            foreach ($selected_states as $id_state) {
                $tax_rules_group->id = $id_tax_rules_group;
                if ($tax_rules_group->hasUniqueTaxRuleForCountry($id_country, $id_state, $id_rule)) {
                    $errors[] = Tools::displayError('A tax rule already exists for this country/state with tax only behavior.');
                    continue;
                }
                $tr = new TaxRule();

                // update or creation?
                if (isset($id_rule) && $first) {
                    $tr->id = $id_rule;
                    $first = false;
                }

                $tr->id_tax = $id_tax;
                $tax_rules_group = new TaxRulesGroup((int)$id_tax_rules_group);
                $tr->id_tax_rules_group = (int)$tax_rules_group->id;
                $tr->id_country = (int)$id_country;
                $tr->id_state = (int)$id_state;
                list($tr->zipcode_from, $tr->zipcode_to) = $tr->breakDownZipCode($zip_code);

                // Construct Object Country
                $country = new Country((int)$id_country, (int)$languageId);

                if ($zip_code && $country->need_zip_code) {
                    if ($country->zip_code_format) {
                        foreach (array($tr->zipcode_from, $tr->zipcode_to) as $zip_code) {
                            if ($zip_code) {
                                if (!$country->checkZipCode($zip_code)) {
                                $errors[] = sprintf(
                                    Tools::displayError('The Zip/postal code is invalid. It must be typed as follows: %s for %s.'),
                                    str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $country->zip_code_format))), $country->name
                                    );
                                }
                            }
                        }
                    }
                }

                $tr->behavior = (int)$behavior;
                $tr->description = $description;
                $tax_rule = $tr;

                if (count($errors) == 0) {
                    //$tax_rules_group = $this->updateTaxRulesGroup($tax_rules_group);
            
                    if( $tr->id ){
                        $tr->setFieldsToUpdate($tr->getFieldsShop());
                    }
                    if (!$tr->save()) {
                        $this->errors[] = Tools::displayError('An error has occurred: Cannot save the current tax rule.');
                    }
                }
            }
        }
  }

}

?>