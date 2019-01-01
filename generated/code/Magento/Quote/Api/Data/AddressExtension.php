<?php
namespace Magento\Quote\Api\Data;

/**
 * Extension class for @see \Magento\Quote\Api\Data\AddressInterface
 */
class AddressExtension extends \Magento\Framework\Api\AbstractSimpleObject implements AddressExtensionInterface
{
    /**
     * @return string|null
     */
    public function getSmartshipAgentId()
    {
        return $this->_get('smartship_agent_id');
    }

    /**
     * @param string $smartshipAgentId
     * @return $this
     */
    public function setSmartshipAgentId($smartshipAgentId)
    {
        $this->setData('smartship_agent_id', $smartshipAgentId);
        return $this;
    }
}
