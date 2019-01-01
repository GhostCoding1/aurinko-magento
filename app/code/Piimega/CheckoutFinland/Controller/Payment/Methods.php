<?php
namespace Piimega\CheckoutFinland\Controller\Payment;

/**
 * class Piimega\CheckoutFinland\Controller\Payment\Methods
 *
 * Used if config parameter "use_xml_mode" is set true and "use_method_select" is set false
 * Renders operators / banks on the own website instead of redirecting to Checkout Finland
 */
class Methods extends \Piimega\CheckoutFinland\Controller\Payment\AbstractAction{

    /**
     * {@inheritdoc}
     */
    public function execute(){
        if($this->_extractRequestedOrder()){
            try{
                $data = $this->_checkoutFinland->requestPaymentMethodsData();
                $page = $this->_resultPageFactory->create();
                $layout = $page->getLayout();
                $layout->getMessagesBlock()->setMessages($this->messageManager->getMessages(true));
                $layout->getBlock('checkoutfinland.payment.methods')->setMethodsData($data);
                return $page;

            } catch(\Exception $e){
                $this->_logger->critical($e);
                if(!$this->_cancelOrder( __('Error redirecting to Checkout Finland'))){
                    $this->_logger->critical(get_class($this)
                        .'Attempt to cancel order which cannot be canceled. Order ID:' .$this->_order->getId());
                }
                $this->messageManager->addError(__('Cannot process payment at the moment. You have not been charged.'));
                return $this->_redirect($this->_checkoutFinland->getCanceledOrderRedirectUrl());
            }
        }
        return $this->_redirect('');
    }
}