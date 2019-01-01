<?php
namespace Piimega\CheckoutFinland\Controller\Payment;

/**
 * class Piimega\CheckoutFinland\Controller\Payment\Cancel
 *
 * Used for local cancellation requests, while class Piimega\CheckoutFinland\Controller\Result\Cancel
 * used for cancellation requests coming from Checkout Finland. The main difference between these two
 * scenarios is in the method of obtaining order (here from customer session) and request params validation
 */
class Cancel extends \Piimega\CheckoutFinland\Controller\Payment\AbstractAction{

    /**
     * {@inheritdoc}
     */
    public function execute(){
        if($this->_extractRequestedOrder()){
            if(!$this->_cancelOrder(__('Customer canceled payment'))){
                $this->_logger->critical(get_class($this)
                    .'Attempt to cancel order which cannot be canceled. Order ID:' .$this->_order->getId());
            }
            $this->messageManager->addNotice(__('Customer canceled payment'));
            return $this->_redirect($this->_checkoutFinland->getCanceledOrderRedirectUrl());
        }
        return $this->_redirect('');
    }

    /**
     * Cancel order, return quote to customer
     *
     * @param string $adminMessage
     * @return bool
     */
    protected function _cancelOrder($adminMessage = ''){
        $this->_order->getPayment()
            ->setCfStatusCode(\Piimega\CheckoutFinland\Model\Checkoutfinland::LOCALLY_CANCELED_STATUS_CODE);
        return parent::_cancelOrder($adminMessage);
    }
}