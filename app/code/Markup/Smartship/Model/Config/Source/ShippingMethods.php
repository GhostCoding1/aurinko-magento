<?php
namespace Markup\Smartship\Model\Config\Source;

class ShippingMethods implements \Magento\Framework\Option\ArrayInterface
{
  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray()
  {
    return [
      ['value' => 'PO2103', 'label' => __('Postipaketti')],
      ['value' => 'PO2104', 'label' => __('Kotipaketti')],
      ['value' => 'PO2102', 'label' => __('Express')],
      ['value' => 'PO2461', 'label' => __('Pikkupaketti')],
      ['value' => 'PO2711', 'label' => __('Parcel Connect')],
      ['value' => 'ITPR', 'label' => __('Priority Parcel')],
      ['value' => 'PO2017', 'label' => __('EMS')],
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
