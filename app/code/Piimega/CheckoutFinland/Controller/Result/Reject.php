<?php
namespace Piimega\CheckoutFinland\Controller\Result;

/**
 * class Piimega\CheckoutFinland\Controller\Result\Reject
 */
class Reject extends \Piimega\CheckoutFinland\Controller\Result\AbstractAction{

    /**
     * @var string
     */
    protected $_statusCodeGroup = \Piimega\CheckoutFinland\Model\Checkoutfinland::STATUS_REJECTED;

    /**
     * @return string
     */
    protected function _getCanceledOrderStatus(){
        return $this->_checkoutFinland->getRejectedPaymentOrderStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(){
        parent::execute();
        if($this->_validateRequestParams() && $this->_extractRequestedOrder()){
            if(!$this->_cancelOrder(__('Payment was rejected by service provider'))){
                $this->_logger->critical(get_class($this)
                    .'Payment was rejected, but order cannot be canceled. Order ID:' .$this->_order->getId());
            }
            $this->messageManager->addNotice(__('Payment was rejected by service provider'));
        }
        return $this->_redirect($this->_checkoutFinland->getCanceledOrderRedirectUrl());
    }
}