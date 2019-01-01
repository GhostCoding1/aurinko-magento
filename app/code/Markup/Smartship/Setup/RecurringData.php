<?php

namespace Markup\Smartship\Setup;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class RecurringData implements \Magento\Framework\Setup\InstallDataInterface
{
  private $productMetadata;

  /**
   * Constructor
   */
  public function __construct(
    \Magento\Framework\App\ProductMetadataInterface $productMetadata
  ) {
    $this->productMetadata = $productMetadata;
  }

  /**
   * {@inheritdoc}
   */
  public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
  {
    // If we are using Magento 2.2.x version, we need to convert PHP serialized
    // data into JSON encoded.
    if (version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')) {
      $this->convertSerializedToJson($setup, $context);
    }
  }

  /**
   * Converts serialized price data into JSON format
   */
  private function convertSerializedToJson(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
  {
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

    // These are not available in <= 2.2 versions so we need to use Object Manager
    $fieldDataConverterFactory = $objectManager->create('Magento\Framework\DB\FieldDataConverterFactory');
    $queryModifierFactory = $objectManager->create('Magento\Framework\DB\Select\QueryModifierFactory');
    $queryGenerator = $objectManager->create('Magento\Framework\DB\Query\Generator');

    $fieldDataConverter = $fieldDataConverterFactory->create(
      \Magento\Framework\DB\DataConverter\SerializedToJson::class
    );

    $select = $setup->getConnection()
      ->select()
      ->from(
        $setup->getTable('core_config_data'),
        ['config_id']
      )
      ->where('path LIKE "carriers/smartship/price_%"');

    $iterator = $queryGenerator->generate('config_id', $select);
    foreach ($iterator as $selectByRange) {
      $configIds = $setup->getConnection()->fetchCol($selectByRange);

      $queryModifier = $queryModifierFactory->create(
        'in',
        [
          'values' => [
            'config_id' => $configIds
          ]
        ]
      );

      $fieldDataConverter->convert(
        $setup->getConnection(),
        $setup->getTable('core_config_data'),
        'config_id',
        'value',
        $queryModifier
      );
    }
  }
}
