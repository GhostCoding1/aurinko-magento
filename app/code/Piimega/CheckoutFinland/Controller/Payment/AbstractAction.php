<?php
namespace Piimega\CheckoutFinland\Controller\Payment;

use \Piimega\CheckoutFinland\Model\Checkoutfinland;
use \Magento\Checkout\Model\Session;
use \Magento\Sales\Model\OrderFactory;
use \Piimega\CheckoutFinland\Logger\Logger;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Data\FormFactory;
use \Magento\Framework\App\Action\Context;

/**
 * class Piimega\CheckoutFinland\Controller\Payment\AbstractAction
 */
abstract class AbstractAction extends \Piimega\CheckoutFinland\Controller\AbstractAction {

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $_formFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param \Piimega\CheckoutFinland\Model\Checkoutfinland $checkoutFinland
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Piimega\CheckoutFinland\Logger\Logger $logger
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        Checkoutfinland $checkoutFinland,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        Logger $logger,
        JsonFactory $resultJsonFactory,
        PageFactory $resultPageFactory,
        FormFactory $formFactory,
        Context $context
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_formFactory = $formFactory;
        $this->_resultPageFactory = $resultPageFactory;
        
        parent::__construct(
            $checkoutFinland, 
            $checkoutSession, 
            $orderFactory, 
            $logger, 
            $context
        );
    }

    /**
     * Extracts order from session data, sets data to the Checkoutfinland model
     *
     * @return bool
     */
    protected function _extractRequestedOrder(){
        $order = $this->_orderFactory->create();
        $order->loadByIncrementId($this->_checkoutSession->getLastRealOrderId());

        if($this->_checkoutFinland->isAllowedToCreateNewPayment($order)){
            $this->_order = $order;
            $this->_checkoutFinland->setInfoInstance($order->getPayment());
            return true;
        }
        $this->_logger->critical('Invalid session order in the redirect action: '
            .$this->_checkoutSession->getLastRealOrderId());
        return false;
    }
}