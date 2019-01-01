<?php
namespace Piimega\CheckoutFinland\Model\Config\Source\Order\Status;

/**
 * Order Statuses source model
 */
class Canceled extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * @var string
     */
    protected $_stateStatuses = \Magento\Sales\Model\Order::STATE_CANCELED;
}
