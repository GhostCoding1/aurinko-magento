<?php
namespace Piimega\CheckoutFinland\Controller\Result;

use \Piimega\CheckoutFinland\Model\Checkoutfinland;
use \Magento\Checkout\Model\Session;
use \Magento\Sales\Model\OrderFactory;
use \Piimega\CheckoutFinland\Logger\Logger;
use \Magento\Framework\App\Action\Context;
use \Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * class Piimega\CheckoutFinland\Controller\Result\AbstractAction
 */
abstract class AbstractAction extends \Piimega\CheckoutFinland\Controller\AbstractAction {

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var array
     */
    protected $_requestParams = [];

    /**
     * @var string
     */
    protected $_statusCodeGroup = \Piimega\CheckoutFinland\Model\Checkoutfinland::STATUS_REJECTED;

    /**
     * @param \Piimega\CheckoutFinland\Model\Checkoutfinland $checkoutFinland
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Piimega\CheckoutFinland\Logger\Logger $logger
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        Checkoutfinland $checkoutFinland,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        Logger $logger,
        OrderSender $orderSender,
        Context $context
    ) {
        $this->_orderSender = $orderSender;
        
        parent::__construct(
            $checkoutFinland, 
            $checkoutSession, 
            $orderFactory, 
            $logger, 
            $context
        );
    }

    /**
     * Handle logging (if configured in the payment method settings)
     *
     * @return void
     */
    public function execute(){
        if($this->_checkoutFinland->logAllRequests()){
            $this->_logger->info(get_class($this) .' Request:', $this->getRequest()->getParams());
        }
    }

    /**
     * @return void
     */
    protected function _collectRequestParams(){
        $params = $this->getRequest()->getParams();
        $this->_requestParams['VERSION']        = isset($params['VERSION'])   ?   $params['VERSION']    : '';
        $this->_requestParams['STAMP']          = isset($params['STAMP'])     ?   $params['STAMP']      : '';
        $this->_requestParams['ORDER_ID']       = isset($params['REFERENCE']) ?   $params['REFERENCE']  : 0;
        $this->_requestParams['TRANSACTION_ID'] = isset($params['PAYMENT'])   ?   $params['PAYMENT']    : 0;
        $this->_requestParams['STATUS']         = isset($params['STATUS'])    ?   $params['STATUS']     : 0;
        $this->_requestParams['ALGORITHM']      = isset($params['ALGORITHM']) ?   $params['ALGORITHM']  : 0;
    }

    /**
     * Validates data received from Checkout Finland and logs errors if there are any
     *
     * @return bool
     */
    protected function _validateRequestParams(){
        $errors = [];

        if(empty($this->_requestParams)){
            $this->_collectRequestParams();
        }

        $originalMac = $this->getRequest()->getParam('MAC');
        $generatedMac = strtoupper(hash_hmac("sha256", implode('&', $this->_requestParams), $this->_checkoutFinland->getPassword()));

        if ($originalMac != $generatedMac) {
            $errors[] = "Error: Signatures do not match. MAC_ORIG:" .$originalMac ." MAC_GEN:" .$generatedMac;
        }

        if($this->_requestParams['VERSION'] != \Piimega\CheckoutFinland\Model\Checkoutfinland::PAYMENT_VERSION){
            $errors[] = "Error: Invalid payment version: " .$this->_requestParams['VERSION'];
        }

        if($this->_requestParams['ALGORITHM'] != \Piimega\CheckoutFinland\Model\Checkoutfinland::PAYMENT_ALGORITHM){
            $errors[] = "Error: Invalid payment algorithm: " .$this->_requestParams['ALGORITHM'];
        }

        if($this->_requestParams['TRANSACTION_ID'] <= 0){
            $errors[] = "Error: Invalid transaction ID: " .$this->_requestParams['TRANSACTION_ID'];
        }

        $status = intval($this->_requestParams['STATUS']);
        if(!in_array($status, $this->_checkoutFinland->getStatusCodes($this->_statusCodeGroup))){
            $errors[] = "Error: Invalid status code: " .$this->_requestParams['STATUS'];
        }

        if(!empty($errors)){
            $this->_logRequestValidationErrors($errors);
            return false;
        }
        return true;
    }

    /**
     * Extracts and validates order according to the received number, sets data to the Checkoutfinland model
     *
     * @return bool
     */
    protected function _extractRequestedOrder(){
        $errors = [];

        if(empty($this->_requestParams)){
            $this->_collectRequestParams();
        }

        $order = $this->_orderFactory->create()->loadByIncrementId($this->_requestParams['ORDER_ID']);
        if($this->_checkoutFinland->isValidOrderObject($order)){
            if($this->_requestParams['STAMP'] != $order->getPayment()->getCfStamp()){
                $errors[] = "Error: Stamps do not match";
            }
        } else {
            $errors[] = "Error: Invalid order id: " .$this->_requestParams['ORDER_ID'];
        }

        if(!empty($errors)){
            $this->_logRequestValidationErrors($errors);
            return false;
        }

        /**
         * Set data to the payment instance
         */
        $order->getPayment()
            ->setCfTransactionId($this->_requestParams['TRANSACTION_ID'])
            ->setCfStatusCode($this->_requestParams['STATUS']);
        $this->_checkoutFinland->setInfoInstance($order->getPayment());

        $this->_order = $order;
        
        return true;
    }

    /**
     * @param array $errors
     * @return void
     */
    protected function _logRequestValidationErrors(&$errors){
        $this->_logger->critical('Validation failed in ' .get_class($this));
        $this->_logger->critical('Received data:', $this->getRequest()->getParams());
        foreach($errors as $error){
            $this->_logger->critical($error);
        }
    }

    /**
     * @return void
     */
    protected function _sendNewOrderEmail(){
        if(!$this->_order->getEmailSend()){
            try{
                $this->_order->setCanSendNewEmailFlag(true);
                $this->_orderSender->send($this->_order);
                $this->_order->setEmailSent(true);
            } catch(\Exception $e){
                $this->_logger->critical('Failed to send new order email for order ' .$this->_order->getId());
            }
        }
    }
}