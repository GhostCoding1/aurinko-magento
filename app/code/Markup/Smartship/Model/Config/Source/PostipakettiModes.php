<?php
namespace Markup\Smartship\Model\Config\Source;

class PostipakettiModes implements \Magento\Framework\Option\ArrayInterface
{
  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray()
  {
    return [
      ['value' => 'combine', 'label' => __('Display all agents in a single shipping method')],
      ['value' => 'separate', 'label' => __('Separate offices and SmartPosts')],
    ];
  }

  /**
   * Get options in "key-value" format
   *
   * @return array
   */
  public function toArray()
  {
    $options = [];

    foreach ($this->toOptionArray() as $option) {
      $options[$option['value']] = $option['label'];
    }

    return $options;
  }
}
