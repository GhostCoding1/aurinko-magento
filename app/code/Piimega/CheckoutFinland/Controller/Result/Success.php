<?php
namespace Piimega\CheckoutFinland\Controller\Result;

/**
 * class Piimega\CheckoutFinland\Controller\Result\Success
 */
class Success extends \Piimega\CheckoutFinland\Controller\Result\AbstractAction{

    /**
     * @var string
     */
    protected $_statusCodeGroup = \Piimega\CheckoutFinland\Model\Checkoutfinland::STATUS_SUCCESS;

    /**
     * {@inheritdoc}
     */
    public function execute(){
        parent::execute();
        if($this->_validateRequestParams() && $this->_extractRequestedOrder()){
            if($this->_checkoutFinland->processPaymentSuccess()){
                try{
                    $this->_order
                        ->addStatusToHistory($this->_checkoutFinland->getSuccessPaymentOrderStatus(),
                            __('Payment approved by service provider'));
                    $this->_sendNewOrderEmail();
                    $this->_order->save();
                    
                    return $this->_redirect($this->_checkoutFinland->getPaymentSuccessRedirectUrl());
                } catch (\Exception $e){
                    $this->_logger->critical($e);
                }
            }
            $this->messageManager->addError(__('Error while processing order'));
            return $this->_redirect($this->_checkoutFinland->getPaymentFailureRedirectUrl());
        }
    }
}