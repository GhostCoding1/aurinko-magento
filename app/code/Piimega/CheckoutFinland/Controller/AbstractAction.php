<?php
namespace Piimega\CheckoutFinland\Controller;

use \Magento\Framework\App\Action\Action;
use \Piimega\CheckoutFinland\Model\Checkoutfinland;
use \Magento\Checkout\Model\Session;
use \Magento\Sales\Model\OrderFactory;
use \Piimega\CheckoutFinland\Logger\Logger;
use \Magento\Framework\App\Action\Context;

abstract class AbstractAction extends Action {

    /**
     * @var \Piimega\CheckoutFinland\Model\Checkoutfinland
     */
    protected $_checkoutFinland;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Piimega\CheckoutFinland\Logger\Logger
     */
    protected $_logger;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var bool
     */
    protected $_restoreQuote = true;

    /**
     * @param \Piimega\CheckoutFinland\Model\Checkoutfinland $checkoutFinland
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Piimega\CheckoutFinland\Logger\Logger $logger
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        Checkoutfinland $checkoutFinland,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        Logger $logger,
        Context $context
    ) {
        $this->_checkoutFinland = $checkoutFinland;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_orderFactory = $orderFactory;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    protected function _getCanceledOrderStatus(){
        return $this->_checkoutFinland->getCanceledPaymentOrderStatus();
    }

    /**
     * Cancel order, return quote to customer
     *
     * @param string $adminMessage
     * @return bool
     */
    protected function _cancelOrder($adminMessage = ''){
        try{
            if($this->_order->canCancel()){
                if(empty($adminMessage)){
                    $adminMessage = __('Order was cancelled due to the payment failure');
                }

                $this->_order
                    ->cancel()
                    ->addStatusToHistory($this->_getCanceledOrderStatus(), $adminMessage)
                    ->save();

                /*
                 * Restore quote or clear checkout session quote data
                 */
                if($this->_order->getQuoteId() == $this->_checkoutSession->getLastQuoteId()){
                    if($this->_restoreQuote){
                        $this->_checkoutSession->restoreQuote();
                    } else {
                        $this->_checkoutSession
                            ->unsLastQuoteId()
                            ->unsLastSuccessQuoteId()
                            ->unsLastOrderId()
                            ->unsLastRealOrderId();
                    }
                }
                return true;
            }
        } catch(\Exception $e){
            $this->_logger->critical($e);
        }
        return false;
    }
}