<?php

namespace Markup\Smartship\Setup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 *
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
      $installer = $setup;
      $installer->startSetup();
      $installer->getConnection()->addColumn(
          $installer->getTable('quote_address'),
          'smartship_agent_id',
          [
              'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
              'nullable' => false,
              'comment' => 'SmartShip Agent ID',
          ]
      );
      $installer->getConnection()->addColumn(
          $installer->getTable('sales_order_address'),
          'smartship_agent_id',
          [
              'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
              'nullable' => false,
              'comment' => 'SmartShip Agent ID',
          ]
      );
      $setup->endSetup();
    }
}
