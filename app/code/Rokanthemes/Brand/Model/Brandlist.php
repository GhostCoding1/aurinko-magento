<?php
/**
 * Blueskytechco
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Blueskytechco.com license that is
 * available through the world-wide-web at this URL:
 * http://www.blueskytechco.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Blueskytechco
 * @package    Rokanthemes_Brand
 * @copyright  Copyright (c) 2014 Blueskytechco (http://www.blueskytechco.com/)
 * @license    http://www.blueskytechco.com/LICENSE-1.0.html
 */
namespace Rokanthemes\Brand\Model;

class Brandlist extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    protected  $_brand;
    
    /**
     * 
     * @param \Rokanthemes\Brand\Model\Brand $brand
     */
    public function __construct(
        \Rokanthemes\Brand\Model\Brand $brand
        ) {
        $this->_brand = $brand;
    }
    
    
    /**
     * Get Gift Card available templates
     *
     * @return array
     */
    public function getAvailableTemplate()
    {
        $brands = $this->_brand->getCollection()
        ->addFieldToFilter('status', '1');
        $listBrand = array();
        foreach ($brands as $brand) {
            $listBrand[] = array('label' => $brand->getName(),
                'value' => $brand->getId());
        }
        return $listBrand;
    }

    /**
     * Get model option as array
     *
     * @return array
     */
    public function getAllOptions($withEmpty = true)
    {
        $options = array();
        $options = $this->getAvailableTemplate();

        if ($withEmpty) {
            array_unshift($options, array(
                'value' => '',
                'label' => '-- Please Select --',
                ));
        }
        return $options;
    }
}