<?php
namespace Piimega\CheckoutFinland\Controller\Result;

/**
 * class Piimega\CheckoutFinland\Controller\Result\Cancel
 *
 * Used for cancellation requests coming from Checkout Finland, while the class 
 * Piimega\CheckoutFinland\Controller\Payment\Cancel used for local cancellation requests. The main difference between 
 * these two scenarios is in the method of obtaining order (here from request params) and request params validation
 */
class Cancel extends \Piimega\CheckoutFinland\Controller\Result\AbstractAction{

    /**
     * @var string
     */
    protected $_statusCodeGroup = \Piimega\CheckoutFinland\Model\Checkoutfinland::STATUS_CANCELED;

    /**
     * {@inheritdoc}
     */
    public function execute(){
        parent::execute();
        if($this->_validateRequestParams() && $this->_extractRequestedOrder()){
            if(!$this->_cancelOrder(__('Customer canceled payment'))){
                $this->_logger->critical(get_class($this)
                    .'Attempt to cancel order which cannot be canceled. Order ID:' .$this->_order->getId());
            }
            $this->messageManager->addNotice(__('Customer canceled payment'));
        }
        return $this->_redirect($this->_checkoutFinland->getCanceledOrderRedirectUrl());
    }
}