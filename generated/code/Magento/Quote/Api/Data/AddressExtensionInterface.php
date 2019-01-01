<?php
namespace Magento\Quote\Api\Data;

/**
 * ExtensionInterface class for @see \Magento\Quote\Api\Data\AddressInterface
 */
interface AddressExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
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
