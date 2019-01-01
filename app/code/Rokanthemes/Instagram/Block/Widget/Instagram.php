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
namespace Rokanthemes\Instagram\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class Instagram extends Template  implements BlockInterface
{
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setTemplate('widget/instagram.phtml');
    }
    

}