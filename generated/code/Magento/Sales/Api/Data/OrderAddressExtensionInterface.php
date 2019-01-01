<?php
namespace Magento\Sales\Api\Data;

/**
 * ExtensionInterface class for @see \Magento\Sales\Api\Data\OrderAddressInterface
 */
interface OrderAddressExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    /**
     * @return string|null
     */
    public function getSmartshipAgentId();

    /**
     * @param string $smartshipAgentId
     * @return $this
     */
    public function setSmartshipAgentId($smartshipAgentId);
}
