<?php
namespace Markup\Smartship\Block\Adminhtml\Order\View;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Address;

/**
 * SmartShip pickup agent class
 */
class Agent extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
  /**
   * Constructor
   */
  public function __construct(
    \Magento\Backend\Block\Template\Context $context,
    \Magento\Framework\Registry $registry,
    \Magento\Sales\Helper\Admin $adminHelper,
    array $data = []
  ) {
    parent::__construct($context, $registry, $adminHelper, $data);
  }

  /**
   * Get agent
   */
  public function getAgent()
  {
    $order = $this->getOrder();
    $shippingAddress = $order->getShippingAddress();

    if ($shippingAddress) {
      $agentJson = $shippingAddress->getSmartshipAgentId();

      if ($agentJson) {
        $agent = json_decode($agentJson);

        if (is_object($agent) && isset($agent->id)) {
          return $agent;
        }
      }
    }

    return FALSE;
  }

  /**
   * Get form action
   */
  public function getFormAction() {
    return $this->getUrl('smartship/agent/update');
  }
}
