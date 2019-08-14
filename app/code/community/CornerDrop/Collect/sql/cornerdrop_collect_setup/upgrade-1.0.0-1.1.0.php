<?php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

$type_int_column = array(
    'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'unsigned' => true,
    'comment'  => "CornerDrop Collect Column"
);

$tables =  array(
    $installer->getTable('sales/quote_address') => array(
        CornerDrop_Collect_Helper_Data::CORNERDROP_RESERVATION_CODE => $type_int_column,
    ),
    $installer->getTable('sales/order_address') => array(
        CornerDrop_Collect_Helper_Data::CORNERDROP_RESERVATION_CODE => $type_int_column
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
