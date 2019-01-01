<?php
namespace Piimega\CheckoutFinland\Controller\Payment;

/**
 * class Piimega\CheckoutFinland\Controller\Payment\Html
 *
 * Used if config parameters "use_xml_mode" and "use_method_select" are set false
 * Redirects customer to Checkout Finland website for operator / bank selection
 */
class Html extends \Piimega\CheckoutFinland\Controller\Payment\AbstractAction{

    /**
     * {@inheritdoc}
     */
    public function execute(){
        if($this->getRequest()->getParam('is_ajax') && $this->_extractRequestedOrder()){
            
            $resultJson = $this->_resultJsonFactory->create();

            try{
                $checkoutData = $this->_checkoutFinland->getCheckoutData();
                $block = $this->_resultPageFactory
                    ->create()
                    ->getLayout()
                    ->createBlock($this->_checkoutFinland->getRedirectBlockType())
                    ->setUrl($this->_checkoutFinland->getCheckoutUrl())
                    ->setFields($checkoutData);

                return $resultJson->setData([
                    'success' => true,
                    'data' =>  $block->toHtml()
                ]);

            } catch(\Exception $e){
                $this->_logger->critical($e);
                if(!$this->_cancelOrder( __('Error redirecting to Checkout Finland'))){
                    $this->_logger->critical(get_class($this)
                        .'Attempt to cancel order which cannot be canceled. Order ID:' .$this->_order->getId());
                }
                $this->messageManager->addError(__('Cannot process payment at the moment. You have not been charged.'));
            }
            return $resultJson->setData([
                'success' => false
            ]);
        }
        $this->_forward('noroute');
    }
}