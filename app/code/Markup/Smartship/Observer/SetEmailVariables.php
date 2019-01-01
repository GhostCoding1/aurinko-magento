<?php
namespace Markup\Smartship\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SetEmailVariables implements ObserverInterface
{
  private $_quoteRepository;

  public function __construct(\Magento\Quote\Model\QuoteRepository $_quoteRepository)
  {
      $this->_quoteRepository = $_quoteRepository;
  }

  public function execute(EventObserver $observer)
  {
    $sender = $observer->getEvent()->getData('sender');
    $transport = $observer->getEvent()->getData('transport');
    $order = $transport->getData('order');
    $quote = $this->_quoteRepository->get($order->getQuoteId());

    if ( ! $order->getIsVirtual() && $quote) {
      list($carrier, $shippingMethod) = explode('_', $order->getShippingMethod());
      if ($carrier === 'smartship') {
        // Get agent data
        $shippingAddress = $quote->getShippingAddress();
        $agentData = $shippingAddress->getSmartshipAgentId();
        if ($agentData && $this->_validJson($agentData)) {
          $agent = json_decode($agentData);

          // Set agent data
          $transport->setData('smartshipAgentId', $agent->id);
          $transport->setData('smartshipAgentName', $agent->name);
          $transport->setData('smartshipAgentAddress', $agent->address);
          $transport->setData('smartshipAgentPostcode', $agent->postcode);
          $transport->setData('smartshipAgentCity', $agent->city);
        }
      }
    }

    return $this;
  }

  /**
   * Validates JSON
   */
  private function _validJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
  }
}
