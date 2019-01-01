<?php
namespace Markup\Smartship\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SaveAgentToOrderObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectmanager)
    {
        $this->_objectManager = $objectmanager;
    }

    public function execute(EventObserver $observer)
    {
      $order = $observer->getEvent()->getData('order');
      $quoteRepository = $this->_objectManager->create('Magento\Quote\Model\QuoteRepository');
      /** @var \Magento\Quote\Model\Quote $quote */
      $quote = $quoteRepository->get($order->getQuoteId());

      // Do nothing for virtual orders
      if ($quote->isVirtual()) {
        return $this;
      }

      // Get agent ID from quote shipping address
      $quoteAddress = $quote->getShippingAddress();
      if ($quoteAddress) {
        $agentId = $quoteAddress->getSmartshipAgentId();

        // Set agent ID on order shipping address
        $orderAddress = $order->getShippingAddress();
        if ($orderAddress) {
          $orderAddress->setSmartshipAgentId($agentId);
          $orderAddress->save();
        }
      }

      return $this;
    }
}
