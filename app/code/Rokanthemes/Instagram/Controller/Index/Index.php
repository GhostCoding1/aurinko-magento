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
namespace Rokanthemes\Instagram\Controller\Index;

use Magento\Framework\App\RequestInterface;

class Index extends \Magento\Framework\App\Action\Action
{

    public function execute() {
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();  
    }

}