<?php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

$type_decimal_column = array(
    'type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
    'scale'    => '4',
    'precision'    => '12',
    'comment'   => 'CornerDrop Collect Column'
);

$type_int_column = array(
    'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'unsigned' => true,
    'comment'  => "CornerDrop Collect Column"
);

$type_boolean_column = array(
    'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'nullable' => false,
    'default'  => 0,
    'comment'  => "CornerDrop Collect Column"
);


$tables =  array(
    $installer->getTable('sales/quote_address') => array(
        CornerDrop_Collect_Helper_Data::IS_CORNERDROP_COLLECT => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        CornerDrop_Collect_Helper_Data::CORNERDROP_STORE_ID_COLUMN => $type_int_column,
        CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::CORNERDROP_TAX => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX => $type_decimal_column
    ),
    $installer->getTable('sales/order') => array(
        CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::CORNERDROP_TAX => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::ORDERED_NOTIFICATION_COLUMN => $type_boolean_column,
        CornerDrop_Collect_Helper_Data::SHIPPED_NOTIFICATION_COLUMN => $type_boolean_column,
        CornerDrop_Collect_Helper_Data::CANCELLED_NOTIFICATION_COLUMN => $type_boolean_column
    ),
    $installer->getTable('sales/order_address') => array(
        CornerDrop_Collect_Helper_Data::IS_CORNERDROP_COLLECT => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        CornerDrop_Collect_Helper_Data::CORNERDROP_STORE_ID_COLUMN => $type_int_column
    ),
    $installer->getTable('sales/invoice') => array(
        CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::CORNERDROP_TAX => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX => $type_decimal_column
    ),
    $installer->getTable('sales/creditmemo') => array(
        CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::CORNERDROP_TAX => $type_decimal_column,
        CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX => $type_decimal_column
    )
);

foreach($tables as $table => $columns) {
    foreach ($columns as $column => $type) {
        if (!$connection->tableColumnExists($table, $column)) {
            $connection->addColumn($table, $column, $type);
        }
    }
}

$installer->endSetup();
