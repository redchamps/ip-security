<?php
namespace RedChamps\IpSecurity\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'ipsecurity_log'
         */
        $ipSecurityLogTable = $installer->getConnection()->newTable(
            $installer->getTable('ipsecurity_log')
        )->addColumn(
            'logid',
            Table::TYPE_INTEGER,
            11,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Log Id'
        )->addColumn(
            'blocked_from',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Blocked From'
        )->addColumn(
            'blocked_ip',
            Table::TYPE_TEXT,
            23,
            ['nullable' => false],
            'Blocked IP'
        )->addColumn(
            'last_block_rule',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Last Block Rule'
        )->addColumn(
            'qty',
            Table::TYPE_INTEGER,
            11,
            ['unsigned' => true, 'nullable' => false],
            'Qty'
        )->addColumn(
            'create_time',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false],
            'Created At'
        )->addColumn(
            'update_time',
            Table::TYPE_DATETIME,
            null,
            ['default' => null],
            'Updated At'
        )->addIndex(
            $setup->getIdxName(
                'ipsecurity_log',
                ['blocked_from', 'blocked_ip'],
                true
            ),
            ['blocked_from', 'blocked_ip'],
            ['type' => 'unique']
        )->setComment(
            'ip security log - count of block qty'
        );

        $installer->getConnection()->createTable($ipSecurityLogTable);

        /**
         * Create table 'ipsecurity_token_log'
         */
        $ipSecurityTokenLogTable = $installer->getConnection()->newTable(
            $installer->getTable('ipsecurity_token_log')
        )->addColumn(
            'logid',
            Table::TYPE_INTEGER,
            11,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Log Id'
        )->addColumn(
            'blocked_ip',
            Table::TYPE_TEXT,
            23,
            ['nullable' => false],
            'Blocked IP'
        )->addColumn(
            'last_block_rule',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Blocked IP'
        )->addColumn(
            'blocked_from',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Blocked From'
        )->addColumn(
            'create_time',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false],
            'Created At'
        )->addColumn(
            'update_time',
            Table::TYPE_DATETIME,
            null,
            ['default' => null],
            'Updated At'
        )->addIndex(
            $setup->getIdxName(
                'ipsecurity_token_log',
                ['blocked_from', 'blocked_ip'],
                true
            ),
            ['blocked_from', 'blocked_ip'],
            ['type' => 'unique']
        )->setComment(
            'ip security token log'
        );

        $installer->getConnection()->createTable($ipSecurityTokenLogTable);
    }
}
