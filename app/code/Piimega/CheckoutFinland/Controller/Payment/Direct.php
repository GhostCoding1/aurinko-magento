<?php
namespace Piimega\CheckoutFinland\Controller\Payment;

/**
 * class Piimega\CheckoutFinland\Controller\Payment\Direct
 *
 * Used if config parameter "use_method_select" is set true
 * Redirects customer directly to the bank / operator after placing order (operator was selected during checkout)
 */
class Direct extends \Piimega\CheckoutFinland\Controller\Payment\AbstractAction{

    /**
     * {@inheritdoc}
     */
    public function execute(){
        if($this->getRequest()->getParam('is_ajax') && $this->_extractRequestedOrder()){
            $resultJson = $this->_resultJsonFactory->create();
            
            if($selectedMethod = $this->_checkoutFinland->getPreselectedMethod()){
                try{
                    $data = $this->_checkoutFinland->requestPaymentMethodsData();
                    foreach($data as $code => $method) {
                        if ($code == $selectedMethod) {
                            $url = isset($method['@attributes']['url']) ? $method['@attributes']['url'] : null;
                            unset($method['@attributes']);
                            if (!empty($url)) {
                                $block = $this->_resultPageFactory
                                    ->create()
                                    ->getLayout()
                                    ->createBlock($this->_checkoutFinland->getRedirectBlockType())
                                    ->setUrl($url)
                                    ->setFields($method);

                                return $resultJson->setData([
                                    'success' => true,
                                    'data' => $block->toHtml()
                                ]);
                            }
                        }
                    }
                } catch(\Exception $e){
                    
                    /*
                     * Order is not canceled here, because if this action fails,
                     * customer will be redirected to the "Methods" action
                     */
                    $this->_logger->critical($e);
                }
            }

            $this->messageManager->addError(
                __('Selected payment method is not available at the moment. Please choose another method.')
            );
            return $resultJson->setData([
                'success' => false,
                'redirect' => 'checkoutfinland/payment/methods'
            ]);
        }
        $this->_forward('noroute');
    }
}