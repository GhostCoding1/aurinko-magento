<?php
namespace Piimega\CheckoutFinland\Block\Methods;

use \Magento\Framework\View\Element\Template;

/**
 * Block for rendering Checkout Finland payment methods xml data
 */
class Checkoutfinland extends Template{

    /**
     * @var array
     */
    protected $_methodsData = [];

    /**
     * @param array $data
     * @return $this
     */
    public function setMethodsData($data){
        $this->_methodsData = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getMethods(){
        return $this->_methodsData;
    }

    /**
     * @return  string
     */
    public function getCancelUrl(){
        return $this->_urlBuilder->getUrl('checkoutfinland/payment/cancel', ['_secure' => true]);
    }
}