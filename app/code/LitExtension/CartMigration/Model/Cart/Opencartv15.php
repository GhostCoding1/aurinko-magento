<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

namespace LitExtension\CartMigration\Model\Cart;

class Opencartv15 extends \LitExtension\CartMigration\Model\Cart
{

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_rule WHERE tax_rule_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturer WHERE manufacturer_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_category WHERE category_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_product WHERE product_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer WHERE customer_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM `_DBPRF_order` WHERE order_id > {$this->_notice['orders']['id_src']} AND order_status_id != 0",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_review WHERE review_id > {$this->_notice['reviews']['id_src']}"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this;
        }
        foreach($data['object'] as $type => $row){
            $count = $this->arrayToCount($row);
            $this->_notice[$type]['new'] = $count;
        }
        return $this;
    }

    /**
     * Process and get data use for config display
     *
     * @return array : Response as success or error with msg
     */
    public function displayConfig() {
        $response = array();
        $default_cfg = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                "languages" => "SELECT cfg.*, lg.* FROM _DBPRF_setting AS cfg LEFT JOIN _DBPRF_language AS lg ON lg.code = cfg.value WHERE cfg.key = 'config_language'",
                "currencies" => "SELECT cfg.*, cur.* FROM _DBPRF_setting AS cfg LEFT JOIN _DBPRF_currency AS cur ON cur.code = cfg.value WHERE cfg.key = 'config_currency'"
            ))
        ));
        if (!$default_cfg || $default_cfg['result'] != 'success') {
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if ($object && $object['languages'] && $object['currencies']) {
            $this->_notice['config']['default_lang'] = isset($object['languages']['0']['language_id']) ? $object['languages']['0']['language_id'] : 1;
            $this->_notice['config']['default_currency'] = isset($object['currencies']['0']['currency_id']) ? $object['currencies']['0']['currency_id'] : 1;
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "languages" => "SELECT * FROM _DBPRF_language",
                "currencies" => "SELECT * FROM _DBPRF_currency",
                "orders_status" => "SELECT * FROM _DBPRF_order_status WHERE language_id = '{$this->_notice['config']['default_lang']}'",
                "customer_group_description" => "SELECT * FROM _DBPRF_customer_group_description WHERE language_id = '{$this->_notice['config']['default_lang']}'"
            ))
        ));
        if (!$data || $data['result'] != 'success') {
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        foreach ($obj['languages'] as $language_row) {
            $lang_id = $language_row['language_id'];
            $lang_name = $language_row['name'] . "(" . $language_row['code'] . ")";
            $language_data[$lang_id] = $lang_name;
        }
        foreach ($obj['currencies'] as $currency_row) {
            $currency_id = $currency_row['currency_id'];
            $currency_name = $currency_row['title'];
            $currency_data[$currency_id] = $currency_name;
        }
        foreach ($obj['orders_status'] as $order_status_row) {
            $order_status_id = $order_status_row['order_status_id'];
            $order_status_name = $order_status_row['name'];
            $order_status_data[$order_status_id] = $order_status_name;
        }
        foreach($obj['customer_group_description'] as $cus_status_row){
            $cus_status_id = $cus_status_row['customer_group_id'];
            $cus_status_name = $cus_status_row['name'];
            $customer_group_data[$cus_status_id] = $cus_status_name;
        }
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $this->_notice['config']['customer_group_data'] = $customer_group_data;
        $response['result'] = 'success';
        return $response;
    }

    /**
     * Save config of use in config step to notice
     */
    public function displayConfirm($params) {
        parent::displayConfirm($params);
        return array(
            'result' => 'success'
        );
    }

    /**
     * Get data for import display
     *
     * @return array : Response as success or error with msg
     */
    public function displayImport() {
        $recent = $this->getRecentNotice();
        if ($recent) {
            $types = array('taxes', 'manufacturers', 'categories', 'products', 'customers', 'orders', 'reviews');
            foreach ($types as $type) {
                if ($this->_notice['config']['add_option']['add_new'] || !$this->_notice['config']['import'][$type]) {
                    $this->_notice[$type]['id_src'] = $recent[$type]['id_src'];
                    $this->_notice[$type]['imported'] = 0;
                    $this->_notice[$type]['error'] = 0;
                    $this->_notice[$type]['point'] = 0;
                    $this->_notice[$type]['finish'] = 0;
                }
            }
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_rule WHERE tax_rule_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturer WHERE manufacturer_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_category WHERE category_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_product WHERE product_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer WHERE customer_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM `_DBPRF_order` WHERE order_id > {$this->_notice['orders']['id_src']} AND order_status_id != 0",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_review WHERE review_id > {$this->_notice['reviews']['id_src']}"
            ))
        ));
        if (!$data || $data['result'] != 'success') {
            return $this->errorConnector();
        }
        $totals = array();
        foreach ($data['object'] as $type => $row) {
            $count = $this->arrayToCount($row);
            $totals[$type] = $count;
        }
		//$totals['products'] =1;
		//$totals['orders'] =1;
        $iTotal = $this->_limitDemoModel($totals);
        foreach ($iTotal as $type => $total) {
            $this->_notice[$type]['total'] = $total;
        }
        $this->_notice['taxes']['time_start'] = time();
        if (!$this->_notice['config']['add_option']['add_new']) {
            $delete = $this->_deleteLeCaMgImport($this->_notice['config']['cart_url']);
            if (!$delete) {
                return $this->errorDatabase(true);
            }
        }
        return array(
            'result' => 'success'
        );
    }

    /**
     * Config currency
     */
    public function configCurrency() {
        if(!parent::configCurrency()) return;
        $allowCur = $this->_notice['config']['currencies'];
        $allow_cur = implode(',', $allowCur);
        $this->_process->currencyAllow($allow_cur);
        $default_cur = $this->_notice['config']['currencies'][$this->_notice['config']['default_currency']];
        $this->_process->currencyDefault($default_cur);
        $currencies = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_currency"
        ));
        if ($currencies && $currencies['result'] == 'success') {
            $data = array();
            foreach ($currencies['object'] as $currency) {
                $currency_id = $currency['currency_id'];
                $currency_value = $currency['value'];
                $currency_mage = $this->_notice['config']['currencies'][$currency_id];
                $data[$currency_mage] = $currency_value;
            }
            $this->_process->currencyRate(array(
                $default_cur => $data
            ));
        }
        return;
    }

    /**
     * Process before import taxes
     */
    public function prepareImportTaxes() {
        parent::prepareImportTaxes();
        $tax_cus = $this->getTaxCustomerDefault();
        if ($tax_cus['result'] == 'success') {
            $this->taxCustomerSuccess(1, $tax_cus['mage_id']);
        }
    }

    /**
     * Query for get data of table convert to tax rule
     *
     * @return string
     */
    protected function _getTaxesMainQuery() {
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $query = "SELECT * FROM _DBPRF_tax_rule
                            WHERE tax_rule_id > {$id_src} ORDER BY tax_rule_id ASC LIMIT {$limit}
                ";
        return $query;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes) {
        $taxRateIds = $this->duplicateFieldValueFromList($taxes['object'], 'tax_rate_id');
        $tax_rate_id_con = $this->arrayToInCondition($taxRateIds);
        $taxClassIds = $this->duplicateFieldValueFromList($taxes['object'], 'tax_class_id');
        $tax_class_id_con = $this->arrayToInCondition($taxClassIds);
        $ext_query = array(
            'tax_class' => "SELECT * FROM _DBPRF_tax_class WHERE tax_class_id IN {$tax_class_id_con}",
            'tax_rates' => "SELECT * FROM _DBPRF_tax_rate WHERE tax_rate_id IN {$tax_rate_id_con}"
        );
        return $ext_query;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @param array $taxesExt : Data of connector return for query in function getTaxesExtQuery
     * @return array
     */
    protected function _getTaxesExtRelQuery($taxes, $taxesExt) {
        $taxZoneIds = $this->duplicateFieldValueFromList($taxesExt['object']['tax_rates'], 'geo_zone_id');
        $tax_zone_query = $this->arrayToInCondition($taxZoneIds);
        $ext_rel_query = array(
            'zones_to_geo_zones' => "SELECT gz.*, ztgz.*, z.name as zone_name, z.code as zone_code, c.iso_code_2, c.name as country_name
                                              FROM _DBPRF_geo_zone AS gz
                                                  LEFT JOIN _DBPRF_zone_to_geo_zone AS ztgz ON ztgz.geo_zone_id = gz.geo_zone_id
                                                  LEFT JOIN _DBPRF_zone AS z ON z.zone_id = ztgz.zone_id
                                                  LEFT JOIN _DBPRF_country AS c ON c.country_id = ztgz.country_id
                                              WHERE ztgz.geo_zone_id IN {$tax_zone_query}"
        );
        return $ext_rel_query;
    }

    /**
     * Get primary key of main tax table
     *
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return int
     */
    public function getTaxId($tax, $taxesExt) {
        return $tax['tax_rule_id'];
    }

    /**
     * Convert source data to data for import
     *
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return array
     */
    public function convertTax($tax, $taxesExt) {
        if (\LitExtension\CartMigration\Model\Custom::TAX_CONVERT) {
            return $this->_custom->convertTaxCustom($this, $tax, $taxesExt);
        }
        $tax_cus_ids = $tax_pro_ids = $tax_rate_ids = array();
        if ($tax_cus_default = $this->getMageIdTaxCustomer(1)) {
            $tax_cus_ids[] = $tax_cus_default;
        }
        $taxClass = $this->getRowFromListByField($taxesExt['object']['tax_class'], 'tax_class_id', $tax['tax_class_id']);
        $tax_pro_data = array(
            'class_name' => $taxClass['title']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if ($tax_pro_ipt['result'] == 'success') {
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($taxClass['tax_class_id'], $tax_pro_ipt['mage_id']);
        }
        $taxRates = $this->getRowFromListByField($taxesExt['object']['tax_rates'], 'tax_rate_id', $tax['tax_rate_id']);
        $taxZone = $this->getListFromListByField($taxesExt['object']['zones_to_geo_zones'], 'geo_zone_id', $taxRates['geo_zone_id']);
        foreach ($taxZone as $tax_zone) {
            if (!$tax_zone['iso_code_2']) {
                continue;
            }
            $tax_rate_data = array();
            $zone = $tax_zone['zone_code'];
            if ($tax_zone['zone_id'] == 0) {
                $zone = 'All States';
            }
            $code = $tax_zone['iso_code_2'] . "-" . $zone . "-" .$tax_zone['name'];
            $tax_rate_data['code'] = $this->createTaxRateCode($code);
            $tax_rate_data['tax_country_id'] = $tax_zone['iso_code_2'];
            if ($tax_zone['zone_id'] == 0) {
                $tax_rate_data['tax_region_id'] = 0;
            } else {
                $tax_rate_data['tax_region_id'] = $this->getRegionId($tax_zone['zone_name'], $tax_zone['iso_code_2']);
            }
            $tax_rate_data['zip_is_range'] = 0;
            $tax_rate_data['tax_postcode'] = "*";
            $tax_rate_data['rate'] = $taxRates['rate'];
            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
            if ($tax_rate_ipt['result'] == 'success') {
                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($taxClass['title'] . " - " . $taxRates['name']);
        $tax_rule_data['customer_tax_class_ids'] = $tax_cus_ids;
        $tax_rule_data['product_tax_class_ids'] = $tax_pro_ids;
        $tax_rule_data['tax_rate_ids'] = $tax_rate_ids;
        $tax_rule_data['priority'] = 0;
        $tax_rule_data['position'] = 0;
        $tax_rule_data['calculate_subtotal'] = false;
        $custom = $this->_custom->convertTaxCustom($this, $tax, $taxesExt);
        if ($custom) {
            $tax_rule_data = array_merge($tax_rule_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $tax_rule_data
        );
    }

    /**
     * Process before import manufacturers
     */
    public function prepareImportManufacturers() {
        parent::prepareImportManufacturers();
        $man_attr = $this->getManufacturerAttributeId($this->_notice['config']['attribute_set_id']);
        if ($man_attr['result'] == 'success') {
            $this->manAttrSuccess(1, $man_attr['mage_id']);
        }
    }

    /**
     * Query for get data for convert to manufacturer option
     *
     * @return string
     */
    protected function _getManufacturersMainQuery() {
        $id_src = $this->_notice['manufacturers']['id_src'];
        $limit = $this->_notice['setting']['manufacturers'];
        $query = "SELECT * FROM _DBPRF_manufacturer WHERE manufacturer_id > {$id_src} ORDER BY manufacturer_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers : Data of connector return for query function getManufacturersMainQuery
     * @return array
     */
    protected function _getManufacturersExtQuery($manufacturers) {
        return array();
    }

    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers : Data of connector return for query function getManufacturersMainQuery
     * @param array $manufacturersExt : Data of connector return for query function getManufacturersExtQuery
     * @return array
     */
    protected function _getManufacturersExtRelQuery($manufacturers, $manufacturersExt) {
        return array();
    }

    /**
     * Get primary key of source manufacturer
     *
     * @param array $manufacturer : One row of object in function getManufacturersMain
     * @param array $manufacturersExt : Data of function getManufacturersExt
     * @return int
     */
    public function getManufacturerId($manufacturer, $manufacturersExt) {
        return $manufacturer['manufacturer_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $manufacturer : One row of object in function getManufacturersMain
     * @param array $manufacturersExt : Data of function getManufacturersExt
     * @return array
     */
    public function convertManufacturer($manufacturer, $manufacturersExt) {
        if (\LitExtension\CartMigration\Model\Custom::MANUFACTURER_CONVERT) {
            return $this->_custom->convertManufacturerCustom($this, $manufacturer, $manufacturersExt);
        }
        $man_attr_id = $this->getMageIdManAttr(1);
        if (!$man_attr_id) {
            return array(
                'result' => 'error',
                'msg' => $this->consoleError("Could not create manufacturer attribute!")
            );
        }
        $manufacturer_data = array(
            'attribute_id' => $man_attr_id
        );
        $manufacturer_data['value']['option'] = array(
            0 => $manufacturer['name']
        );
        foreach ($this->_notice['config']['languages'] as $store_id) {
            $manufacturer_data['value']['option'][$store_id] = $manufacturer['name'];
        }
        $custom = $this->_custom->convertManufacturerCustom($this, $manufacturer, $manufacturersExt);
        if ($custom) {
            $manufacturer_data = array_merge($manufacturer_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $manufacturer_data
        );
    }

    /**
     * Query for get data of main table use import category
     *
     * @return string
     */
    protected function _getCategoriesMainQuery() {
        $id_src = $this->_notice['categories']['id_src'];
        $limit = $this->_notice['setting']['categories'];
        $query = "SELECT * FROM _DBPRF_category WHERE category_id > {$id_src} ORDER BY category_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories) {
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'category_id');
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'categories_description' => "SELECT * FROM _DBPRF_category_description WHERE category_id IN {$cat_id_con}"
        );
        return $ext_query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @param array $categoriesExt : Data of connector return for query function getCategoriesExtQuery
     * @return array
     */
    protected function _getCategoriesExtRelQuery($categories, $categoriesExt) {
        return array();
    }

    /**
     * Get primary key of source category
     *
     * @param array $category : One row of object in function getCategoriesMain
     * @param array $categoriesExt : Data of function getCategoriesExt
     * @return int
     */
    public function getCategoryId($category, $categoriesExt) {
        return $category['category_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $category : One row of object in function getCategoriesMain
     * @param array $categoriesExt : Data of function getCategoriesExt
     * @return array
     */
    public function convertCategory($category, $categoriesExt) {
        if (\LitExtension\CartMigration\Model\Custom::CATEGORY_CONVERT) {
            return $this->_custom->convertCategoryCustom($this, $category, $categoriesExt);
        }
        if ($category['parent_id'] == 0 || $category['parent_id'] == $category['category_id']) {
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($category['parent_id']);
            if (!$cat_parent_id) {
                $parent_ipt = $this->_importCategoryParent($category['parent_id']);
                if ($parent_ipt['result'] == 'error') {
                    return $parent_ipt;
                } else if ($parent_ipt['result'] == 'warning') {
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['category_id']} import failed. Error: Could not import parent category id = {$category['parent_id']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $catDesc = $this->getListFromListByField($categoriesExt['object']['categories_description'], 'category_id', $category['category_id']);
        $cat_name = $this->getRowValueFromListByField($catDesc, 'language_id', $this->_notice['config']['default_lang'], 'name');
        $cat_des = $this->getRowValueFromListByField($catDesc, 'language_id', $this->_notice['config']['default_lang'], 'description');
        $cat_meta_key = $this->getRowValueFromListByField($catDesc, 'language_id', $this->_notice['config']['default_lang'], 'meta_keyword');
        $cat_meta_des = $this->getRowValueFromListByField($catDesc, 'language_id', $this->_notice['config']['default_lang'], 'meta_description');
        $cat_data['name'] = $cat_name ? html_entity_decode($cat_name) : " ";
        $cat_data['description'] = html_entity_decode($cat_des);
        $cat_data['meta_keywords'] = $cat_meta_key;
        $cat_data['meta_description'] = $cat_meta_des;
        if ($category['image'] && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), $category['image'], 'catalog/category')) {
            $cat_data['image'] = $img_path;
        }
        $pCat = $this->_objectManager->create('Magento\Catalog\Model\Category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = $category['status'];
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = \Magento\Catalog\Model\Category::DM_PRODUCT;
        $multi_store = array();
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            $store_data = array();
            $store_name = $this->getRowValueFromListByField($catDesc, 'language_id', $lang_id, 'name');
            $store_des = $this->getRowValueFromListByField($catDesc, 'language_id', $lang_id, 'description');
            $store_meta_key = $this->getRowValueFromListByField($catDesc, 'language_id', $lang_id, 'meta_keyword');
            $store_meta_des = $this->getRowValueFromListByField($catDesc, 'language_id', $lang_id, 'meta_description');
            if ($lang_id != $this->_notice['config']['default_lang'] && $store_name) {
                $store_data['store_id'] = $store_id;
                $store_data['name'] = html_entity_decode($store_name);
                $store_data['description'] = html_entity_decode($store_des);
                $store_data['meta_keywords'] = $store_meta_key;
                $store_data['meta_description'] = $store_meta_des;
                $multi_store[] = $store_data;
            }
        }
        $cat_data['multi_store'] = $multi_store;
        if ($this->_seo) {
            $seo = $this->_seo->convertCategorySeo($this, $category, $categoriesExt);
            if ($seo) {
                $cat_data['seo_url'] = $seo;
            }
        }
        $custom = $this->_custom->convertCategoryCustom($this, $category, $categoriesExt);
        if ($custom) {
            $cat_data = array_merge($cat_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $cat_data
        );
    }

    /**
     * Process before import products
     */
    public function prepareImportProducts() {
        parent::prepareImportProducts();
        $this->_notice['extend']['website_ids'] = $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }

    /**
     * Query for get data of main table use for import product
     *
     * @return string
     */
    protected function _getProductsMainQuery() {
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $query = "SELECT * FROM _DBPRF_product WHERE product_id > {$id_src}  ORDER BY product_id ASC LIMIT {$limit}";
		//$query = "SELECT * FROM _DBPRF_product WHERE product_id = '50' AND product_id > {$id_src} ORDER BY product_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */
    protected function _getProductsExtQuery($products) {
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'product_id');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
			'product_option_variant_value' => "SELECT * FROM _DBPRF_product_option_variant_value WHERE product_id IN {$pro_ids_query}",
			//'product_option_variant_value' => "SELECT * FROM _DBPRF_product_option_variant_value WHERE  product_id IN {$pro_ids_query}",
            'product_option_variant' => "SELECT * FROM _DBPRF_product_option_variant WHERE product_id IN {$pro_ids_query}",
		
		
            'product_description' => "SELECT * FROM _DBPRF_product_description WHERE product_id IN {$pro_ids_query}",
            'product_discount' => "SELECT * FROM _DBPRF_product_discount WHERE product_id IN {$pro_ids_query}",
            'product_to_category' => "SELECT * FROM _DBPRF_product_to_category WHERE product_id IN {$pro_ids_query}",
            'product_image' => "SELECT * FROM _DBPRF_product_image WHERE product_id IN {$pro_ids_query}",
            'product_special' => "SELECT * FROM _DBPRF_product_special WHERE product_id IN {$pro_ids_query}",
            'product_option' => "SELECT * FROM _DBPRF_product_option WHERE product_id IN {$pro_ids_query}",
            'product_option_value' => "SELECT * FROM _DBPRF_product_option_value WHERE product_id IN {$pro_ids_query}",
            'product_attribute' => "SELECT * FROM _DBPRF_product_attribute WHERE product_id IN {$pro_ids_query}",
            'product_related' => "SELECT * FROM _DBPRF_product_related WHERE product_id IN {$pro_ids_query}"
        );
        return $ext_query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @param array $productsExt : Data of connector return for query function getProductsExtQuery
     * @return array
     */
    protected function _getProductsExtRelQuery($products, $productsExt) {
        $productOptionIds = $this->duplicateFieldValueFromList($productsExt['object']['product_option'], 'option_id');
        $productOptionValueIds = $this->duplicateFieldValueFromList($productsExt['object']['product_option_value'], 'option_value_id');
        $attributeIds = $this->duplicateFieldValueFromList($productsExt['object']['product_attribute'], 'attribute_id');
        $product_option_ids_query = $this->arrayToInCondition($productOptionIds);
        $product_option_value_ids_query = $this->arrayToInCondition($productOptionValueIds);
        $attribute_ids_query = $this->arrayToInCondition($attributeIds);
        $ext_rel_query = array(
            'option' => "SELECT o.*, od.* FROM `_DBPRF_option` as o LEFT JOIN _DBPRF_option_description as od ON od.option_id = o.option_id WHERE o.option_id IN {$product_option_ids_query}",
            'option_value' => "SELECT ov.*, ovd.* FROM _DBPRF_option_value as ov LEFT JOIN _DBPRF_option_value_description as ovd ON ovd.option_value_id = ov.option_value_id WHERE ov.option_value_id IN {$product_option_value_ids_query}",
            'attribute_description' => "SELECT * FROM _DBPRF_attribute_description WHERE attribute_id IN {$attribute_ids_query}"
        );
        return $ext_rel_query;
    }

    /**
     * Get primary key of source product main
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsMain
     * @return int
     */
    public function getProductId($product, $productsExt) {
        return $product['product_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsMain
     * @return array
     */
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	public function convertProduct($product, $productsExt){
        if (\LitExtension\CartMigration\Model\Custom::PRODUCT_CONVERT) {
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $pro_data = array();
        $pro_data_add = array();
        // Attribute
        $attr_pro = $this->getListFromListByField($productsExt['object']['product_attribute'], 'product_id', $product['product_id']);
        if($attr_pro) {
            $attributes = $this->duplicateFieldValueFromList($attr_pro, 'attribute_id');
            $entity_type_id = $this->_objectManager->create('Magento\Eav\Model\Entity')->setType(\Magento\Catalog\Model\Product::ENTITY)->getTypeId();
            $attribute_set_id = $this->_notice['config']['attribute_set_id'];
            $store_view = \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE;
            if($attributes) {
                foreach ($attributes as $row) {
                    $attr = $this->getListFromListByField($productsExt['object']['attribute_description'], 'attribute_id', $row);
                    $attr_val = $this->getListFromListByField($attr_pro, 'attribute_id', $attr[0]['attribute_id']);
                    $attr_import = $this->_makeAttributeImport($attr, $attr_val, $entity_type_id, $attribute_set_id, $store_view);
                    $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                    if (!$attr_after) {
                        return array(
                            'result' => "warning",
                            'msg' => $this->consoleWarning("Product Id = {$product['product_id']} import failed. Error: Product attribute could not create!")
                        );
                    }
                    $pro_data_add[$attr_after['attribute_id']]['value'] = isset($attr_after['option_ids']['option_0']) ? $attr_after['option_ids']['option_0'] : '';
                    $pro_data_add[$attr_after['attribute_id']]['backend_type'] = $attr_after['backend_type'];
                }
            }
        }
        //end Attr
        $type_id = \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE;
        $visibility = \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH;
        //$entity_type_id = $this->_objectManager->create('Magento\Eav\Model\Entity')->setType(\Magento\Catalog\Model\Product::ENTITY)->getTypeId();
        //$children_product = $this->getListFromListByField($productsExt['object']['options_bot'], 'product', $product['productId']);
        $children_product = $this->getListFromListByField($productsExt['object']['product_option'], 'product_id', $product['product_id']);
        $proOption = $this->getListFromListByField($productsExt['object']['product_option_value'], 'product_id', $product['product_id']);       
		if($children_product && $proOption){
            $type_id = 'configurable';
            $config_data = $this->_importChildrenProduct($product, $children_product, $productsExt);
            if($config_data['result'] != 'success'){
                return $config_data;
            }
            $pro_data = array_merge($config_data['data'], $pro_data);
        }
        $pro_data = array_merge($this->_convertProduct(false, $product, $productsExt, $visibility, $type_id, false, false),$pro_data);
        if ($pro_data_add) {
            $pro_data['add_data'] = $pro_data_add;
        }
		return array(
            'result' => 'success',
            'data' => $pro_data
        );
    }
    
    protected function _importChildrenProduct($parent_product, $children_products, $productsExt){
        $result = array();
        $entity_type_id = $this->_objectManager->create('Magento\Eav\Model\Entity')->setType(\Magento\Catalog\Model\Product::ENTITY)->getTypeId();
        $dataChildes = $attrMage = array();
		$proOption = $this->getListFromListByField($productsExt['object']['product_option_value'], 'product_id', $parent_product['product_id']);
        $attr = array();
		foreach($children_products as $keys => $children) {
			$proOptVal = $this->getListFromListByField($proOption, 'option_id', $children['option_id']);
			foreach($proOptVal as $key => $proOpt) {
				
				$attr[$keys][$children['option_id']][$key] = $proOpt['option_value_id'] ;
			}
		}
		if(!isset($attr[0]) && isset($attr[1])){
			$attr[0] = $attr[1];
			$attr[1] = array();
			//var_dump($attr[0]);
			//var_dump($attr[1]);exit;
		}
		
		foreach($attr[0] as $k => $children) {
			
			foreach($children as $child) {

				if(isset($attr[1])){
					
					foreach($attr[1] as $j => $childrens) {
						foreach($childrens as $childs) {

							$dataOpts = array();
							$option_collection = '';
							$dataTMP = array();
							$dataTMP1 = array();
							$proOpt = $this->getListFromListByField($productsExt['object']['option'], 'option_id',$j );
							$attr_name = $this->getRowValueFromListByField($proOpt, 'language_id', $this->_notice['config']['default_lang'], 'name');
							$attr_code = $this->joinTextToKey($attr_name, 27, '_');
							$proVal = $this->getListFromListByField($productsExt['object']['option_value'], 'option_value_id',$childs);
							$opt_name = $this->getRowValueFromListByField($proVal, 'language_id', $this->_notice['config']['default_lang'], 'name');
							$attr_import = $this->_makeAttributeImport1($attr_name, $attr_code, $opt_name, $entity_type_id, $this->_notice['config']['attribute_set_id']);
							if (!$attr_import) {
								return array(
									'result' => "warning",
									'msg' => $this->consoleWarning("Product Id = {$parent_product['productId']} import failed. Error: Product attribute could not create!")
								);
							}
							$dataOptAfterImport = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
							if (!$dataOptAfterImport) {
								return array(
									'result' => "warning",
									'msg' => $this->consoleWarning("Product Id = {$parent_product['productId']} import failed. Error: Product attribute could not create!")
								);
							}
							$dataTMP['attribute_id'] = $dataOptAfterImport['attribute_id'];
							$dataTMP['value_index'] = $dataOptAfterImport['option_ids']['option_0'];
							$dataOpts[] = $dataTMP;
							$attrMage[$dataOptAfterImport['attribute_id']]['attribute_label'] = $attr_name;
							$attrMage[$dataOptAfterImport['attribute_id']]['attribute_code'] = $dataOptAfterImport['attribute_code'];
							
							
							
							$proOpt1 = $this->getListFromListByField($productsExt['object']['option'], 'option_id',$k );
							$attr_name1 = $this->getRowValueFromListByField($proOpt1, 'language_id', $this->_notice['config']['default_lang'], 'name');
							$attr_code1 = $this->joinTextToKey($attr_name1, 27, '_');
							$proVal1 = $this->getListFromListByField($productsExt['object']['option_value'], 'option_value_id',$child);
							$opt_name1 = $this->getRowValueFromListByField($proVal1, 'language_id', $this->_notice['config']['default_lang'], 'name');
							$attr_import1 = $this->_makeAttributeImport1($attr_name1, $attr_code1, $opt_name1, $entity_type_id, $this->_notice['config']['attribute_set_id']);
							if (!$attr_import1) {
								return array(
									'result' => "warning",
									'msg' => $this->consoleWarning("Product Id = {$parent_product['productId']} import failed. Error: Product attribute could not create!")
								);
							}
							$dataOptAfterImport1 = $this->_process->attribute($attr_import1['config'], $attr_import1['edit']);
							if (!$dataOptAfterImport1) {
								return array(
									'result' => "warning",
									'msg' => $this->consoleWarning("Product Id = {$parent_product['productId']} import failed. Error: Product attribute could not create!")
								);
							}
							$dataTMP1['attribute_id'] = $dataOptAfterImport1['attribute_id'];
							$dataTMP1['value_index'] = $dataOptAfterImport1['option_ids']['option_0'];
							$dataOpts[] = $dataTMP1;
							$attrMage[$dataOptAfterImport1['attribute_id']]['attribute_label'] = $attr_name1;
							$attrMage[$dataOptAfterImport1['attribute_id']]['attribute_code'] = $dataOptAfterImport1['attribute_code'];
							
							
							
							
							//if ($option['option_name']) {
							$option_collection = $option_collection . ' - ' . $opt_name . ' - ' . $opt_name1;
							//}
			//                    }
			//                }
							$convertPro = $this->_convertProduct($children, $parent_product, $productsExt, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE, $option_collection, true);
							
							
							$pro_opt_val = $this->getRowFromListByField($productsExt['object']['product_option_value'], 'option_value_id',$childs);
							$pro_opt_val1 = $this->getRowFromListByField($productsExt['object']['product_option_value'], 'option_value_id',$child);
							
							$variant = $this->getListFromListByField($productsExt['object']['product_option_variant_value'], 'product_id',$parent_product['product_id'] );
							//$id_option = $this->getRowValueFromListByField($productsExt['object']['product_option_value'], 'option_value_id', $this->_notice['config']['default_lang'], 'name');
							$id_option = $pro_opt_val['product_option_value_id'];
							$id_option1 = $pro_opt_val1['product_option_value_id'];
							$info_variantt = $this->getRowValueFromListByField($variant, 'product_option_value_id',$id_option , 'product_option_variant_id');
							//$info_variantt1 = $this->getRowValueFromListByField($variant, 'product_option_value_id',$id_option1 , 'product_option_variant_id');
							
							
							$info_var = $this->getListFromListByField($variant, 'product_option_value_id',$id_option);
							$info_var1= $this->getListFromListByField($variant, 'product_option_value_id',$id_option1);
							foreach($info_var as $info){
								foreach($info_var1 as $info1){
									if($info['product_option_variant_id'] == $info1['product_option_variant_id'] ){
										$info_variantt = $info['product_option_variant_id'];
									}else{
										continue;
									}
								
								}
								
							}
							
							//var_dump($info_variant);exit;
							$info_variant =$this->getRowFromListByField($productsExt['object']['product_option_variant'], 'product_option_variant_id',$info_variantt );
							//$info_variant1 =$this->getRowFromListByField($productsExt['object']['product_option_variant'], 'product_option_variant_id',$info_variantt1 );
							//var_dump($info_variant);
							//var_dump($info_variant1);exit;
							$convertPro['sku'] = $this->createProductSku($info_variant['sku'], $this->_notice['config']['languages']);
							if(!$convertPro['sku']){
								$convertPro['sku'] = $this->createProductSku($parent_product['sku'], $this->_notice['config']['languages']);
							}
							//var_dump($info_variant['sku']);exit;
							//if($info_variant['stock']){
								$convertPro['stock_data'] = array(
									'is_in_stock' => 1,
									'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $info_variant['stock'] < 1) ? 0 : 1,
									'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $info_variant['stock'] < 1) ? 0 : 1,
									'qty' => $info_variant['stock'] 
								);
							//}
							
							$convertPro['weight'] = $info_variant['weight'] ;
							//if(!$convertPro['weight']){
								//$convertPro['weight'] =  $parent_product['weight'] ?  $parent_product['weight'] : 0;
							//}
							if ($info_variant['image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $info_variant['image'], 'catalog/product', false, true)) {
								$convertPro['image_import_path'] = array('path' => $image_path, 'label' => '');
							}
							/* elseif ($info_variant1['image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $info_variant1['image'], 'catalog/product', false, true)) {
								$convertPro['image_import_path'] = array('path' => $image_path, 'label' => '');
							} */
												
							
							$convertPro['price'] = $parent_product['price'] + $pro_opt_val['price'] + $pro_opt_val1['price'];
							//var_dump($info_variant);exit;
							$pro_import = $this->_process->product($convertPro);
							if ($pro_import['result'] !== 'success') {
								return array(
									'result' => "warning",
									'msg' => $this->consoleWarning("Product Id = {$parent_product['productId']} import failed. Error: Product children could not create!")
								);
							}
							$dataChildes[] = $pro_import['mage_id'];
							foreach ($dataOpts as $dataAttribute) {
								$this->setProAttrSelect($dataAttribute['attribute_id'], $pro_import['mage_id'], $dataAttribute['value_index']);
							}
						}
					}
				}
				else{
					//var_dump(1);exit;
					$dataOpts = array();
					$option_collection = '';
					$dataTMP1 = array();

					$proOpt1 = $this->getListFromListByField($productsExt['object']['option'], 'option_id',$k );
					$attr_name1 = $this->getRowValueFromListByField($proOpt1, 'language_id', $this->_notice['config']['default_lang'], 'name');
					$attr_code1 = $this->joinTextToKey($attr_name1, 27, '_');
					$proVal1 = $this->getListFromListByField($productsExt['object']['option_value'], 'option_value_id',$child);
					$opt_name1 = $this->getRowValueFromListByField($proVal1, 'language_id', $this->_notice['config']['default_lang'], 'name');
					$attr_import1 = $this->_makeAttributeImport1($attr_name1, $attr_code1, $opt_name1, $entity_type_id, $this->_notice['config']['attribute_set_id']);
					if (!$attr_import1) {
						return array(
							'result' => "warning",
							'msg' => $this->consoleWarning("Product Id = {$parent_product['productId']} import failed. Error: Product attribute could not create!")
						);
					}
					$dataOptAfterImport1 = $this->_process->attribute($attr_import1['config'], $attr_import1['edit']);
					if (!$dataOptAfterImport1) {
						return array(
							'result' => "warning",
							'msg' => $this->consoleWarning("Product Id = {$parent_product['productId']} import failed. Error: Product attribute could not create!")
						);
					}
					$dataTMP1['attribute_id'] = $dataOptAfterImport1['attribute_id'];
					$dataTMP1['value_index'] = $dataOptAfterImport1['option_ids']['option_0'];
					$dataOpts[] = $dataTMP1;
					$attrMage[$dataOptAfterImport1['attribute_id']]['attribute_label'] = $attr_name1;
					$attrMage[$dataOptAfterImport1['attribute_id']]['attribute_code'] = $dataOptAfterImport1['attribute_code'];
					
					
					
					
					//if ($option['option_name']) {
					$option_collection = $option_collection . ' - ' . $opt_name1;
					//}
	//                    }
	//                }
					$convertPro = $this->_convertProduct($children, $parent_product, $productsExt, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE, $option_collection, true);
					//$pro_opt_val = $this->getRowFromListByField($productsExt['object']['product_option_value'], 'option_value_id',$childs);
					$pro_opt_val1 = $this->getRowFromListByField($productsExt['object']['product_option_value'], 'option_value_id',$child);
					$convertPro['price'] = $parent_product['price'] + $pro_opt_val1['price'];
					
					$variant = $this->getListFromListByField($productsExt['object']['product_option_variant_value'], 'product_id',$parent_product['product_id'] );

					$id_option1 = $pro_opt_val1['product_option_value_id'];
					//$info_variant = $this->getRowValueFromListByField($variant, 'product_option_value_id',$id_option , 'product_option_variant_id');
					$info_variantt1 = $this->getRowValueFromListByField($variant, 'product_option_value_id',$id_option1 , 'product_option_variant_id');

					
					$info_variant1 =$this->getRowFromListByField($productsExt['object']['product_option_variant'], 'product_option_variant_id',$info_variantt1 );
					//var_dump($info_variant1);exit;
					if($info_variant1['sku']){
						
						$convertPro['sku'] = $this->createProductSku($info_variant1['sku'], $this->_notice['config']['languages']) ;
					}
					//if($info_variant1['stock']){

						$convertPro['stock_data'] = array(
							'is_in_stock' => 1,
							'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $info_variant1['stock'] < 1) ? 0 : 1,
							'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $info_variant1['stock'] < 1) ? 0 : 1,
							'qty' => $info_variant1['stock']  
						);
					//}
					//if($info_variant1['weight']){
						
						$convertPro['weight'] = $info_variant1['weight'];
					//}
					if ($info_variant1['image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $info_variant1['image'], 'catalog/product', false, true)) {
						$convertPro['image_import_path'] = array('path' => $image_path, 'label' => '');
					}
					
					
					
					$pro_import = $this->_process->product($convertPro);
					if ($pro_import['result'] !== 'success') {
						return array(
							'result' => "warning",
							'msg' => $this->consoleWarning("Product Id = {$parent_product['productId']} import failed. Error: Product children could not create!")
						);
					}
					$dataChildes[] = $pro_import['mage_id'];
					foreach ($dataOpts as $dataAttribute) {
						$this->setProAttrSelect($dataAttribute['attribute_id'], $pro_import['mage_id'], $dataAttribute['value_index']);
					}					
				}
			}
		}
		
        if($dataChildes){
            $result = $this->_createConfigProductData($dataChildes, $attrMage);
        }
        return array(
            'result' => 'success',
            'data' => $result
        );
    }
    
    protected function _makeAttributeImport1($attribute_name, $attribute_code, $option_name, $entity_type_id, $attribute_set_id, $type = 'select'){
        $multi_option = $multi_attr = $result = array();
        $multi_option[0] = $option_name;
        $multi_attr[0] = $attribute_name;
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => $attribute_code,
            'attribute_set_id' => $attribute_set_id,
            'frontend_input' => $type,
            'frontend_label' => $multi_attr,
            'is_visible_on_front' => 1,
            'is_global' => 1,
            'is_configurable' => true,
            'option' => array(
                'value' => array('option_0' => $multi_option)
            )
        );
        $edit = array(
            'is_global' => 1,
            'is_configurable' => true,
        );
        $result['config'] = $config;
        $result['edit'] = $edit;
        if(empty($result['config'])) return false;
        return $result;
    }

    protected function _createConfigProductData($dataChildes, $attrMage){
        $attribute_config = array();
        $result['associated_product_ids'] = $dataChildes;
        $i = 0;
        foreach ($attrMage as $key => $attribute) {
            $dad = array(
                'label' => $attribute['attribute_label'],
                'attribute_id' => $key,
                'code' => $attribute['attribute_code'],
                'position' => $i,
            );
            $attribute_config[$key] = $dad;
            $i++;
        }
        $result['configurable_attributes_data'] = $attribute_config;
        $result['can_save_configurable_attributes'] = 1;
        $result['affect_configurable_product_attributes'] = 1;
        return $result;
    }
    
    public function _convertProduct($parent_product, $product, $productsExt, $visibility, $type_id, $option_collection, $check_variant_pro = false){
        if (\LitExtension\CartMigration\Model\Custom::PRODUCT_CONVERT) {
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $categories = array();
        //$proCat = $this->getListFromListByField($productsExt['object']['cats_idx'], 'productId', $product['productId']);
        $proCat = $this->getListFromListByField($productsExt['object']['product_to_category'], 'product_id', $product['product_id']);
		if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['category_id']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $proDesc = $this->getListFromListByField($productsExt['object']['product_description'], 'product_id', $product['product_id']);
        $pro_desc_def = $this->getRowFromListByField($proDesc, 'language_id',$this->_notice['config']['default_lang']);
        if($check_variant_pro){
            $pro_data['name'] = html_entity_decode($pro_desc_def['name']) . $option_collection;
            //$pro_data['price'] = $product['price'] + $parent_product['option_price'];
        }else{
            //$pro_data['price'] = $product['price'] ? $product['price'] : 0;
            $pro_data['name'] = html_entity_decode($pro_desc_def['name']);;
            if ($product['image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['image'], 'catalog/product', false, true)) {
                $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
            }
            //$proImg = $this->getListFromListByField($productsExt['object']['products_images'], 'productId', $product['productId']);
			$proImg = $this->getListFromListByField($productsExt['object']['product_image'], 'product_id', $product['product_id']);
            if ($proImg) {
                foreach ($proImg as $gallery) {
                    if ($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $gallery['image'], 'catalog/product', false, true)) {
                        $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '');
                    }
                }
            }
        }
		$pro_data['price'] = $product['price'] ? $product['price'] : 0;
		//$proDesc = $this->getListFromListByField($productsExt['object']['inv_lang'], 'prod_master_id', $product['productId']);
        //$pro_data = array();
        $pro_data['type_id'] = $type_id;// \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $categories;
        //$pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        //$pro_data['sku'] = $this->createProductSku($product['productCode'], $this->_notice['config']['languages']);
        if($product['sku']) {
            $pro_data['sku'] = $this->createProductSku($product['sku'], $this->_notice['config']['languages']);
        } else {
            $pro_data['sku'] = $this->createProductSku($product['model'], $this->_notice['config']['languages']);
        }
		//$pro_data['name'] = $product['name'];
        $pro_data['description'] = $this->changeImgSrcInText(html_entity_decode($pro_desc_def['description']), $this->_notice['config']['add_option']['img_des']);
        //$pro_data['price'] = $product['price'] ? $product['price'] : 0;
        //$pro_data['special_price'] = $product['sale_price'];
        $proTier = $this->getListFromListByField($productsExt['object']['product_discount'], 'product_id', $product['product_id']);
        if($proTier) {
            foreach ($proTier as $row) {
                $value = array(
                    'website_id' => 0,
                    'cust_group' => isset($this->_notice['config']['customer_group'][$row['customer_group_id']]) ? $this->_notice['config']['customer_group'][$row['customer_group_id']] : \Magento\Customer\Model\Group::CUST_GROUP_ALL,
                    'price_qty' => $row['quantity'],
                    'price' => $row['price']
                );
                $tier_prices[] = $value;
            }
            $pro_data['tier_price'] = $tier_prices;
        }
        $proSpecial = $this->getRowFromListByField($productsExt['object']['product_special'], 'product_id', $product['product_id']);
        if ($proSpecial) {
            $pro_data['special_price'] = $proSpecial['price'];
            $pro_data['special_from_date'] = $this->_cookSpecialDate($proSpecial['date_start']);
            $pro_data['special_to_date'] = $this->_cookSpecialDate($proSpecial['date_end']);
        }

        //$pro_data['weight']   = $product['prodWeight'] ? $product['prodWeight']: 0 ;
		$pro_data['weight'] = $product['weight'] ? $product['weight'] : 0;
		$pro_data['status'] = ($product['status'] == 1) ? 1 : 2;
        if ($product['tax_class_id'] != 0 && $tax_pro_id = $this->getMageIdTaxProduct($product['tax_class_id'])) {
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = $product['date_added'];
        $pro_data['visibility'] = $visibility;// \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH;
        
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['quantity'] < 1) ? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['quantity'] < 1) ? 0 : 1,
            'qty' => $product['quantity']
        );
        
//        if ($product['image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['image'], 'catalog/product', false, true)) {
//            $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
//        }
//        $proImg = $this->getListFromListByField($productsExt['object']['products_images'], 'productId', $product['productId']);
//        if ($proImg) {
//            foreach ($proImg as $gallery) {
//                if ($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $gallery['filename'], 'catalog/product', false, true)) {
//                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '');
//                }
//            }
//        }
        if ($manufacture_mage_id = $this->getMageIdManufacturer($product['manufacturer_id'])) {
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
		$pro_data['meta_title'] = html_entity_decode($pro_desc_def['name']);
        $pro_data['meta_keyword'] = $pro_desc_def['meta_keyword'];
        $pro_data['meta_description'] = $pro_desc_def['meta_description'];
        $multi_store = array();
        
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            if ($lang_id != $this->_notice['config']['default_lang'] && $store_data_change = $this->getRowFromListByField($proDesc, 'language_id', $lang_id)) {
                $store_data = array();
                $store_data['name'] = html_entity_decode($store_data_change['name']);
                $store_data['description'] = $this->changeImgSrcInText(html_entity_decode($store_data_change['description']), $this->_notice['config']['add_option']['img_des']);
                //$store_data['short_description'] = $this->changeImgSrcInText(html_entity_decode($store_data_change['description']), $this->_notice['config']['add_option']['img_des']);
                $store_data['meta_title'] = html_entity_decode($store_data_change['name']);
                $store_data['meta_keyword'] = $store_data_change['meta_keyword'];
                $store_data['meta_description'] = $store_data_change['meta_description'];
                $store_data['store_id'] = $store_id;
                $multi_store[] = $store_data;
            }
        }
        $pro_data['multi_store'] = $multi_store;
        if ($this->_seo) {
            $seo = $this->_seo->convertProductSeo($this, $product, $productsExt);
            if ($seo) {
                $pro_data['seo_url'] = $seo;
            }
        }
        $custom = $this->_custom->convertProductCustom($this, $product, $productsExt);
        if($custom){
            $pro_data = array_merge($pro_data, $custom);
        }
        return $pro_data;
//        return array(
//            'result' => 'success',
//            'data' => $pro_data
//        );
    }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
    /* public function convertProduct($product, $productsExt) {
        if (\LitExtension\CartMigration\Model\Custom::PRODUCT_CONVERT) {
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $pro_data = array();
        $pro_data_add = array();
        // Attribute
        $attr_pro = $this->getListFromListByField($productsExt['object']['product_attribute'], 'product_id', $product['product_id']);
        if($attr_pro) {
            $attributes = $this->duplicateFieldValueFromList($attr_pro, 'attribute_id');
            $entity_type_id = $this->_objectManager->create('Magento\Eav\Model\Entity')->setType(\Magento\Catalog\Model\Product::ENTITY)->getTypeId();
            $attribute_set_id = $this->_notice['config']['attribute_set_id'];
            $store_view = \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE;
            if($attributes) {
                foreach ($attributes as $row) {
                    $attr = $this->getListFromListByField($productsExt['object']['attribute_description'], 'attribute_id', $row);
                    $attr_val = $this->getListFromListByField($attr_pro, 'attribute_id', $attr[0]['attribute_id']);
                    $attr_import = $this->_makeAttributeImport($attr, $attr_val, $entity_type_id, $attribute_set_id, $store_view);
                    $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                    if (!$attr_after) {
                        return array(
                            'result' => "warning",
                            'msg' => $this->consoleWarning("Product Id = {$product['product_id']} import failed. Error: Product attribute could not create!")
                        );
                    }
                    $pro_data_add[$attr_after['attribute_id']]['value'] = isset($attr_after['option_ids']['option_0']) ? $attr_after['option_ids']['option_0'] : '';
                    $pro_data_add[$attr_after['attribute_id']]['backend_type'] = $attr_after['backend_type'];
                }
            }
        }
        //end Attr
        $categories = array();
        $proCat = $this->getListFromListByField($productsExt['object']['product_to_category'], 'product_id', $product['product_id']);
        if ($proCat) {
            foreach ($proCat as $pro_cat) {
                $cat_id = $this->getMageIdCategory($pro_cat['category_id']);
                if ($cat_id) {
                    $categories[] = $cat_id;
                }
            }
        }
        $proDesc = $this->getListFromListByField($productsExt['object']['product_description'], 'product_id', $product['product_id']);
        $pro_desc_def = $this->getRowFromListByField($proDesc, 'language_id', $this->_notice['config']['default_lang']);
        $pro_data['type_id'] = \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $categories;
        if($product['sku']) {
            $pro_data['sku'] = $this->createProductSku($product['sku'], $this->_notice['config']['languages']);
        } else {
            $pro_data['sku'] = $this->createProductSku($product['model'], $this->_notice['config']['languages']);
        }
        $pro_data['name'] = html_entity_decode($pro_desc_def['name']);
        
        //url key
        $url_def = $this->_objectManager->create('Magento\Catalog\Model\Product')->formatUrlKey($pro_data['name']);
        $pro_data['url_key'] = $this->generateUrlKeyFromUrlKey($url_def);
        //eouk
        
        $pro_data['description'] = $this->changeImgSrcInText(html_entity_decode($pro_desc_def['description']), $this->_notice['config']['add_option']['img_des']);
        //$pro_data['short_description'] = $this->changeImgSrcInText(html_entity_decode($pro_desc_def['description']), $this->_notice['config']['add_option']['img_des']);
        if(isset($pro_desc_def['tag'])){
            $pro_data['tags'] = $pro_desc_def['tag'];
        }
        $pro_data['price'] = $product['price'] ? $product['price'] : 0;
        $proTier = $this->getListFromListByField($productsExt['object']['product_discount'], 'product_id', $product['product_id']);
        if($proTier) {
            foreach ($proTier as $row) {
                $value = array(
                    'website_id' => 0,
                    'cust_group' => isset($this->_notice['config']['customer_group'][$row['customer_group_id']]) ? $this->_notice['config']['customer_group'][$row['customer_group_id']] : \Magento\Customer\Model\Group::CUST_GROUP_ALL,
                    'price_qty' => $row['quantity'],
                    'price' => $row['price']
                );
                $tier_prices[] = $value;
            }
            $pro_data['tier_price'] = $tier_prices;
        }
        $proSpecial = $this->getRowFromListByField($productsExt['object']['product_special'], 'product_id', $product['product_id']);
        if ($proSpecial) {
            $pro_data['special_price'] = $proSpecial['price'];
            $pro_data['special_from_date'] = $this->_cookSpecialDate($proSpecial['date_start']);
            $pro_data['special_to_date'] = $this->_cookSpecialDate($proSpecial['date_end']);
        }
        $pro_data['product_has_weight'] = 1;
        $pro_data['weight'] = $product['weight'] ? $product['weight'] : 0;
        $pro_data['status'] = ($product['status'] == 1) ? 1 : 2;
        if ($product['tax_class_id'] != 0 && $tax_pro_id = $this->getMageIdTaxProduct($product['tax_class_id'])) {
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = $product['date_added'];
        $pro_data['visibility'] = \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH;
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['quantity'] < 1) ? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['quantity'] < 1) ? 0 : 1,
            'qty' => $product['quantity']
        );
        if ($manufacture_mage_id = $this->getMageIdManufacturer($product['manufacturer_id'])) {
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        if ($product['image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['image'], 'catalog/product', false, true)) {
            $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
        }
        $proImg = $this->getListFromListByField($productsExt['object']['product_image'], 'product_id', $product['product_id']);
        if ($proImg) {
            foreach ($proImg as $gallery) {
                if ($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $gallery['image'], 'catalog/product', false, true)) {
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '');
                }
            }
        }
        $pro_data['meta_title'] = html_entity_decode($pro_desc_def['name']);
        $pro_data['meta_keyword'] = $pro_desc_def['meta_keyword'];
        $pro_data['meta_description'] = $pro_desc_def['meta_description'];
        $multi_store = array();
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            if ($lang_id != $this->_notice['config']['default_lang'] && $store_data_change = $this->getRowFromListByField($proDesc, 'language_id', $lang_id)) {
                $store_data = array();
                $store_data['name'] = html_entity_decode($store_data_change['name']);
                $store_data['description'] = $this->changeImgSrcInText(html_entity_decode($store_data_change['description']), $this->_notice['config']['add_option']['img_des']);
                //$store_data['short_description'] = $this->changeImgSrcInText(html_entity_decode($store_data_change['description']), $this->_notice['config']['add_option']['img_des']);
                $store_data['meta_title'] = html_entity_decode($store_data_change['name']);
                $store_data['meta_keyword'] = $store_data_change['meta_keyword'];
                $store_data['meta_description'] = $store_data_change['meta_description'];
                $store_data['store_id'] = $store_id;
                $multi_store[] = $store_data;
            }
        }
        $pro_data['multi_store'] = $multi_store;
        if ($this->_seo) {
            $seo = $this->_seo->convertProductSeo($this, $product, $productsExt);
            if ($seo) {
                $pro_data['seo_url'] = $seo;
            }
        }
        $custom = $this->_custom->convertProductCustom($this, $product, $productsExt);
        if ($custom) {
            $pro_data = array_merge($pro_data, $custom);
        }
        if ($pro_data_add) {
            $pro_data['add_data'] = $pro_data_add;
        }
        return array(
            'result' => 'success',
            'data' => $pro_data
        );
    } */

    /**
     * Process after one product import successful
     *
     * @param int $product_mage_id : Id of product save successful to magento
     * @param array $data : Data of function convertProduct
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsExt
     * @return boolean
     */
    public function afterSaveProduct($product_mage_id, $data, $product, $productsExt) {
        if (parent::afterSaveProduct($product_mage_id, $data, $product, $productsExt)) {
            return;
        }
/*         $proAttr = $this->getListFromListByField($productsExt['object']['product_option'], 'product_id', $product['product_id']);
        $proOption = $this->getListFromListByField($productsExt['object']['product_option_value'], 'product_id', $product['product_id']);
        if ($proAttr) {
            $opt_data = array();
            $proOptId = $this->duplicateFieldValueFromList($proAttr, 'option_id');
            $typeSelect = array('drop_down','radio','checkbox','text');
            foreach ($proOptId as $pro_opt_id) {
                $proOpt = $this->getListFromListByField($productsExt['object']['option'], 'option_id', $pro_opt_id);
                $proOptVal = $this->getListFromListByField($proOption, 'option_id', $pro_opt_id);
                if (!$proOpt) {
					//print_r(1111);
                    continue;
                }
                $type = $this->getRowValueFromListByField($proOpt, 'language_id', $this->_notice['config']['default_lang'], 'type');
                $type_import = $this->_getOptionTypeByTypeSrc($type);
                $option = array(
                    'previous_group' => $this->_objectManager->create('Magento\Catalog\Model\Product\Option')->getGroupByType($type_import),
                    'type' => $type_import,
                    'is_require' => $this->getRowValueFromListByField($proAttr, 'option_id', $pro_opt_id, 'required'),
                    'title' => $this->getRowValueFromListByField($proOpt, 'language_id', $this->_notice['config']['default_lang'], 'name')
                );
                $values = array();
                if($proOptVal && in_array($type_import, $typeSelect)) {
                    foreach ($proOptVal as $pro_opt_val) {
                        $proVal = $this->getListFromListByField($productsExt['object']['option_value'], 'option_value_id', $pro_opt_val['option_value_id']);
                        $value = array(
                            //'option_type_id' => -1,
                            'title' => $this->getRowValueFromListByField($proVal, 'language_id', $this->_notice['config']['default_lang'], 'name'),
                            'price' => $pro_opt_val['price_prefix'] . $pro_opt_val['price'],
                            'price_type' => 'fixed',
                            'sku' => isset($pro_opt_val['sku']) ? $pro_opt_val['sku'] : '',
                        );
                        $values[] = $value;
                    }
                } else {
                    $option['price'] = 0;
                    $option['price_type'] = 'fixed';
                    $option['sku'] = '';
                }
                $option['values'] = $values ? $values : '';
                $opt_data[] = $option;
            }
            $this->importProductOption($product_mage_id, $opt_data);
        } */
        //Related product
        $relateProducts = $this->getListFromListByField($productsExt['object']['product_related'], 'product_id', $product['product_id']);
        if ($relateProducts) {
            $relate_products = $this->duplicateFieldValueFromList($relateProducts, 'related_id');
            $this->setProductRelation($product_mage_id, $relate_products, 1, true);
        }
    }

    /**
     * Query for get data of main table use for import customer
     *
     * @return string
     */
    protected function _getCustomersMainQuery() {
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $query = "SELECT * FROM _DBPRF_customer WHERE customer_id > {$id_src} ORDER BY customer_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers) {
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'customer_id');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'address' => "SELECT a.*, c.*, z.*, z.name as zone_name
                                            FROM _DBPRF_address AS a
                                                LEFT JOIN _DBPRF_country AS c ON a.country_id = c.country_id
                                                LEFT JOIN _DBPRF_zone AS z ON a.zone_id = z.zone_id
                                            WHERE a.customer_id IN {$customer_ids_query}"
        );
        return $ext_query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @param array $customersExt : Data of connector return for query function getCustomersExtQuery
     * @return array
     */
    protected function _getCustomersExtRelQuery($customers, $customersExt) {
        return array();
    }

    /**
     * Get primary key of source customer main
     *
     * @param array $customer : One row of object in function getCustomersMain
     * @param array $customersExt : Data of function getCustomersExt
     * @return int
     */
    public function getCustomerId($customer, $customersExt) {
        return $customer['customer_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $customer : One row of object in function getCustomersMain
     * @param array $customersExt : Data of function getCustomersExt
     * @return array
     */
    public function convertCustomer($customer, $customersExt) {
        if (\LitExtension\CartMigration\Model\Custom::CUSTOMER_CONVERT) {
            return $this->_custom->convertCustomerCustom($this, $customer, $customersExt);
        }
        $cus_data = array();
        if ($this->_notice['config']['add_option']['pre_cus']) {
            $cus_data['id'] = $customer['customer_id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = $customer['firstname'];
        $cus_data['lastname'] = $customer['lastname'];
        $cus_data['created_at'] = $customer['date_added'];
        //$cus_data['is_subscribed'] = $customer['newsletter'];
        $cus_data['group_id'] = isset($this->_notice['config']['customer_group'][$customer['customer_group_id']]) ? $this->_notice['config']['customer_group'][$customer['customer_group_id']] : 1;
        $custom = $this->_custom->convertCustomerCustom($this, $customer, $customersExt);
        if ($custom) {
            $cus_data = array_merge($cus_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $cus_data
        );
    }

    /**
     * Process after one customer import successful
     *
     * @param int $customer_mage_id : Id of customer import to magento
     * @param array $data : Data of function convertCustomer
     * @param array $customer : One row of object function getCustomersMain
     * @param array $customersExt : Data of function getCustomersExt
     * @return boolean
     */
    public function afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt) {
        if (parent::afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt)) {
            return;
        }
        if ($customer['newsletter']) {
            $this->subscribeCustomerById($customer_mage_id);
        }
        $this->_importCustomerRawPass($customer_mage_id, $customer['password'] . ":" . $customer['salt']);
        $cusAdd = $this->getListFromListByField($customersExt['object']['address'], 'customer_id', $customer['customer_id']);
        if ($cusAdd) {
            foreach ($cusAdd as $cus_add) {
                $address = array();
                $address['firstname'] = $cus_add['firstname'];
                $address['lastname'] = $cus_add['lastname'];
                $address['country_id'] = $cus_add['iso_code_2'];
                $address['street'] = $cus_add['address_1'] . "\n" . $cus_add['address_2'];
                $address['postcode'] = $cus_add['postcode'] ? $cus_add['postcode'] : 0;
                $address['city'] = $cus_add['city'];
                $address['telephone'] = $customer['telephone'];
                $address['company'] = $cus_add['company'];
                $address['fax'] = $customer['fax'];
                if ($cus_add['zone_id'] != 0) {
                    $region_id = $this->getRegionId($cus_add['zone_name'], $cus_add['iso_code_2']);
                    if ($region_id) {
                        $address['region_id'] = $region_id;
                    }
                    $address['region'] = $cus_add['zone_name'];
                } else {
                    $address['region'] = $cus_add['address_2'];
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if ($address_ipt['result'] == 'success' && $cus_add['address_id'] == $customer['address_id']) {
                    try {
                        $cus = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($customer_mage_id);
                        $cus->setDefaultBilling($address_ipt['mage_id']);
                        $cus->setDefaultShipping($address_ipt['mage_id']);
                        $cus->save();
                    } catch (\Exception $e) {
                        
                    }
                }
            }
        }
    }

    /**
     * Get data use for import order
     *
     * @return array : Response of connector
     */
    protected function _getOrdersMainQuery() {
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $query = "SELECT * FROM `_DBPRF_order` WHERE order_id > {$id_src} AND order_status_id != 0 ORDER BY order_id ASC LIMIT {$limit}";
        //var_dump($query);exit;
		return $query;
    }

    /**
     * Get data relation use for import order
     *
     * @param array $orders : Data of function getOrdersMain
     * @return array : Response of connector
     */
    protected function _getOrdersExtQuery($orders) {
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'order_id');
        $bilCountry = (array) $this->duplicateFieldValueFromList($orders['object'], 'payment_country_id');
        $delCountry = (array) $this->duplicateFieldValueFromList($orders['object'], 'shipping_country_id');
        $countries = array_unique(array_merge($bilCountry, $delCountry));
        $countries = $this->_flitArrayNum($countries);
        $bilState = (array) $this->duplicateFieldValueFromList($orders['object'], 'payment_zone_id');
        $delState = (array) $this->duplicateFieldValueFromList($orders['object'], 'shipping_zone_id');
        $states = array_unique(array_merge($bilState, $delState));
        $states = $this->_flitArrayNum($states);
        $order_ids_query = $this->arrayToInCondition($orderIds);
        $countries_query = $this->arrayToInCondition($countries);
        $states_query = $this->arrayToInCondition($states);
        $ext_query = array(
            'order_product' => "SELECT * FROM _DBPRF_order_product WHERE order_id IN {$order_ids_query}",
            'order_option' => "SELECT * FROM _DBPRF_order_option WHERE order_id IN {$order_ids_query}",
            'order_history' => "SELECT *  FROM _DBPRF_order_history WHERE order_id IN {$order_ids_query} ORDER BY order_history_id DESC",
            'order_total' => "SELECT * FROM _DBPRF_order_total WHERE order_id IN {$order_ids_query}",
            'currency' => "SELECT currency_id, code FROM _DBPRF_currency",
            'country' => "SELECT * FROM _DBPRF_country WHERE country_id IN {$countries_query}",
            'zone' => "SELECT * FROM _DBPRF_zones WHERE zone_id IN {$states_query}"
        );
        return $ext_query;
    }
    
    /**
     * Query for get data relation use for import order
     *
     * @param array $orders : Data of connector return for query function getOrdersMainQuery
     * @param array $ordersExt : Data of connector return for query function getOrdersExtQuery
     * @return array
     */
    protected function _getOrdersExtRelQuery($orders, $ordersExt){
        return array();
    }

    /**
     * Get primary key of source order main
     *
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return int
     */
    public function getOrderId($order, $ordersExt) {
        return $order['order_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return array
     */
    public function convertOrder($order, $ordersExt) {
        if (\LitExtension\CartMigration\Model\Custom::ORDER_CONVERT) {
            return $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        }
        $data = array();

        $address_billing['firstname'] = $order['payment_firstname'];
        $address_billing['lastname'] = $order['payment_lastname'];
        $address_billing['company'] = $order['payment_company'];
        $address_billing['email'] = $order['email'];
        $address_billing['street'] = $order['payment_address_1'] . "\n" . $order['payment_address_2'];
        $address_billing['city'] = $order['payment_city'];
        $address_billing['postcode'] = $order['payment_postcode'];
        $bil_country = $this->getRowValueFromListByField($ordersExt['object']['country'], 'country_id', $order['payment_country_id'], 'iso_code_2');
        $address_billing['country_id'] = $bil_country;
        if (is_numeric($order['payment_zone_id'])) {
            $billing_state = $this->getRowValueFromListByField($ordersExt['object']['zone'], 'zone_id', $order['payment_zone_id'], 'name');
            if (!$billing_state) {
                $billing_state = $order['payment_zone'];
            }
        } else {
            $billing_state = $order['payment_zone'];
        }
        $billing_region_id = $this->getRegionId($billing_state, $address_billing['country_id']);
        if ($billing_region_id) {
            $address_billing['region_id'] = $billing_region_id;
        } else {
            $address_billing['region'] = $billing_state;
        }
        $address_billing['telephone'] = $order['telephone'];

        $address_shipping['firstname'] = $order['shipping_firstname'];
        $address_shipping['lastname'] = $order['shipping_lastname'];
        $address_shipping['company'] = $order['shipping_company'];
        $address_shipping['email'] = $order['email'];
        $address_shipping['street'] = $order['shipping_address_1'] . "\n" . $order['shipping_address_2'];
        $address_shipping['city'] = $order['shipping_city'];
        $address_shipping['postcode'] = $order['shipping_postcode'];
        $del_country = $this->getRowValueFromListByField($ordersExt['object']['country'], 'country_id', $order['shipping_country_id'], 'iso_code_2');
        $address_shipping['country_id'] = $del_country;
        if (is_numeric($order['shipping_zone_id'])) {
            $shipping_state = $this->getRowValueFromListByField($ordersExt['object']['zone'], 'zone_id', $order['shipping_zone_id'], 'name');
            if (!$shipping_state) {
                $shipping_state = $order['shipping_zone'];
            }
        } else {
            $shipping_state = $order['shipping_zone'];
        }
        $shipping_region_id = $this->getRegionId($shipping_state, $address_shipping['country_id']);
        if ($shipping_region_id) {
            $address_shipping['region_id'] = $shipping_region_id;
        } else {
            $address_shipping['region'] = $shipping_state;
        }
        $address_shipping['telephone'] = $order['telephone'];

        $orderPro = $this->getListFromListByField($ordersExt['object']['order_product'], 'order_id', $order['order_id']);
        $orderProOpt = $this->getListFromListByField($ordersExt['object']['order_option'], 'order_id', $order['order_id']);
        $carts = array();
        $sum_qty = 0;
        $orderTotal = $this->getListFromListByField($ordersExt['object']['order_total'], 'order_id', $order['order_id']);
        $ot_coupon = $this->getRowValueFromListByField($orderTotal, 'code', 'coupon', 'value');
        $ot_voucher = $this->getRowValueFromListByField($orderTotal, 'code', 'voucher', 'value');
        $discount_amount = 0;
        if ($ot_coupon) {
            $discount_amount += $ot_coupon;
        }
        if ($ot_voucher) {
            $discount_amount += $ot_voucher;
        }
        $discount = abs($discount_amount);
        foreach ($orderPro as $item) {
            $sum_qty += $item['quantity'];
        }
        foreach ($orderPro as $order_pro) {
            $cart = array();
            $product_id = $this->getMageIdProduct($order_pro['product_id']);
            if ($product_id) {
                $cart['product_id'] = $product_id;
            }
            $cart['product_type'] = \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE;
            $cart['name'] = $order_pro['name'];
            $cart['sku'] = $order_pro['model'];
            $cart['price'] = $order_pro['total'] / $order_pro['quantity'];
            $cart['original_price'] = $order_pro['price'];
            //$cart['tax_amount'] = ($order_pro['total'] - ($discount / $sum_qty * $order_pro['quantity'])) * $order_pro['tax'] / 100;
            //$cart['tax_percent'] = $order_pro['tax'];
            $cart['discount_amount'] = $discount / $sum_qty * $order_pro['quantity'];
            $cart['qty_ordered'] = $order_pro['quantity'];
            $cart['row_total'] = $order_pro['total'];
            if ($orderProOpt) {
                $listOpt = $this->getListFromListByField($orderProOpt, 'order_product_id', $order_pro['order_product_id']);
                if ($listOpt) {
                    $product_opt = array();
                    $options = array();
                    foreach ($listOpt as $list_opt) {
                        $option = array(
                            'label' => $list_opt['name'],
                            'value' => $list_opt['value'],
                            'print_value' => $list_opt['value'],
                            'option_id' => 'option_' . $list_opt['product_option_id'],
                            'option_type' => $list_opt['type'],
                            'option_value' => 0,
                            'custom_view' => false
                        );
                        $options[] = $option;
                    }
                    $product_opt = array('options' => $options);
                    $cart['product_options'] = json_encode($product_opt);
                }
            }
            $carts[] = $cart;
        }

        $customer_id = $this->getMageIdCustomer($order['customer_id']);
        $orderStatus = $this->getListFromListByField($ordersExt['object']['order_history'], 'order_id', $order['order_id']);
        $order_status = isset($orderStatus[0]) ? $orderStatus[0] : 'canceled';
        $order_status_id = isset($orderStatus[0]) ? $orderStatus[0]['order_status_id'] : 7;
        $ot_shipping = $this->getRowValueFromListByField($orderTotal, 'code', 'shipping', 'value');
        $ot_shipping_desc = $this->getRowValueFromListByField($orderTotal, 'code', 'shipping', 'title');
        $ot_subtotal = $this->getRowValueFromListByField($orderTotal, 'code', 'sub_total', 'value');
        $ot_total = $this->getRowValueFromListByField($orderTotal, 'code', 'total', 'value');
        $ot_tax = $this->_getOrderTaxValueFromListByCode($orderTotal, 'tax');
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);

        $order_data = array();
        $order_data['store_id'] = $store_id;
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $order['email'];
        $order_data['customer_firstname'] = $order['firstname'];
        $order_data['customer_lastname'] = $order['lastname'];
        $order_data['customer_group_id'] = 1;
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = isset($this->_notice['config']['order_status'][$order_status_id]) ? $this->_notice['config']['order_status'][$order_status_id] : 'canceled';
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($ot_subtotal);
        $order_data['base_subtotal'] = $order_data['subtotal'];
        $order_data['shipping_amount'] = $ot_shipping;
        $order_data['base_shipping_amount'] = $ot_shipping;
        $order_data['base_shipping_invoiced'] = $ot_shipping;
        $order_data['shipping_description'] = $ot_shipping_desc;
        if ($ot_tax) {
            $order_data['tax_amount'] = $ot_tax;
            $order_data['base_tax_amount'] = $ot_tax;
        }
        $order_data['discount_amount'] = $discount_amount;
        $order_data['base_discount_amount'] = $discount_amount;
        $order_data['grand_total'] = $this->incrementPriceToImport($ot_total);
        $order_data['base_grand_total'] = $order_data['grand_total'];
        $order_data['base_total_invoiced'] = $order_data['grand_total'];
        //$order_data['total_paid'] = $order_data['grand_total'];
        //$order_data['base_total_paid'] = $order_data['grand_total'];
        $order_data['base_to_global_rate'] = true;
        $order_data['base_to_order_rate'] = true;
        $order_data['store_to_base_rate'] = true;
        $order_data['store_to_order_rate'] = true;
        $order_data['base_currency_code'] = $store_currency['base'];
        $order_data['global_currency_code'] = $store_currency['base'];
        $order_data['store_currency_code'] = $store_currency['base'];
        $order_data['order_currency_code'] = $store_currency['base'];
        $order_data['created_at'] = $order['date_added'];

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['order_id'];
        $custom = $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }

    /**
     * Process after one order save successful
     *
     * @param int $order_mage_id : Id of order import to magento
     * @param array $data : Data of function convertOrder
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return boolean
     */
    public function afterSaveOrder($order_mage_id, $data, $order, $ordersExt) {
        if (parent::afterSaveOrder($order_mage_id, $data, $order, $ordersExt)) {
            return;
        }
        $orderStatus = $this->getListFromListByField($ordersExt['object']['order_history'], 'order_id', $order['order_id']);
        $orderStatus = array_reverse($orderStatus);
        foreach ($orderStatus as $key => $order_status) {
            $order_status_data = array();
            $order_status_id = $order_status['order_status_id'];
            $order_status_data['status'] = isset($this->_notice['config']['order_status'][$order_status_id]) ? $this->_notice['config']['order_status'][$order_status_id] : 'canceled';
            if ($order_status_data['status']) {
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            if ($key == 0) {
                $order_status_data['comment'] = "<b>Reference order #" . $order['order_id'] . "</b><br /><b>Payment method: </b>" . $order['payment_method'] . "<br /><b>Shipping method: </b> " . $data['order']['shipping_description'] . "<br /><br />" . $order['comment'];
            } else {
                $order_status_data['comment'] = $order_status['comment'];
            }
            $order_status_data['is_customer_notified'] = 1;
            $order_status_data['updated_at'] = $order_status['date_added'];
            $order_status_data['created_at'] = $order_status['date_added'];
            $this->_process->ordersComment($order_mage_id, $order_status_data);
        }
    }

    /**
     * Query for get main data use for import review
     *
     * @return string
     */
    protected function _getReviewsMainQuery() {
        $id_src = $this->_notice['reviews']['id_src'];
        $limit = $this->_notice['setting']['reviews'];
        $query = "SELECT * FROM _DBPRF_review WHERE review_id > {$id_src} ORDER BY review_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @return array
     */
    protected function _getReviewsExtQuery($reviews) {
        return array();
    }

    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @param array $reviewsExt : Data of connector return for query function getReviewsExtQuery
     * @return array
     */
    protected function _getReviewsExtRelQuery($reviews, $reviewsExt) {
        return array();
    }

    /**
     * Get primary key of source review main
     *
     * @param array $review : One row of object in function getReviewsMain
     * @param array $reviewsExt : Data of function getReviewsExt
     * @return int
     */
    public function getReviewId($review, $reviewsExt) {
        return $review['review_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $review : One row of object in function getReviewsMain
     * @param array $reviewsExt : Data of function getReviewsExt
     * @return array
     */
    public function convertReview($review, $reviewsExt) {
        if (\LitExtension\CartMigration\Model\Custom::REVIEW_CONVERT) {
            return $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        }
        $product_mage_id = $this->getMageIdProduct($review['product_id']);
        if (!$product_mage_id) {
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['review_id']} import failed. Error: Product Id = {$review['product_id']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = ($review['status'] == 0) ? 3 : 1;
        $data['title'] = " ";
        $data['detail'] = $review['text'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getMageIdCustomer($review['customer_id'])) ? $this->getMageIdCustomer($review['customer_id']) : null;
        $data['nickname'] = $review['author'];
        $data['rating'] = $review['rating'];
        $data['created_at'] = $review['date_added'];
        $data['review_id_import'] = $review['review_id'];
        $custom = $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }

############################################################ Extend function ##################################

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_category WHERE category_id = {$parent_id}"
        ));
        if(!$categories || $categories['result'] != 'success'){
            return $this->errorConnector(true);
        }
        $categoriesExt = $this->getCategoriesExt($categories);
        if($categoriesExt['result'] != 'success'){
            return $categoriesExt;
        }
        $category = $categories['object'][0];
        $convert = $this->convertCategory($category, $categoriesExt);
        if($convert['result'] != 'success'){
            return array(
                'result' => 'warning',
            );
        }
        $data = $convert['data'];
        $category_ipt = $this->_process->category($data);
        if($category_ipt['result'] == 'success'){
            $this->categorySuccess($parent_id, $category_ipt['mage_id']);
            $this->afterSaveCategory($category_ipt['mage_id'], $data, $category, $categoriesExt);
        } else {
            $category_ipt['result'] = 'warning';
        }
        return $category_ipt;
    }

    /**
     * Get array value is number in array 2D
     */
    protected function _flitArrayNum($array) {
        $data = array();
        foreach ($array as $value) {
            if (is_numeric($value)) {
                $data[] = $value;
            }
        }
        return $data;
    }

    protected function _getOptionTypeByTypeSrc($type_name) {
        $types = array(
            'select' => 'drop_down',
            'text' => 'field',
            'radio' => 'radio',
            'checkbox' => 'checkbox',
            'file' => 'file',
            'textarea' => 'area',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'date_time'
        );
        return isset($types[$type_name]) ? $types[$type_name] : false;
    }

    protected function _updateProductOptionStoreView($product_id, $options, $pre_options, $store_id) {
        $mage_product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
        $mage_option = $mage_product->getProductOptionsCollection();
        if (isset($mage_option)) {
            $customOptions = array();
            foreach ($mage_option as $o) {
                $title = $o->getTitle();
                $cos = array();
                $co = array();
                foreach ($pre_options as $key => $pre_option) {
                    if ($title == $pre_option['title']) {
                        $o->setProductSku($mage_product->getSku());
                        $o->setTitle($options[$key]['title']);
                        $o->setType($pre_option['type']);
                        $o->setIsRequire($pre_option['is_require']);
                        if($options[$key]['values']) {
                            $option_value = $o->getValuesCollection();
                            foreach ($option_value as $v) {
                                $value_title = $v->getTitle();
                                foreach ($pre_option['values'] as $k => $pre_value) {
                                        if ($value_title == $pre_value['title']) {
                                            $v->setTitle($options[$key]['values'][$k]['title']);
                                            $v->setOptionId($o->getOptionId());
                                            $cos[] = $v->toArray($co);
                                        }
                                }
                            }
                        }
                    }
                }
                $o->setData("values", $cos);
                $customOptions[] = $o;
            }
            $mage_product->setOptions($customOptions);
            $mage_product->setStoreId($store_id);
            $mage_product->save();
        }
    }

    protected function _getOrderTaxValueFromListByCode($list, $code) {
        $result = 0;
        if ($list) {
            foreach ($list as $row) {
                if ($row['code'] == $code) {
                    $result += $row['value'];
                }
            }
        }
        return $result;
    }
    
    protected function _makeAttributeImport($attribute, $option, $entity_type_id, $attribute_set_id, $store_view) {
        $attr_des = array();
        $attr_des[] = $this->getRowValueFromListByField($attribute, 'language_id', $this->_notice['config']['default_lang'], 'name');
        $attr_name = $this->joinTextToKey($attr_des[0], 30, '_');
        $opt_des = array();
        $opt_des[] = $this->getRowValueFromListByField($option, 'language_id', $this->_notice['config']['default_lang'], 'text');
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            if($lang_id == $this->_notice['config']['default_lang']) {continue;}
            $opt_des[$store_id] = $this->getRowValueFromListByField($option, 'language_id', $lang_id, 'text');
            $attr_des[$store_id] = $this->getRowValueFromListByField($attribute, 'language_id', $lang_id, 'name');
        }
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => $attr_name,
            'attribute_set_id' => $attribute_set_id,
            'frontend_input' => 'select',
            'frontend_label' =>  $attr_des,
            'is_visible_on_front' => 1,
            'is_global' => $store_view,
            'option' => array(
                'value' => array('option_0' => $opt_des)
            )
        );
        $edit = array(
            'is_global' => $store_view,
        );
        $result['config'] = $config;
        $result['edit'] = $edit;
        return $result;
    }

    /**
     * TODO: CRON
     */

    public function getAllTaxes()
    {
        if(!$this->_notice['config']['import']['taxes']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_tax_rule ORDER BY tax_rule_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllManufacturers()
    {
        if(!$this->_notice['config']['import']['manufacturers']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_manufacturer ORDER BY manufacturer_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllCategories()
    {
        if(!$this->_notice['config']['import']['categories']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_category ORDER BY category_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllProducts()
    {
        if(!$this->_notice['config']['import']['products']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_product ORDER BY product_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllCustomers()
    {
        if(!$this->_notice['config']['import']['customers']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_customer ORDER BY customer_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllOrders()
    {
        if(!$this->_notice['config']['import']['orders']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM `_DBPRF_order` WHERE order_status_id != 0 ORDER BY order_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllReviews()
    {
        if(!$this->_notice['config']['import']['reviews']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_review ORDER BY review_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

}
