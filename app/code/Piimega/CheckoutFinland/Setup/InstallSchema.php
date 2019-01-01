<?php
namespace Piimega\CheckoutFinland\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context){
        $installer = $setup;
        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_payment'),
            'cf_transaction_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => '31',
                'comment' => 'Checkout Finland Transaction ID'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_payment'),
            'cf_stamp',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                'nullable' => true,
                'comment' => 'Checkout Finland Payment Timestamp'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_payment'),
            'cf_status_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => true,
                'unsigned' => false,
                'comment' => 'Checkout Finland Payment Status Code'
            ]
        );

        $installer->endSetup();
    }
}
