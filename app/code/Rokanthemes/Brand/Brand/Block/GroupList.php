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
namespace Rokanthemes\Brand\Block;

class GroupList extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Rokanthemes\Brand\Helper\Data
     */
    protected $_brandHelper;

    /**
     * @var \Rokanthemes\Brand\Model\Brand
     */
    protected $_group;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context      
     * @param \Magento\Framework\Registry                      $registry     
     * @param \Rokanthemes\Brand\Helper\Data                           $brandHelper  
     * @param \Rokanthemes\Brand\Model\Group                           $group        
     * @param \Magento\Store\Model\StoreManagerInterface       $storeManager 
     * @param array                                            $data         
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Rokanthemes\Brand\Helper\Data $brandHelper,
        \Rokanthemes\Brand\Model\Group $group,
        array $data = []
        ) { 
        $this->_group = $group;
        $this->_coreRegistry = $registry;
        $this->_brandHelper = $brandHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        if(!$this->getConfig('general_settings/enable')) return;
        parent::_construct();
    }

    public function getGroupList(){
        $collection = $this->_group->getCollection()
        ->addFieldToFilter('status',1)
        ->addFieldToFilter('shown_in_sidebar',1)
        ->setOrder('position','ASC');
        return $collection;
    }
    public function getConfig($key, $default = '')
    {
        $result = $this->_brandHelper->getConfig($key);
        if(!$result){

            return $default;
        }
        return $result;
    }
}