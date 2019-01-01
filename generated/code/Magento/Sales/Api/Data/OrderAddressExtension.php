<?php
namespace Magento\Sales\Api\Data;

/**
 * Extension class for @see \Magento\Sales\Api\Data\OrderAddressInterface
 */
class OrderAddressExtension extends \Magento\Framework\Api\AbstractSimpleObject implements OrderAddressExtensionInterface
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
