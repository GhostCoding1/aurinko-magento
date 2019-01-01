<?php
/**
 * Rokanthemes Infotech
 * Rokanthemes Instagram Extension
 * 
 * @category   Rokanthemes
 * @package    Rokanthemes_Instagram
 * @copyright  Copyright © 2006-2016 Rokanthemes (https://www.rokanthemesinfotech.com)
 * @license    https://www.rokanthemesinfotech.com/magento-extension-license/
 */
namespace Rokanthemes\Instagram\Block;

use Magento\Framework\View\Element\Template;

class Instagram extends Template {
    
    
    protected function _prepareLayout() {
        
        parent::_prepareLayout();
        $this->pageConfig->getTitle()->set(__('Instagram'));
    }
    

}