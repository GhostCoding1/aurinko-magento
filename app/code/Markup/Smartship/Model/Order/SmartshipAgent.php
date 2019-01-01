<?php
namespace Markup\Smartship\Model\Order;

class SmartshipAgent extends \Magento\Framework\DataObject implements \Markup\Smartship\Api\Data\SmartshipAgentInterface
{
  /**
   * {@inheritdoc}
   */
  public function getAgentId()
  {
    return $this->getData(self::AGENT_ID);
  }

  /**
   * {@inheritdoc}
   */
  public function setAgentId($value)
  {
    return $this->setData(self::AGENT_ID, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getName()
  {
    return $this->getData(self::NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function setName($value)
  {
    return $this->setData(self::NAME, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getStreetAddress()
  {
    return $this->getData(self::STREET_ADDRESS);
  }

  /**
   * {@inheritdoc}
   */
  public function setStreetAddress($value)
  {
    return $this->setData(self::STREET_ADDRESS, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getPostcode()
  {
    return $this->getData(self::POSTCODE);
  }

  /**
   * {@inheritdoc}
   */
  public function setPostcode($value)
  {
    return $this->setData(self::POSTCODE, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getCity()
  {
    return $this->getData(self::CITY);
  }

  /**
   * {@inheritdoc}
   */
  public function setCity($value)
  {
    return $this->setData(self::CITY, $value);
  }

  public function __toString() {
    return serialize($this);
  }
}
