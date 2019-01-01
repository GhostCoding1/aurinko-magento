<?php

namespace Markup\Smartship\Plugin\Model\Order;

class OrderGet
{
  private $orderExtensionFactory;
  private $smartshipAgentFactory;

  /**
   * Init plugin
   */
  public function __construct(
    \Magento\Sales\Api\Data\OrderExtensionFactory $orderExtensionFactory,
    \Markup\Smartship\Model\Order\SmartshipAgentFactory $smartshipAgentFactory
  ) {
    $this->orderExtensionFactory = $orderExtensionFactory;
    $this->smartshipAgentFactory = $smartshipAgentFactory;
  }

  public function afterGet(
    \Magento\Sales\Api\OrderRepositoryInterface $subject,
    \Magento\Sales\Api\Data\OrderInterface $resultOrder
  ) {
    $resultOrder = $this->getAgent($resultOrder);

    return $resultOrder;
  }

  private function getAgent(\Magento\Sales\Api\Data\OrderInterface $order)
  {
    if ($order->getIsVirtual()) {
      return $order;
    }

    $extensionAttributes = $order->getExtensionAttributes();
    $shippingAddress = $order->getShippingAddress();

    if ($shippingAddress) {
      $agentJson = $shippingAddress->getSmartshipAgentId();

      if ($agentJson) {
        $agentData = json_decode($agentJson);

        $agent = $this->smartshipAgentFactory->create();
        $agent->setAgentId($agentData->id);
        $agent->setName($agentData->name);
        $agent->setStreetAddress($agentData->address);
        $agent->setPostcode($agentData->postcode);
        $agent->setCity($agentData->city);

        $orderExtension = $extensionAttributes ? $extensionAttributes : $this->orderExtensionFactory->create();
        $orderExtension->setSmartshipAgent($agent);
      }
    }

    return $order;
  }
}
